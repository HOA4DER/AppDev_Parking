<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['is_signed_in']) || empty($_SESSION['user_id'])) {
    header('Location: main.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'toggle_multiple_vehicles') {
        $status = isset($_POST['status']) && $_POST['status'] === 'true' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET has_multiple_vehicles = ? WHERE id = ?");
        $success = $stmt->execute([$status, $user_id]);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action === 'get_vehicles') {
        $stmt = $pdo->prepare("SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['vehicles' => $vehicles]);
        exit;
    }
    
    if ($action === 'add_vehicle') {
        $plate = strtoupper(trim($_POST['plate'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        if (empty($plate) || empty($category)) {
            echo json_encode(['success' => false, 'error' => 'Missing fields.']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO user_vehicles (user_id, plate_number, category) VALUES (?, ?, ?)");
        $success = $stmt->execute([$user_id, $plate, $category]);
        $vehicle_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE users SET has_multiple_vehicles = 1 WHERE id = ?");
        $stmt->execute([$user_id]);

        echo json_encode(['success' => $success, 'id' => $vehicle_id, 'plate' => $plate, 'category' => $category]);
        exit;
    }

    if ($action === 'edit_vehicle') {
        $id = intval($_POST['id'] ?? 0);
        $plate = strtoupper(trim($_POST['plate'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        if (empty($plate) || empty($category) || $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Missing fields.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE user_vehicles SET plate_number = ?, category = ? WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$plate, $category, $id, $user_id]);
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($action === 'delete_vehicle') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM user_vehicles WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$id, $user_id]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_vehicles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $count = intval($stmt->fetchColumn());
        if ($count === 0) {
            $stmt = $pdo->prepare("UPDATE users SET has_multiple_vehicles = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
        }

        echo json_encode(['success' => $success]);
        exit;
    }
}
date_default_timezone_set('Asia/Manila');
$current_time = date('H:i:s');
$current_date = date('Y-m-d');

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: main.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date DESC, arrival_time DESC");
    $stmt->execute([$user_id]);
    $all_bookings = $stmt->fetchAll();

    $active_bookings = [];
    $completed_bookings = [];
    $void_bookings = [];
    $cancelled_bookings = [];
    $total_spent = 0.0;

    foreach ($all_bookings as $booking) {
        if ($booking['status'] === 'cancelled') {
            $cancelled_bookings[] = $booking;
        } else {
            $booking_date = $booking['booking_date'];
            $arrival_time = $booking['arrival_time'];
            
            $grace_end_ts = strtotime($arrival_time) + 1800;
            $grace_end_time = date('H:i:s', $grace_end_ts);

            $past_grace_period = false;
            if ($booking_date < $current_date) {
                $past_grace_period = true;
            } elseif ($booking_date === $current_date) {
                if (date('H:i:s') > $grace_end_time) {
                    $past_grace_period = true;
                }
            }

            if ($past_grace_period) {
                if (intval($booking['id']) % 2 === 0) {
                    $completed_bookings[] = $booking;
                    $total_spent += floatval($booking['total_amount']);
                } else {
                    $void_bookings[] = $booking;
                    $total_spent += (floatval($booking['total_amount']) * 0.5);
                }
            } else {
                $active_bookings[] = $booking;
                $total_spent += floatval($booking['total_amount']);
            }
        }
    }

    $total_bookings = count($all_bookings);

} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}

function formatTime($timeStr) {
    $timeParts = explode(':', $timeStr);
    $hours = intval($timeParts[0]);
    $minutes = $timeParts[1] ?? '00';
    $period = $hours >= 12 ? 'PM' : 'AM';
    $hours = $hours % 12 ?: 12;
    return $hours . ':' . $minutes . ' ' . $period;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>User Dashboard | Siksik Parking</title>
    <script src="script.js?v=20260710-auth" defer></script>
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
                <a class="nav-bar-btn" href="profile.php">Profile</a>
                <a class="nav-bar-btn" href="index.php#pricing">Pricing</a>
                <a class="nav-bar-btn" href="index.php#about-us">About Us</a>
                <a class="nav-bar-btn" href="index.php#contact">Contact</a>
            </div>

            <div class="sign-in-container" style="gap: 12px;">
                <a class="SignIn-btn" href="dashboard.php">
                    Dashboard
                </a>
                <a class="SignIn-btn" href="profile.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Profile
                </a>
                <a class="SignIn-btn" href="book.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Book Now
                </a>
                <a class="SignIn-btn" href="index.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Home
                </a>
                <a class="SignIn-btn" href="logout.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Sign Out
                </a>
            </div>
        </div>

        <main class="booking-shell">
            <div class="booking-title-row">
                <div>
                    <span class="booking-kicker">USER PORTAL</span>
                    <h1 class="booking-title">My Dashboard</h1>
                </div>
                <div class="booking-live-pill">
                    <div class="pulse-green"></div>
                    <span>Account Status: Active</span>
                </div>
            </div>

            <div class="dashboard-grid">
                <aside class="profile-sidebar">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <div style="text-align: center;">
                        <h2 style="margin: 0; font-size: 18px; color: white;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #8d8b8b;"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="profile-info" style="margin-bottom: 0;">
                        <div>
                            <span>Phone Number</span>
                            <strong><?php echo htmlspecialchars($user['phone_number']); ?></strong>
                        </div>
                        <div>
                            <span>Plate Number/s</span>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-end; text-align: right;" id="sidebar-plates-list">
                                <strong style="font-family: monospace; font-size: 13px; color: white;"><?php echo htmlspecialchars($user['plate_number']); ?> <span style="font-size: 9px; color: #8d8b8b; font-weight: normal; text-transform: uppercase;">(<?php echo htmlspecialchars($user['default_vehicle_category'] ?? '4wheels (Sedan)'); ?>)</span> <span style="font-size: 9px; color: #00d4a8; font-weight: normal; border: 1px solid rgba(0,212,168,0.3); padding: 1px 4px; border-radius: 4px; margin-left: 2px;">DEFAULT</span></strong>
                                <?php
                                $stmt_v = $pdo->prepare("SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY id DESC");
                                $stmt_v->execute([$user_id]);
                                $sidebar_vehicles = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($sidebar_vehicles as $sv) {
                                    echo '<strong style="font-family: monospace; font-size: 13px; color: white;">' . htmlspecialchars($sv['plate_number']) . ' <span style="font-size: 9px; color: #8d8b8b; font-weight: normal; text-transform: uppercase;">(' . htmlspecialchars($sv['category']) . ')</span></strong>';
                                }
                                ?>
                            </div>
                        </div>
                        <div>
                            <span>Account Created</span>
                            <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
                        </div>
                    </div>

                    <a href="profile.php#vehicles" class="cancel-booking-btn" style="display: block; text-align: center; text-decoration: none; width: 100%; box-sizing: border-box; border-color: rgba(0, 212, 168, 0.3); background: rgba(0, 212, 168, 0.05); color: #00d4a8; margin-top: 16px; font-weight: 500; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Edit Profile and Vehicles</a>
                </aside>

                <section class="dashboard-main">
                    <div class="stats-row">
                        <div class="stat-card">
                            <span class="stat-val"><?php echo count($active_bookings); ?></span>
                            <span class="stat-lbl">Active</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-val"><?php echo count($completed_bookings); ?></span>
                            <span class="stat-lbl">Completed</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-val"><?php echo count($void_bookings); ?></span>
                            <span class="stat-lbl">Void</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-val"><?php echo count($cancelled_bookings); ?></span>
                            <span class="stat-lbl">Cancelled</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-val">₱<?php echo number_format($total_spent, 2); ?></span>
                            <span class="stat-lbl">Total Spent</span>
                        </div>
                    </div>

                    <div class="tabs-header">
                        <button class="tab-btn active" onclick="switchDashboardTab(event, 'tab-active')">Active (<?php echo count($active_bookings); ?>)</button>
                        <button class="tab-btn" onclick="switchDashboardTab(event, 'tab-completed')">Completed (<?php echo count($completed_bookings); ?>)</button>
                        <button class="tab-btn" onclick="switchDashboardTab(event, 'tab-void')">Void (<?php echo count($void_bookings); ?>)</button>
                        <button class="tab-btn" onclick="switchDashboardTab(event, 'tab-cancelled')">Cancelled (<?php echo count($cancelled_bookings); ?>)</button>
                    </div>

                    <!-- ACTIVE RESERVATIONS PANEL -->
                    <div class="tab-content-panel active" id="tab-active">
                        <?php if (empty($active_bookings)): ?>
                            <div class="empty-dashboard-state">
                                <p style="margin: 0;">No active reservations found.</p>
                                <a href="book.php" class="SignIn-btn" style="width: fit-content; margin: 16px auto 0; font-size: 13px; padding: 8px 16px;">Book a Spot Now</a>
                            </div>
                        <?php else: ?>
                            <div class="booking-history-container">
                                <?php foreach ($active_bookings as $b): ?>
                                    <div class="history-card">
                                        <div class="history-card-header">
                                            <span class="history-receipt-no"><?php echo htmlspecialchars($b['receipt_number']); ?></span>
                                            <span class="history-status-badge status-confirmed">active</span>
                                        </div>
                                        <div class="history-card-body">
                                            <div>
                                                <span>Location</span>
                                                <strong><?php echo htmlspecialchars($b['location_name']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Floor</span>
                                                <strong><?php echo htmlspecialchars($b['floor']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Spot</span>
                                                <strong><?php echo htmlspecialchars($b['spot_label'] . ' (' . ucfirst($b['spot_type']) . ')'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Date</span>
                                                <strong><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></strong>
                                            </div>
                                            <div>
                                                <span>Arrival Time</span>
                                                <strong><?php echo formatTime($b['arrival_time']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Duration</span>
                                                <strong><?php echo $b['duration_hours'] . ($b['duration_hours'] > 1 ? ' hours' : ' hour'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Overnight Parking</span>
                                                <strong><?php echo $b['is_overnight'] ? 'Yes' : 'No'; ?></strong>
                                            </div>
                                            <div>
                                                <span>Vehicle</span>
                                                <strong><?php echo htmlspecialchars(($b['plate_number'] ?? 'Default') . (!empty($b['vehicle_category']) ? ' (' . $b['vehicle_category'] . ')' : '')); ?></strong>
                                            </div>
                                            <div>
                                                <span>Payment Method</span>
                                                <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($b['payment_method']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Total Cost</span>
                                                <strong>₱<?php echo number_format($b['total_amount'], 2); ?></strong>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px;">
                                            <button class="cancel-booking-btn" style="flex: 1; border-color: rgba(255,255,255,0.16); background: rgba(255,255,255,0.02); color: #8d8b8b;" onclick="viewReceiptFromDashboard(<?php echo htmlspecialchars(json_encode($b)); ?>)">View Receipt</button>
                                            <button class="cancel-booking-btn" style="flex: 1;" onclick="cancelBookingFromDashboard(<?php echo $b['id']; ?>)">Cancel Reservation</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- COMPLETED RESERVATIONS PANEL -->
                    <div class="tab-content-panel" id="tab-completed">
                        <?php if (empty($completed_bookings)): ?>
                            <div class="empty-dashboard-state">
                                <p style="margin: 0;">No completed reservations found.</p>
                            </div>
                        <?php else: ?>
                            <div class="booking-history-container">
                                <?php foreach ($completed_bookings as $b): ?>
                                    <div class="history-card">
                                        <div class="history-card-header">
                                            <span class="history-receipt-no"><?php echo htmlspecialchars($b['receipt_number']); ?></span>
                                            <span class="history-status-badge status-confirmed" style="color: #8d8b8b; background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1);">completed</span>
                                        </div>
                                        <div class="history-card-body">
                                            <div>
                                                <span>Location</span>
                                                <strong><?php echo htmlspecialchars($b['location_name']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Floor</span>
                                                <strong><?php echo htmlspecialchars($b['floor']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Spot</span>
                                                <strong><?php echo htmlspecialchars($b['spot_label'] . ' (' . ucfirst($b['spot_type']) . ')'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Date</span>
                                                <strong><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></strong>
                                            </div>
                                            <div>
                                                <span>Arrival Time</span>
                                                <strong><?php echo formatTime($b['arrival_time']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Duration</span>
                                                <strong><?php echo $b['duration_hours'] . ($b['duration_hours'] > 1 ? ' hours' : ' hour'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Overnight Parking</span>
                                                <strong><?php echo $b['is_overnight'] ? 'Yes' : 'No'; ?></strong>
                                            </div>
                                            <div>
                                                <span>Vehicle</span>
                                                <strong><?php echo htmlspecialchars(($b['plate_number'] ?? 'Default') . (!empty($b['vehicle_category']) ? ' (' . $b['vehicle_category'] . ')' : '')); ?></strong>
                                            </div>
                                            <div>
                                                <span>Payment Method</span>
                                                <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($b['payment_method']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Total Cost</span>
                                                <strong>₱<?php echo number_format($b['total_amount'], 2); ?></strong>
                                            </div>
                                        </div>
                                        <button class="cancel-booking-btn" style="border-color: rgba(255,255,255,0.16); background: rgba(255,255,255,0.02); color: #8d8b8b;" onclick="viewReceiptFromDashboard(<?php echo htmlspecialchars(json_encode($b)); ?>)">View Receipt</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- VOID RESERVATIONS PANEL -->
                    <div class="tab-content-panel" id="tab-void">
                        <?php if (empty($void_bookings)): ?>
                            <div class="empty-dashboard-state">
                                <p style="margin: 0;">No voided reservations found.</p>
                            </div>
                        <?php else: ?>
                            <div class="booking-history-container">
                                <?php foreach ($void_bookings as $b): ?>
                                    <div class="history-card">
                                        <div class="history-card-header">
                                            <span class="history-receipt-no"><?php echo htmlspecialchars($b['receipt_number']); ?></span>
                                            <span class="history-status-badge status-void">void</span>
                                        </div>
                                        <div class="history-card-body">
                                            <div>
                                                <span>Location</span>
                                                <strong><?php echo htmlspecialchars($b['location_name']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Floor</span>
                                                <strong><?php echo htmlspecialchars($b['floor']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Spot</span>
                                                <strong><?php echo htmlspecialchars($b['spot_label'] . ' (' . ucfirst($b['spot_type']) . ')'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Date</span>
                                                <strong><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></strong>
                                            </div>
                                            <div>
                                                <span>Arrival Time</span>
                                                <strong><?php echo formatTime($b['arrival_time']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Duration</span>
                                                <strong><?php echo $b['duration_hours'] . ($b['duration_hours'] > 1 ? ' hours' : ' hour'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Overnight Parking</span>
                                                <strong><?php echo $b['is_overnight'] ? 'Yes' : 'No'; ?></strong>
                                            </div>
                                            <div>
                                                <span>Vehicle</span>
                                                <strong><?php echo htmlspecialchars(($b['plate_number'] ?? 'Default') . (!empty($b['vehicle_category']) ? ' (' . $b['vehicle_category'] . ')' : '')); ?></strong>
                                            </div>
                                            <div>
                                                <span>Payment Method</span>
                                                <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($b['payment_method']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Total Cost</span>
                                                <strong>₱<?php echo number_format($b['total_amount'], 2); ?></strong>
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 10px;">
                                            <button class="cancel-booking-btn" style="flex: 1; border-color: rgba(255,255,255,0.16); background: rgba(255,255,255,0.02); color: #8d8b8b;" onclick="viewReceiptFromDashboard(<?php echo htmlspecialchars(json_encode($b)); ?>)">View Receipt</button>
                                            <span style="flex: 1; font-size: 11px; color: #ff9f43; background: rgba(255,159,67,0.05); border: 1px dashed rgba(255,159,67,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 4px; font-weight: 500;">Grace Period Missed (50% Refunded)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- CANCELLED RESERVATIONS PANEL -->
                    <div class="tab-content-panel" id="tab-cancelled">
                        <?php if (empty($cancelled_bookings)): ?>
                            <div class="empty-dashboard-state">
                                <p style="margin: 0;">No cancelled reservations found.</p>
                            </div>
                        <?php else: ?>
                            <div class="booking-history-container">
                                <?php foreach ($cancelled_bookings as $b): ?>
                                    <div class="history-card">
                                        <div class="history-card-header">
                                            <span class="history-receipt-no"><?php echo htmlspecialchars($b['receipt_number']); ?></span>
                                            <span class="history-status-badge status-cancelled">cancelled</span>
                                        </div>
                                        <div class="history-card-body">
                                            <div>
                                                <span>Location</span>
                                                <strong><?php echo htmlspecialchars($b['location_name']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Floor</span>
                                                <strong><?php echo htmlspecialchars($b['floor']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Spot</span>
                                                <strong><?php echo htmlspecialchars($b['spot_label'] . ' (' . ucfirst($b['spot_type']) . ')'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Date</span>
                                                <strong><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></strong>
                                            </div>
                                            <div>
                                                <span>Arrival Time</span>
                                                <strong><?php echo formatTime($b['arrival_time']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Duration</span>
                                                <strong><?php echo $b['duration_hours'] . ($b['duration_hours'] > 1 ? ' hours' : ' hour'); ?></strong>
                                            </div>
                                            <div>
                                                <span>Overnight Parking</span>
                                                <strong><?php echo $b['is_overnight'] ? 'Yes' : 'No'; ?></strong>
                                            </div>
                                            <div>
                                                <span>Vehicle</span>
                                                <strong><?php echo htmlspecialchars(($b['plate_number'] ?? 'Default') . (!empty($b['vehicle_category']) ? ' (' . $b['vehicle_category'] . ')' : '')); ?></strong>
                                            </div>
                                            <div>
                                                <span>Payment Method</span>
                                                <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($b['payment_method']); ?></strong>
                                            </div>
                                            <div>
                                                <span>Total Cost</span>
                                                <strong>₱<?php echo number_format($b['total_amount'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </section>

    <!-- Modal for viewing receipt -->
    <div class="receipt-modal" id="dashboard-receipt-modal" style="display: none;">
        <div class="receipt-modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px;">
                <h3 style="color: white; margin: 0; font-size: 16px;">Parking Ticket / Receipt</h3>
                <button onclick="closeReceiptModal()" style="background: transparent; border: none; color: #8d8b8b; font-size: 22px; cursor: pointer;">&times;</button>
            </div>
            <div style="background: white; padding: 12px; border-radius: 8px; width: fit-content; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <img id="dashboard-qr-img" src="" alt="Scanable QR Code" style="width: 180px; height: 180px; display: block; object-fit: contain;">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #8d8b8b;">
                <div style="display: flex; justify-content: space-between;"><span>Ticket Number:</span><strong id="m-receipt" style="color: #00d4a8; font-family: monospace;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Location:</span><strong id="m-location" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Floor & Spot:</span><strong id="m-spot" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Date:</span><strong id="m-date" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Time:</span><strong id="m-time" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Duration:</span><strong id="m-duration" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Vehicle Plate:</span><strong id="m-plate" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Vehicle Type:</span><strong id="m-vehicle-category" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Overnight Parking:</span><strong id="m-overnight" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Payment Method:</span><strong id="m-payment" style="color: white; text-transform: uppercase;">--</strong></div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 8px; margin-top: 4px; font-size: 14px;"><span style="color: #00d4a8;">Total Price:</span><strong id="m-total" style="color: #00d4a8; font-weight: bold;">--</strong></div>
            </div>
            
            <button onclick="downloadReceiptFromDashboard()" class="download-receipt-btn" style="margin: 0; width: 100%;">Download Text Ticket</button>
        </div>
    </div>

    <!-- Modal for managing multiple vehicles -->
    <div class="receipt-modal" id="vehicles-modal" style="display: none;">
        <div class="receipt-modal-card" style="max-width: 440px; width: 90%; display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
                <h3 style="color: white; margin: 0; font-size: 16px;">Manage Registered Vehicles</h3>
                <button onclick="closeVehiclesModal()" style="background: transparent; border: none; color: #8d8b8b; font-size: 22px; cursor: pointer;">&times;</button>
            </div>
            
            <div id="modal-vehicle-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto; padding-right: 4px; box-sizing: border-box; width: 100%;">
                <!-- Dynamically loaded via AJAX -->
            </div>
            
            <form id="modal-vehicle-form" style="display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 16px; box-sizing: border-box; width: 100%;">
                <input type="hidden" id="edit-vehicle-id" value="">
                <label id="form-action-title" style="font-size: 11px; text-transform: uppercase; color: #8d8b8b; font-weight: bold; margin-bottom: 4px;">Add New Vehicle</label>
                
                <input type="text" id="modal-v-plate" placeholder="Plate (e.g. ABC 1234)" style="box-sizing: border-box; width: 100%; background: rgba(8,8,8,0.5); border: 1px solid rgba(255,255,255,0.12); padding: 8px; border-radius: 6px; color: white; font-size: 12px;" required maxlength="10">
                
                <select id="modal-v-category" style="box-sizing: border-box; width: 100%; background: rgba(8,8,8,0.5); border: 1px solid rgba(255,255,255,0.12); padding: 8px; border-radius: 6px; color: white; font-size: 12px; cursor: pointer;" required>
                    <optgroup label="Motorcycles">
                        <option value="Motorcycle (below 150cc)">Motorcycle (below 150cc)</option>
                        <option value="Bigbike (Above 400cc)">Bigbike (Above 400cc)</option>
                    </optgroup>
                    <optgroup label="Four Wheels">
                        <option value="4wheels (Sedan)">4wheels (Sedan)</option>
                        <option value="4wheels (SUV)">4wheels (SUV)</option>
                        <option value="4wheels (Pickup)">4wheels (Pickup)</option>
                        <option value="4wheels (Mid-size SUV)">4wheels (Mid-size SUV)</option>
                    </optgroup>
                </select>
                
                <div style="display: flex; gap: 8px; width: 100%; box-sizing: border-box;">
                    <button type="submit" id="vehicle-form-submit-btn" style="flex: 2; background: #00d4a8; border: none; color: black; font-weight: bold; padding: 8px; border-radius: 6px; cursor: pointer; font-size: 12px;">+ Add Vehicle</button>
                    <button type="button" id="cancel-edit-btn" style="display: none; flex: 1; background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b; padding: 8px; border-radius: 6px; cursor: pointer; font-size: 12px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchDashboardTab(event, tabId) {
            // Hide all panels
            const panels = document.querySelectorAll('.tab-content-panel');
            panels.forEach(p => p.classList.remove('active'));
            
            // Deactivate all tab buttons
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(t => t.classList.remove('active'));
            
            // Show selected panel & activate button
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        let activeReceiptData = null;

        function viewReceiptFromDashboard(booking) {
            activeReceiptData = booking;
            
            // Format time
            const formattedTime = formatJsTime(booking.arrival_time);
            
            document.getElementById('m-receipt').textContent = booking.receipt_number;
            document.getElementById('m-location').textContent = booking.location_name;
            document.getElementById('m-spot').textContent = booking.floor + ' - ' + booking.spot_label;
            document.getElementById('m-date').textContent = new Date(booking.booking_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
            document.getElementById('m-time').textContent = formattedTime;
            document.getElementById('m-duration').textContent = booking.duration_hours + (booking.duration_hours > 1 ? ' hours' : ' hour');
            document.getElementById('m-plate').textContent = booking.plate_number || 'Default';
            document.getElementById('m-vehicle-category').textContent = booking.vehicle_category || '--';
            document.getElementById('m-overnight').textContent = Number(booking.is_overnight) === 1 ? 'Yes' : 'No';
            document.getElementById('m-payment').textContent = booking.payment_method;
            document.getElementById('m-total').textContent = 'PHP ' + Number(booking.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Fetch dynamic QR code
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent('SiksikParkingTicket_' + booking.receipt_number);
            document.getElementById('dashboard-qr-img').src = qrUrl;

            document.getElementById('dashboard-receipt-modal').style.display = 'flex';
        }

        function closeReceiptModal() {
            document.getElementById('dashboard-receipt-modal').style.display = 'none';
            activeReceiptData = null;
        }

        function formatJsTime(timeStr) {
            const parts = timeStr.split(':');
            let hours = Number(parts[0]);
            const minutes = parts[1] || '00';
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            return hours + ':' + minutes + ' ' + ampm;
        }

        function downloadReceiptFromDashboard() {
            if (!activeReceiptData) return;
            
            const b = activeReceiptData;
            const textContent = [
                'Siksik Parking System Receipt',
                'Ticket No.: ' + b.receipt_number,
                'Date: ' + b.booking_date,
                '',
                'Location: ' + b.location_name,
                'Floor & Spot: ' + b.floor + ' - ' + b.spot_label,
                'Arrival: ' + formatJsTime(b.arrival_time),
                'Duration: ' + b.duration_hours + ' hours',
                'Vehicle Plate: ' + (b.plate_number || 'Default'),
                'Vehicle Type: ' + (b.vehicle_category || '--'),
                'Overnight Parking: ' + (Number(b.is_overnight) === 1 ? 'Yes' : 'No'),
                'Payment Method: ' + b.payment_method.toUpperCase(),
                'Total Price: PHP ' + Number(b.total_amount).toLocaleString('en-US'),
                '',
                'Thank you for booking with Siksik.'
            ].join('\n');

            const blob = new Blob([textContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = b.receipt_number + '.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        async function cancelBookingFromDashboard(bookingId) {
            if (!confirm('Are you sure you want to cancel this parking reservation?')) return;
            
            try {
                const response = await fetch('book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=cancel_booking&booking_id=${bookingId}`
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Cancellation failed: ' + result.error);
                    }
                } else {
                    alert('Cancellation failed.');
                }
            } catch (e) {
                console.error("Cancellation error:", e);
                alert('An error occurred during cancellation.');
            }
        }

        // --- Vehicle Management Scripts ---
        const manageVehiclesBtn = document.getElementById('manage-vehicles-btn');
        const vehiclesModal = document.getElementById('vehicles-modal');
        const modalVehicleList = document.getElementById('modal-vehicle-list');
        const modalVehicleForm = document.getElementById('modal-vehicle-form');
        const editVehicleIdInput = document.getElementById('edit-vehicle-id');
        const formActionTitle = document.getElementById('form-action-title');
        const modalVPlateInput = document.getElementById('modal-v-plate');
        const modalVCategorySelect = document.getElementById('modal-v-category');
        const submitBtn = document.getElementById('vehicle-form-submit-btn');
        const cancelEditBtn = document.getElementById('cancel-edit-btn');

        if (manageVehiclesBtn) {
            manageVehiclesBtn.addEventListener('click', function() {
                if (vehiclesModal) {
                    vehiclesModal.style.display = 'flex';
                    resetVehicleForm();
                    loadVehiclesList();
                }
            });
        }

        function closeVehiclesModal() {
            if (vehiclesModal) {
                vehiclesModal.style.display = 'none';
            }
        }

        function resetVehicleForm() {
            if (editVehicleIdInput) editVehicleIdInput.value = '';
            if (modalVPlateInput) modalVPlateInput.value = '';
            if (modalVCategorySelect) modalVCategorySelect.selectedIndex = 0;
            if (formActionTitle) formActionTitle.textContent = 'Add New Vehicle';
            if (submitBtn) submitBtn.textContent = '+ Add Vehicle';
            if (cancelEditBtn) cancelEditBtn.style.display = 'none';
        }

        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', resetVehicleForm);
        }

        async function loadVehiclesList() {
            if (!modalVehicleList) return;
            modalVehicleList.innerHTML = '<div style="color: #8d8b8b; font-size: 12px; text-align: center; padding: 10px;">Loading...</div>';
            
            try {
                const fd = new FormData();
                fd.append('action', 'get_vehicles');
                const response = await fetch('dashboard.php', { method: 'POST', body: fd });
                if (response.ok) {
                    const data = await response.json();
                    renderVehicles(data.vehicles || []);
                    updateSidebarPlates(data.vehicles || []);
                }
            } catch(e) {
                console.error("Error loading vehicles:", e);
                modalVehicleList.innerHTML = '<div style="color: #ff4d4d; font-size: 11px; text-align: center;">Error loading vehicles.</div>';
            }
        }

        function updateSidebarPlates(vehicles) {
            const sidebarList = document.getElementById('sidebar-plates-list');
            if (!sidebarList) return;
            
            const defaultPlate = "<?php echo htmlspecialchars($user['plate_number']); ?>";
            let html = `<strong style="font-family: monospace; font-size: 13px; color: white;">${defaultPlate} <span style="font-size: 9px; color: #00d4a8; font-weight: normal; border: 1px solid rgba(0,212,168,0.3); padding: 1px 4px; border-radius: 4px; margin-left: 2px;">DEFAULT</span></strong>`;
            
            vehicles.forEach(v => {
                html += `<strong style="font-family: monospace; font-size: 13px; color: white;">${v.plate_number} <span style="font-size: 9px; color: #8d8b8b; font-weight: normal; text-transform: uppercase;">(${v.category})</span></strong>`;
            });
            sidebarList.innerHTML = html;
        }

        function renderVehicles(vehicles) {
            if (!modalVehicleList) return;
            if (vehicles.length === 0) {
                modalVehicleList.innerHTML = '<div style="color: #555; font-size: 11px; text-align: center; padding: 10px; border: 1px dashed rgba(255,255,255,0.06); border-radius: 6px;">No vehicles added yet.</div>';
                return;
            }

            modalVehicleList.innerHTML = vehicles.map(v => `
                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 8px; border-radius: 6px; box-sizing: border-box; width: 100%;">
                    <div style="display: flex; flex-direction: column;">
                        <span style="color: white; font-weight: bold; font-size: 12px; font-family: monospace; letter-spacing: 0.5px;">${v.plate_number}</span>
                        <span style="color: #8d8b8b; font-size: 10px; text-transform: uppercase;">${v.category}</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="startEditVehicle(${v.id}, '${v.plate_number}', '${v.category}')" style="background: transparent; border: none; color: #00d4a8; font-weight: bold; font-size: 11px; cursor: pointer; padding: 2px 4px;">Edit</button>
                        <button onclick="deleteVehicle(${v.id})" style="background: transparent; border: none; color: #ff4d4d; font-weight: bold; font-size: 14px; cursor: pointer; padding: 0 4px;">&times;</button>
                    </div>
                </div>
            `).join('');
        }

        function startEditVehicle(id, plate, category) {
            if (editVehicleIdInput) editVehicleIdInput.value = id;
            if (modalVPlateInput) modalVPlateInput.value = plate;
            if (modalVCategorySelect) modalVCategorySelect.value = category;
            if (formActionTitle) formActionTitle.textContent = 'Edit Vehicle Details';
            if (submitBtn) submitBtn.textContent = 'Update Vehicle';
            if (cancelEditBtn) cancelEditBtn.style.display = 'block';
        }

        if (modalVehicleForm) {
            modalVehicleForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const editId = editVehicleIdInput.value;
                const plate = modalVPlateInput.value;
                const category = modalVCategorySelect.value;
                
                const originalSubmitText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                
                try {
                    const fd = new FormData();
                    if (editId) {
                        fd.append('action', 'edit_vehicle');
                        fd.append('id', editId);
                    } else {
                        fd.append('action', 'add_vehicle');
                    }
                    fd.append('plate', plate);
                    fd.append('category', category);
                    
                    const response = await fetch('dashboard.php', { method: 'POST', body: fd });
                    if (response.ok) {
                        const result = await response.json();
                        if (result.success) {
                            resetVehicleForm();
                            loadVehiclesList();
                        } else {
                            alert('Error: ' + result.error);
                        }
                    }
                } catch(err) {
                    console.error("Error saving vehicle:", err);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalSubmitText;
                }
            });
        }

        async function deleteVehicle(id) {
            if (!confirm('Are you sure you want to remove this vehicle?')) return;
            
            try {
                const fd = new FormData();
                fd.append('action', 'delete_vehicle');
                fd.append('id', id);
                const response = await fetch('dashboard.php', { method: 'POST', body: fd });
                if (response.ok) {
                    resetVehicleForm();
                    loadVehiclesList();
                }
            } catch(e) {
                console.error("Error deleting vehicle:", e);
            }
        }
    </script>
</body>
</html>
