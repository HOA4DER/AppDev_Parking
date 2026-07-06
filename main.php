<?php
session_start();
require_once 'db.php';

if (!empty($_SESSION['is_signed_in']) && !isset($_POST['action'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sign_in') {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['is_signed_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['phone_number'] = $user['phone_number'];
                $_SESSION['plate_number'] = $user['plate_number'];

                echo json_encode(['success' => true, 'message' => 'Login successful! Redirecting...']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'sign_up') {
        header('Content-Type: application/json');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $plate_number = trim($_POST['plate_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($first_name) || empty($last_name) || empty($phone_number) || empty($plate_number) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }

        if (!preg_match('/^[0-9]{11}$/', $phone_number)) {
            echo json_encode(['success' => false, 'message' => 'Phone number must be 11 digits (e.g. 09XXXXXXXXX).']);
            exit;
        }

        if (strlen($plate_number) < 4) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid plate number.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, phone_number, plate_number, email, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $phone_number, $plate_number, $email, $hashedPassword]);

            $userId = $pdo->lastInsertId();
            $_SESSION['is_signed_in'] = true;
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['phone_number'] = $phone_number;
            $_SESSION['plate_number'] = $plate_number;

            echo json_encode(['success' => true, 'message' => 'Account created successfully! Redirecting...']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Parking System</title>
    <script src="script.js" defer></script>
</head>
<body>
    <section class="auth-page-section">
        <div class="main-page-header-container auth-page-header">
            <div class="logo-system-name-container">
                <img src="images/logo.svg" class="logo-icon" alt="Siksik logo">
                <span class="system-name">Siksik</span>
            </div>

            <div class="nav-bar-container">
                <a class="nav-bar-btn" href="index.php#home">Home</a>
                <a class="nav-bar-btn" href="dashboard.php">Dashboard</a>
                <a class="nav-bar-btn" href="index.php#how-it-works">Book Parking</a>
                <a class="nav-bar-btn" href="index.php#pricing">Pricing</a>
                <a class="nav-bar-btn" href="index.php#about-us">About Us</a>
                <a class="nav-bar-btn" href="index.php#contact">Contact</a>
            </div>

            <div class="sign-in-container">
                <a class="SignIn-btn" href="index.php">
                    Home
                    <img src="images/arrow-right.svg" class="side-icon" alt="">
                </a>
            </div>
        </div>

    <section class="body-section auth-body-section">
        <div class="bg-left-body-wrapper">
            <div class="left-body-wrapper">
                <div class="landing-page-features-header">
                    <div class="landing-page-tag-line-container">
                        <span class="landing-page-tag-line-container-1">Smart Parking</span>
                        <span class="landing-page-tag-line-container-2">for the city</span>
                        <span class="landing-page-tag-line-container-1">that never stops.</span>
                    </div>

                    <div class="landing-page-subheader-tagline-container">
                        <span class="landing-page-subheader-tagline">Reserve spots in seconds, track availability in real <br> time, and never circle the block again.</span>
                    </div>

                    <div class="features-wrapper">
                        <div class="features-container">
                            <img src="images/offer-reserve.svg" class="feature-icon" alt="">
                            <span class="feauture-text">Reserve any spot in under 60 seconds</span>
                        </div>

                        <div class="features-container">
                            <img src="images/offer-security.svg" class="feature-icon" alt="">
                            <span class="feauture-text">Verified, monitored parking locations</span>
                        </div>

                        <div class="features-container">
                            <img src="images/offer-availability.svg" class="feature-icon" alt="">
                            <span class="feauture-text">Real-time availability — no surprises</span>
                        </div>

                        <div class="features-container">
                            <img src="images/offer-pricing.svg" class="feature-icon" alt="">
                            <span class="feauture-text">Competitive pricing starting at ₱60/hr</span>
                        </div>
                    </div>

                    <div class="learn-more-container">
                        <a class="learn-more-btn" href="index.php#about-us">
                            <img src="images/logo.svg" class="learn-more-icon" alt="">
                            <span class="learn-more-text">LEARN MORE ABOUT US</span>
                            <img src="images/arrow-right.svg" class="drop-down-icon" alt="">
                        </a>
                    </div>
                </div> 

                <div class="facts-wrapper">
                    <div class="facts-container">
                        <span class="facts-header">2400+</span>
                        <span class="facts-subheader">ACTIVE SPOTS</span>
                    </div>

                    <div class="facts-container">
                        <span class="facts-header">18 mins</span>
                        <span class="facts-subheader">AVG. SAVE TIME</span>
                    </div>

                    <div class="facts-container">
                        <span class="facts-header">99.9%</span>
                        <span class="facts-subheader">UPTIME</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-body-wrapper">
            <div class="right-body-bg">
                <div class="header-subheader-login-container">
                    <span class="header-login-text">Welcome Back</span>
                    <span class="subheader-login-text">Sign in to manage your parking</span>
                </div>

                <div class="login-container">
                    <form class="form-container" method="post">
                        <div class="email-pass-container">
                            <span class="login-email-text">EMAIL</span>
                            <input class="email-input-bar" type="email" name="email" placeholder="you@example.com">

                            <span class="login-email-text">PASSWORD</span>
                            <input class="email-input-bar" type="password" name="password" placeholder="••••••••••••••••••">
                        </div>

                        <div class="signIn-container">
                            <input class="signIn-btn" type="submit" name="sign_in" value="Sign In">
                        </div>
                    </form>

                    <div class="no-account-container">
                        <span class="no-account-text">No account yet?</span>
                        <button class="create-acc-btn" onclick="createAcc()">Create One</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </section>
</body>
</html>
