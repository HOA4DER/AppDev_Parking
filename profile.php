<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['is_signed_in']) || empty($_SESSION['user_id'])) {
    header('Location: main.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$vehicle_categories = [
    'Motorcycle (below 150cc)',
    'Bigbike (Above 400cc)',
    '4wheels (Sedan)',
    '4wheels (SUV)',
    '4wheels (Pickup)',
    '4wheels (Mid-size SUV)'
];

function redirectProfile($message, $type = 'success') {
    $_SESSION['profile_flash'] = [
        'message' => $message,
        'type' => $type
    ];
    header('Location: profile.php');
    exit;
}

function cleanPlate($plate) {
    return strtoupper(trim($plate));
}

function userVehicleCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_vehicles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return intval($stmt->fetchColumn());
}

function syncMultipleVehicleFlag($pdo, $user_id) {
    $count = userVehicleCount($pdo, $user_id);
    $stmt = $pdo->prepare("UPDATE users SET has_multiple_vehicles = ? WHERE id = ?");
    $stmt->execute([$count > 0 ? 1 : 0, $user_id]);
}

function plateExistsForUser($pdo, $user_id, $plate, $ignore_vehicle_id = 0) {
    $stmt = $pdo->prepare("SELECT plate_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $default_plate = $stmt->fetchColumn();
    if ($default_plate && strtoupper($default_plate) === strtoupper($plate)) {
        return true;
    }

    $sql = "SELECT COUNT(*) FROM user_vehicles WHERE user_id = ? AND UPPER(plate_number) = UPPER(?)";
    $params = [$user_id, $plate];
    if ($ignore_vehicle_id > 0) {
        $sql .= " AND id <> ?";
        $params[] = $ignore_vehicle_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return intval($stmt->fetchColumn()) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_profile') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $phone_number = trim($_POST['phone_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $plate_number = cleanPlate($_POST['plate_number'] ?? '');
            $default_vehicle_category = trim($_POST['default_vehicle_category'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');

            if ($first_name === '' || $last_name === '' || $phone_number === '' || $email === '' || $plate_number === '' || $default_vehicle_category === '') {
                redirectProfile('Please complete all required profile fields.', 'error');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                redirectProfile('Please enter a valid email address.', 'error');
            }
            if (!preg_match('/^[0-9]{11}$/', $phone_number)) {
                redirectProfile('Phone number must be 11 digits, for example 09XXXXXXXXX.', 'error');
            }
            if (strlen($plate_number) < 4 || strlen($plate_number) > 15) {
                redirectProfile('Plate number must be between 4 and 15 characters.', 'error');
            }
            if (!in_array($default_vehicle_category, $vehicle_categories, true)) {
                redirectProfile('Please choose a valid default vehicle type.', 'error');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $user_id]);
            if (intval($stmt->fetchColumn()) > 0) {
                redirectProfile('That email address is already used by another account.', 'error');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_vehicles WHERE user_id = ? AND UPPER(plate_number) = UPPER(?)");
            $stmt->execute([$user_id, $plate_number]);
            if (intval($stmt->fetchColumn()) > 0) {
                redirectProfile('That plate is already listed as an additional vehicle. Use a unique default plate.', 'error');
            }

            if ($new_password !== '') {
                if (strlen($new_password) < 6) {
                    redirectProfile('New password must be at least 6 characters.', 'error');
                }
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, email = ?, plate_number = ?, default_vehicle_category = ?, password = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone_number, $email, $plate_number, $default_vehicle_category, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ?, email = ?, plate_number = ?, default_vehicle_category = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone_number, $email, $plate_number, $default_vehicle_category, $user_id]);
            }

            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['phone_number'] = $phone_number;
            $_SESSION['plate_number'] = $plate_number;

            redirectProfile('Profile updated.');
        }

        if ($action === 'add_vehicle') {
            $plate = cleanPlate($_POST['plate'] ?? '');
            $category = trim($_POST['category'] ?? '');

            if ($plate === '' || $category === '') {
                redirectProfile('Enter a plate number and vehicle type before adding a vehicle.', 'error');
            }
            if (strlen($plate) < 4 || strlen($plate) > 15) {
                redirectProfile('Vehicle plate must be between 4 and 15 characters.', 'error');
            }
            if (!in_array($category, $vehicle_categories, true)) {
                redirectProfile('Please choose a valid vehicle type.', 'error');
            }
            if (plateExistsForUser($pdo, $user_id, $plate)) {
                redirectProfile('That plate is already registered on your account.', 'error');
            }

            $stmt = $pdo->prepare("INSERT INTO user_vehicles (user_id, plate_number, category) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $plate, $category]);
            syncMultipleVehicleFlag($pdo, $user_id);
            redirectProfile('Vehicle added.');
        }

        if ($action === 'update_vehicle') {
            $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
            $plate = cleanPlate($_POST['plate'] ?? '');
            $category = trim($_POST['category'] ?? '');

            if ($vehicle_id <= 0 || $plate === '' || $category === '') {
                redirectProfile('Vehicle update is missing required details.', 'error');
            }
            if (strlen($plate) < 4 || strlen($plate) > 15) {
                redirectProfile('Vehicle plate must be between 4 and 15 characters.', 'error');
            }
            if (!in_array($category, $vehicle_categories, true)) {
                redirectProfile('Please choose a valid vehicle type.', 'error');
            }
            if (plateExistsForUser($pdo, $user_id, $plate, $vehicle_id)) {
                redirectProfile('That plate is already registered on your account.', 'error');
            }

            $stmt = $pdo->prepare("UPDATE user_vehicles SET plate_number = ?, category = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$plate, $category, $vehicle_id, $user_id]);
            redirectProfile($stmt->rowCount() > 0 ? 'Vehicle updated.' : 'No vehicle changes were saved.', $stmt->rowCount() > 0 ? 'success' : 'error');
        }

        if ($action === 'delete_vehicle') {
            $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
            if ($vehicle_id <= 0) {
                redirectProfile('Vehicle could not be found.', 'error');
            }

            $stmt = $pdo->prepare("DELETE FROM user_vehicles WHERE id = ? AND user_id = ?");
            $stmt->execute([$vehicle_id, $user_id]);
            syncMultipleVehicleFlag($pdo, $user_id);
            redirectProfile($stmt->rowCount() > 0 ? 'Vehicle removed.' : 'Vehicle could not be removed.', $stmt->rowCount() > 0 ? 'success' : 'error');
        }
    } catch (Exception $e) {
        redirectProfile('Profile update failed: ' . $e->getMessage(), 'error');
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: main.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);
$default_category = $user['default_vehicle_category'] ?? '4wheels (Sedan)';
if (!in_array($default_category, $vehicle_categories, true)) {
    $default_category = '4wheels (Sedan)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>User Profile | Siksik Parking</title>
    <script src="script.js?v=20260710-message" defer></script>
    <style>
        .profile-page-grid {
            display: grid;
            grid-template-columns: minmax(260px, 0.85fr) minmax(0, 1.45fr);
            gap: 24px;
            align-items: start;
            margin-top: 28px;
        }
        .profile-panel {
            box-sizing: border-box;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            background: rgba(17, 19, 24, 0.78);
            padding: 22px;
            color: white;
        }
        .profile-panel + .profile-panel {
            margin-top: 18px;
        }
        .profile-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
        }
        .profile-panel-title {
            margin: 0;
            color: white;
            font-size: 18px;
            line-height: 1.25;
        }
        .profile-panel-copy {
            margin: 6px 0 0;
            color: #8d8b8b;
            font-size: 13px;
            line-height: 1.5;
        }
        .profile-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .profile-form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .profile-form-field.full {
            grid-column: 1 / -1;
        }
        .profile-form-field label {
            color: #8d8b8b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .profile-input,
        .profile-select {
            box-sizing: border-box;
            width: 100%;
            min-height: 42px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            background: rgba(8, 8, 8, 0.48);
            color: white;
            padding: 10px 12px;
            font: 14px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .profile-input:focus,
        .profile-select:focus {
            outline: none;
            border-color: rgba(0, 212, 168, 0.55);
            box-shadow: 0 0 0 3px rgba(0, 212, 168, 0.1);
        }
        .profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }
        .profile-primary-btn,
        .profile-secondary-btn,
        .profile-danger-btn {
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .profile-primary-btn {
            border: none;
            background: #00d4a8;
            color: #04110f;
        }
        .profile-secondary-btn {
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: transparent;
            color: #e2e6ed;
            text-decoration: none;
        }
        .profile-danger-btn {
            border: 1px solid rgba(255, 77, 77, 0.35);
            background: rgba(255, 77, 77, 0.06);
            color: #ff7d7d;
        }
        .profile-summary {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .profile-avatar-large {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 212, 168, 0.45);
            background: rgba(0, 212, 168, 0.12);
            color: #00d4a8;
            font-size: 28px;
            font-weight: 800;
        }
        .profile-meta-row {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 12px;
        }
        .profile-meta-label {
            display: block;
            color: #555;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .profile-meta-value {
            color: #e2e6ed;
            font-size: 14px;
        }
        .vehicle-list {
            display: grid;
            gap: 12px;
        }
        .vehicle-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(8, 8, 8, 0.34);
            padding: 14px;
        }
        .vehicle-form-row {
            display: grid;
            grid-template-columns: minmax(130px, 0.8fr) minmax(180px, 1fr) auto auto;
            gap: 10px;
            align-items: end;
        }
        .default-vehicle-pill {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            border: 1px solid rgba(0, 212, 168, 0.32);
            border-radius: 999px;
            color: #00d4a8;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
        }
        .profile-flash {
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 18px;
            font-size: 13px;
            border: 1px solid rgba(0, 212, 168, 0.28);
            background: rgba(0, 212, 168, 0.08);
            color: #00d4a8;
        }
        .profile-flash.error {
            border-color: rgba(255, 77, 77, 0.32);
            background: rgba(255, 77, 77, 0.08);
            color: #ff8a8a;
        }
        @media (max-width: 980px) {
            .profile-page-grid {
                grid-template-columns: 1fr;
            }
            .vehicle-form-row {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .profile-form-grid {
                grid-template-columns: 1fr;
            }
            .profile-actions {
                flex-direction: column;
            }
            .profile-primary-btn,
            .profile-secondary-btn,
            .profile-danger-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <section class="booking-page-section">
        <div class="main-page-header-container booking-page-header">
            <div class="logo-system-name-container">
                <img src="images/logo.png" class="logo-icon" alt="Siksik logo">
                <span class="system-name">Siksik</span>
            </div>

            <div class="nav-bar-container">
                <a class="nav-bar-btn" href="index.php#home">Home</a>
                <a class="nav-bar-btn" href="book.php">Book Parking</a>
                <a class="nav-bar-btn" href="dashboard.php">Dashboard</a>
                <a class="nav-bar-btn active-nav-btn" href="profile.php">Profile</a>
                <a class="nav-bar-btn" href="index.php#contact">Contact</a>
            </div>

            <div class="sign-in-container" style="gap: 12px;">
                <a class="SignIn-btn" href="book.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Book Now
                </a>
                <a class="SignIn-btn" href="logout.php">
                    Sign Out
                </a>
            </div>
        </div>

        <main class="booking-shell">
            <div class="booking-title-row">
                <div>
                    <span class="booking-kicker">USER PROFILE</span>
                    <h1 class="booking-title">Profile and Vehicles</h1>
                </div>
                <div class="booking-live-pill">
                    <div class="pulse-green"></div>
                    <span><?php echo count($vehicles) + 1; ?> registered vehicle<?php echo count($vehicles) === 0 ? '' : 's'; ?></span>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="profile-flash <?php echo $flash['type'] === 'error' ? 'error' : ''; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="profile-page-grid">
                <aside class="profile-panel">
                    <div class="profile-summary">
                        <div class="profile-avatar-large">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h2 class="profile-panel-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                            <p class="profile-panel-copy"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="profile-meta-row">
                            <span class="profile-meta-label">Default Vehicle</span>
                            <span class="profile-meta-value"><?php echo htmlspecialchars($user['plate_number']); ?>, <?php echo htmlspecialchars($default_category); ?></span>
                        </div>
                        <div class="profile-meta-row">
                            <span class="profile-meta-label">Phone</span>
                            <span class="profile-meta-value"><?php echo htmlspecialchars($user['phone_number']); ?></span>
                        </div>
                        <div class="profile-meta-row">
                            <span class="profile-meta-label">Account Created</span>
                            <span class="profile-meta-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <a class="profile-secondary-btn" href="dashboard.php" style="text-align: center;">Back to Dashboard</a>
                    </div>
                </aside>

                <section>
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <div>
                                <h2 class="profile-panel-title">Account Details</h2>
                                <p class="profile-panel-copy">Update your contact details and default vehicle used for bookings.</p>
                            </div>
                            <span class="default-vehicle-pill">Default Vehicle</span>
                        </div>

                        <form method="post">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="profile-form-grid">
                                <div class="profile-form-field">
                                    <label for="first_name">First Name</label>
                                    <input class="profile-input" id="first_name" name="first_name" type="text" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="last_name">Last Name</label>
                                    <input class="profile-input" id="last_name" name="last_name" type="text" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="phone_number">Phone Number</label>
                                    <input class="profile-input" id="phone_number" name="phone_number" type="tel" maxlength="11" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="email">Email</label>
                                    <input class="profile-input" id="email" name="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="plate_number">Default Plate Number</label>
                                    <input class="profile-input" id="plate_number" name="plate_number" type="text" maxlength="15" value="<?php echo htmlspecialchars($user['plate_number']); ?>" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="default_vehicle_category">Default Vehicle Type</label>
                                    <select class="profile-select" id="default_vehicle_category" name="default_vehicle_category" required>
                                        <?php foreach ($vehicle_categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $default_category === $category ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="profile-form-field full">
                                    <label for="new_password">New Password</label>
                                    <input class="profile-input" id="new_password" name="new_password" type="password" placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                            <div class="profile-actions">
                                <button class="profile-primary-btn" type="submit">Save Profile</button>
                            </div>
                        </form>
                    </div>

                    <div class="profile-panel" id="vehicles">
                        <div class="profile-panel-header">
                            <div>
                                <h2 class="profile-panel-title">Additional Vehicles</h2>
                                <p class="profile-panel-copy">Add multiple vehicles and choose one when making a parking reservation.</p>
                            </div>
                        </div>

                        <form method="post" class="vehicle-card" style="margin-bottom: 14px;">
                            <input type="hidden" name="action" value="add_vehicle">
                            <div class="vehicle-form-row">
                                <div class="profile-form-field">
                                    <label for="new_vehicle_plate">Plate Number</label>
                                    <input class="profile-input" id="new_vehicle_plate" name="plate" type="text" maxlength="15" placeholder="ABC 1234" required>
                                </div>
                                <div class="profile-form-field">
                                    <label for="new_vehicle_category">Vehicle Type</label>
                                    <select class="profile-select" id="new_vehicle_category" name="category" required>
                                        <?php foreach ($vehicle_categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="profile-primary-btn" type="submit">Add Vehicle</button>
                            </div>
                        </form>

                        <div class="vehicle-list">
                            <?php if (empty($vehicles)): ?>
                                <div class="vehicle-card">
                                    <p class="profile-panel-copy" style="margin: 0;">No additional vehicles yet. Your default vehicle can already be used for bookings.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <form method="post" class="vehicle-card">
                                        <input type="hidden" name="vehicle_id" value="<?php echo intval($vehicle['id']); ?>">
                                        <div class="vehicle-form-row">
                                            <div class="profile-form-field">
                                                <label>Plate Number</label>
                                                <input class="profile-input" name="plate" type="text" maxlength="15" value="<?php echo htmlspecialchars($vehicle['plate_number']); ?>" required>
                                            </div>
                                            <div class="profile-form-field">
                                                <label>Vehicle Type</label>
                                                <select class="profile-select" name="category" required>
                                                    <?php foreach ($vehicle_categories as $category): ?>
                                                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $vehicle['category'] === $category ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button class="profile-secondary-btn" type="submit" name="action" value="update_vehicle">Update</button>
                                            <button class="profile-danger-btn" type="submit" name="action" value="delete_vehicle" onclick="return confirm('Remove this vehicle from your profile?');">Remove</button>
                                        </div>
                                    </form>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </section>
</body>
</html>
