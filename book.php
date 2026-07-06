<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['is_signed_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your session has expired. Please sign in again.']);
        exit;
    }
    header('Location: main.php');
    exit;
}

date_default_timezone_set('Asia/Manila');

// 1. Handle API Action to Get Occupied Spots
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_occupied_spots') {
    header('Content-Type: application/json');
    $location_id = $_GET['location_id'] ?? '';
    $floor = $_GET['floor'] ?? '';
    $arrival_time = $_GET['arrival_time'] ?? '';
    $duration = intval($_GET['duration'] ?? 2);

    if (empty($location_id) || empty($floor) || empty($arrival_time)) {
        echo json_encode(['occupied_spots' => []]);
        exit;
    }

    $query_start = $arrival_time;
    $query_end_ts = strtotime($arrival_time) + ($duration * 3600);
    $query_end = date('H:i:s', $query_end_ts);

    try {
        $stmt = $pdo->prepare("
            SELECT spot_label 
            FROM bookings 
            WHERE location_id = ? 
              AND floor = ? 
              AND status = 'confirmed'
              AND arrival_time < ? 
              AND ADDTIME(arrival_time, SEC_TO_TIME(duration_hours * 3600)) > ?
        ");
        $stmt->execute([$location_id, $floor, $query_end, $query_start]);
        $occupied = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['occupied_spots' => $occupied]);
    } catch (Exception $e) {
        echo json_encode(['occupied_spots' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// 2. Handle API Action to Confirm Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_booking') {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['error' => 'User not logged in.']);
        exit;
    }

    $location_id = $_POST['location_id'] ?? '';
    $location_name = $_POST['location_name'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $spot_label = $_POST['spot_label'] ?? '';
    $spot_type = $_POST['spot_type'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $duration = intval($_POST['duration'] ?? 2);
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'gcash';
    $is_overnight = isset($_POST['is_overnight']) && ($_POST['is_overnight'] === 'true' || $_POST['is_overnight'] === '1' || $_POST['is_overnight'] === 1);

    if (empty($location_id) || empty($floor) || empty($spot_label) || empty($arrival_time)) {
        echo json_encode(['error' => 'Missing booking details.']);
        exit;
    }

    // Backend price verification
    if ($is_overnight) {
        $parts = explode(':', $arrival_time);
        $hours = intval($parts[0] ?? 0);
        $isPM = $hours >= 12;
        $basePrice = $isPM ? 120 : 145;

        $current_date = date('Y-m-d');
        $arrival_ts = strtotime($current_date . ' ' . $arrival_time);
        $departure_ts = $arrival_ts + ($duration * 3600);

        // 7:00 AM next morning
        $next_7am_ts = strtotime($current_date . ' 07:00:00') + 86400; // +1 day

        $extra_cost = 0;
        if ($departure_ts > $next_7am_ts) {
            $diff_seconds = $departure_ts - $next_7am_ts;
            $extra_hours = ceil($diff_seconds / 3600);
            $extra_cost = $extra_hours * 20;
        }

        $total_amount = $basePrice + $extra_cost;
    } else {
        if ($duration <= 4) {
            $total_amount = 50;
        } else {
            $total_amount = 50 + ($duration - 4) * 15;
        }
    }

    $query_start = $arrival_time;
    $query_end_ts = strtotime($arrival_time) + ($duration * 3600);
    $query_end = date('H:i:s', $query_end_ts);

    try {
        // Double booking prevention check
        $stmt = $pdo->prepare("
            SELECT id 
            FROM bookings 
            WHERE location_id = ? 
              AND floor = ? 
              AND spot_label = ?
              AND status = 'confirmed'
              AND arrival_time < ? 
              AND ADDTIME(arrival_time, SEC_TO_TIME(duration_hours * 3600)) > ?
        ");
        $stmt->execute([$location_id, $floor, $spot_label, $query_end, $query_start]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'This spot has just been reserved by someone else. Please choose another spot.']);
            exit;
        }

        // Generate receipt
        try {
            $randomCode = strtoupper(bin2hex(random_bytes(3)));
        } catch (Exception $exception) {
            $randomCode = strtoupper(substr(uniqid('', true), -6));
        }
        $receipt_number = 'SK-' . date('Ymd') . '-' . $randomCode;
        $issued_at = date('M d, Y h:i A');

        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, booking_date, receipt_number, location_id, location_name, floor, spot_label, spot_type, arrival_time, duration_hours, hourly_rate, payment_method, total_amount, is_overnight) 
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $receipt_number,
            $location_id,
            $location_name,
            $floor,
            $spot_label,
            $spot_type,
            $arrival_time,
            $duration,
            $hourly_rate,
            $payment_method,
            $total_amount,
            $is_overnight ? 1 : 0
        ]);

        echo json_encode([
            'receipt_number' => $receipt_number,
            'issued_at' => $issued_at,
            'payment_method' => $payment_method
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. Handle AJAX Action to Cancel Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_booking') {
    header('Content-Type: application/json');
    $user_id = $_SESSION['user_id'] ?? null;
    $booking_id = intval($_POST['booking_id'] ?? 0);

    if (!$user_id || !$booking_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'confirmed'");
        $stmt->execute([$booking_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Booking successfully cancelled.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Booking not found or already cancelled.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Book Parking | Siksik</title>
    <script src="script.js" defer></script>
</head>
<body>
    <section class="booking-page-section">
        <div class="main-page-header-container booking-page-header">
            <div class="logo-system-name-container">
                <img src="images/logo.svg" class="logo-icon" alt="Siksik logo">
                <span class="system-name">Siksik</span>
            </div>

            <div class="nav-bar-container">
                <a class="nav-bar-btn" href="index.php#home">Home</a>
                <a class="nav-bar-btn active-nav-btn" href="book.php">Book Parking</a>
                <a class="nav-bar-btn" href="index.php#pricing">Pricing</a>
                <a class="nav-bar-btn" href="index.php#about-us">About Us</a>
                <a class="nav-bar-btn" href="index.php#contact">Contact</a>
            </div>

            <div class="sign-in-container" style="gap: 12px;">
                <a class="SignIn-btn" href="dashboard.php" style="background: transparent; border: 1px solid rgba(255,255,255,0.16); color: #8d8b8b;">
                    Dashboard
                </a>
                <a class="SignIn-btn" href="index.php">
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
                    <span class="booking-kicker">BOOKING FLOW</span>
                    <h1 class="booking-title">Choose your parking spot</h1>
                </div>
                <div class="booking-live-pill">
                    <div class="pulse-green"></div>
                    <span>Live spots updated now</span>
                </div>
            </div>

            <div class="booking-stepper" aria-label="Booking steps">
                <button class="booking-step is-active" type="button" data-step-nav="1">1. Location</button>
                <button class="booking-step" type="button" data-step-nav="2">2. Area</button>
                <button class="booking-step" type="button" data-step-nav="3">3. Spot</button>
            </div>

            <div class="booking-layout">
                <section class="booking-panel booking-location-panel" data-booking-step="1">
                    <div class="booking-panel-header">
                        <div>
                            <span class="booking-panel-label">Step 1</span>
                            <h2 class="booking-panel-title">Choose Mall Parking</h2>
                        </div>
                    </div>

                    <div class="location-list booking-location-list" aria-label="Philippine mall parking options">
                        <article class="location-card" data-location-card="sm-moa">
                            <div class="location-card-top">
                                <h3>SM Mall of Asia Parking</h3>
                                <span class="verified-badge">4.8 verified</span>
                            </div>
                            <div class="location-card-meta">
                                <span>Pasay City</span>
                                <span>PHP 60-100/hr</span>
                                <span>58 spots</span>
                            </div>
                            <button class="view-spots-btn" type="button" data-view-spots="sm-moa">View Floors</button>
                        </article>

                        <article class="location-card" data-location-card="sm-megamall">
                            <div class="location-card-top">
                                <h3>SM Megamall Parking</h3>
                                <span class="verified-badge">4.9 verified</span>
                            </div>
                            <div class="location-card-meta">
                                <span>Mandaluyong City</span>
                                <span>PHP 70-110/hr</span>
                                <span>46 spots</span>
                            </div>
                            <button class="view-spots-btn" type="button" data-view-spots="sm-megamall">View Floors</button>
                        </article>

                        <article class="location-card" data-location-card="ayala-manila-bay">
                            <div class="location-card-top">
                                <h3>Ayala Malls Manila Bay Parking</h3>
                                <span class="verified-badge">4.7 verified</span>
                            </div>
                            <div class="location-card-meta">
                                <span>Paranaque City</span>
                                <span>PHP 60-90/hr</span>
                                <span>39 spots</span>
                            </div>
                            <button class="view-spots-btn" type="button" data-view-spots="ayala-manila-bay">View Floors</button>
                        </article>

                        <article class="location-card" data-location-card="glorietta">
                            <div class="location-card-top">
                                <h3>Glorietta Parking</h3>
                                <span class="verified-badge">4.6 verified</span>
                            </div>
                            <div class="location-card-meta">
                                <span>Makati City</span>
                                <span>PHP 80-120/hr</span>
                                <span>34 spots</span>
                            </div>
                            <button class="view-spots-btn" type="button" data-view-spots="glorietta">View Floors</button>
                        </article>

                        <article class="location-card" data-location-card="trinoma">
                            <div class="location-card-top">
                                <h3>TriNoma Parking</h3>
                                <span class="verified-badge">4.8 verified</span>
                            </div>
                            <div class="location-card-meta">
                                <span>Quezon City</span>
                                <span>PHP 50-90/hr</span>
                                <span>41 spots</span>
                            </div>
                            <button class="view-spots-btn" type="button" data-view-spots="trinoma">View Floors</button>
                        </article>
                    </div>
                </section>

                <section class="booking-panel booking-detail-panel" data-booking-step="2">
                    <div class="booking-panel-header">
                        <div>
                            <span class="booking-panel-label">Step 2</span>
                            <h2 class="booking-panel-title">Choose Parking Area / Floor</h2>
                        </div>
                        <span class="selected-location-name">Select a location first</span>
                    </div>

                    <div class="selected-location-card">
                        <span class="selected-location-distance">Nearby parking details will appear here.</span>
                        <span class="selected-location-meta">Pick a mall parking option from Step 1.</span>
                    </div>

                    <div class="floor-tabs" aria-label="Parking floors"></div>
                </section>

                <section class="booking-panel booking-spots-panel" data-booking-step="3">
                    <div class="booking-panel-header">
                        <div>
                            <span class="booking-panel-label">Step 3</span>
                            <h2 class="booking-panel-title">Choose Parking Spot</h2>
                        </div>
                        <span class="selected-floor-name">Basement 1</span>
                    </div>

                    <div class="parking-grid" aria-label="Parking spot layout"></div>

                    <div class="parking-legend">
                        <span><i class="legend-box legend-available"></i>Available</span>
                        <span><i class="legend-box legend-occupied"></i>Occupied</span>
                        <span><i class="legend-box legend-reserved"></i>Reserved</span>
                        <span><i class="legend-box legend-selected"></i>Selected</span>
                        <span><i class="legend-box legend-ev"></i>EV Charging</span>
                        <span><i class="legend-box legend-accessible"></i>Accessible</span>
                    </div>
                </section>

                <aside class="booking-summary-panel" data-booking-step="3" data-receipt-endpoint="book.php">
                    <div class="booking-panel-header">
                        <div>
                            <span class="booking-panel-label">Summary</span>
                            <h2 class="booking-panel-title">Booking Summary</h2>
                        </div>
                    </div>

                    <div class="summary-list">
                        <div><span>Location</span><strong data-summary="location">Not selected</strong></div>
                        <div><span>Floor</span><strong data-summary="floor">Not selected</strong></div>
                        <div><span>Spot</span><strong data-summary="spot">Not selected</strong></div>
                        <div><span>Hourly Rate</span><strong data-summary="rate">--</strong></div>
                        <div><span>Arrival Time</span><input class="summary-input" type="time" value="09:00" data-arrival-time></div>
                        <div><span>Duration</span><select class="summary-input" data-duration>
                            <option value="1">1 hour</option>
                            <option value="2" selected>2 hours</option>
                            <option value="3">3 hours</option>
                            <option value="4">4 hours</option>
                            <option value="5">5 hours</option>
                            <option value="6">6 hours</option>
                            <option value="7">7 hours</option>
                            <option value="8">8 hours</option>
                            <option value="10">10 hours</option>
                            <option value="12">12 hours</option>
                            <option value="16">16 hours</option>
                            <option value="20">20 hours</option>
                            <option value="24">24 hours</option>
                        </select></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px dashed rgba(255, 255, 255, 0.08); padding-top: 8px; margin-top: 4px;">
                            <label for="overnight-chk" style="color: #8d8b8b; font-size: 13px; cursor: pointer; user-select: none;">Overnight Parking</label>
                            <input type="checkbox" id="overnight-chk" data-overnight style="accent-color: #00d4a8; cursor: pointer; width: 18px; height: 18px; margin: 0;">
                        </div>
                        <div class="summary-total"><span>Estimated Total</span><strong data-summary="total">--</strong></div>
                    </div>

                    <button class="confirm-booking-btn" type="button" disabled>Confirm Booking</button>
                    <span class="booking-confirmation" aria-live="polite"></span>

                    <div class="booking-receipt-panel" data-receipt-panel hidden>
                        <div class="thank-you-animation" aria-hidden="true">
                            <span class="thank-you-ring"></span>
                            <span class="thank-you-check"></span>
                        </div>
                        <div class="receipt-heading">
                            <span class="booking-panel-label">Thank you</span>
                            <h3>Your parking spot is booked</h3>
                        </div>

                        <div class="receipt-card">
                            <div class="receipt-card-top">
                                <span>Receipt No.</span>
                                <strong data-receipt-field="number">--</strong>
                            </div>
                            <div class="receipt-lines">
                                <div><span>Issued</span><strong data-receipt-field="issued">--</strong></div>
                                <div><span>Location</span><strong data-receipt-field="location">--</strong></div>
                                <div><span>Floor</span><strong data-receipt-field="floor">--</strong></div>
                                <div><span>Spot</span><strong data-receipt-field="spot">--</strong></div>
                                <div><span>Arrival</span><strong data-receipt-field="arrival">--</strong></div>
                                <div><span>Duration</span><strong data-receipt-field="duration">--</strong></div>
                                <div><span>Overnight Parking</span><strong data-receipt-field="overnight">--</strong></div>
                                <div><span>Payment Method</span><strong data-receipt-field="payment" style="text-transform: uppercase;">--</strong></div>
                                <div><span>Total</span><strong data-receipt-field="total">--</strong></div>
                            </div>
                        </div>

                        <div style="background: white; padding: 10px; border-radius: 8px; width: fit-content; margin: 16px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; justify-content: center;">
                            <img id="receipt-qr-img" src="" alt="Ticket QR" style="width: 140px; height: 140px; display: block; object-fit: contain;">
                        </div>

                        <button class="download-receipt-btn" type="button" data-download-receipt>Download Receipt</button>
                    </div>
                </aside>
            </div>

            <!-- Booking History Section -->
            <div class="booking-history-wrapper" style="margin-top: 48px;">
                <div class="booking-panel-header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span class="booking-panel-label">DASHBOARD</span>
                        <h2 class="booking-panel-title">My Parking Reservations</h2>
                    </div>
                    <div class="booking-live-pill">
                        <div class="pulse-green"></div>
                        <span>Updated in real-time</span>
                    </div>
                </div>
                <div class="booking-history-container" id="booking-history-list">
                    <!-- History cards will be loaded here via AJAX -->
                </div>
            </div>
        </main>

        <!-- Checkout / Payment Modal -->
        <div class="checkout-modal-backdrop" id="checkout-modal" style="display: none;">
            <div class="checkout-modal-card">
                <div class="checkout-modal-header">
                    <h2 class="booking-panel-title" style="margin: 0; font-size: 20px;">Secure Checkout</h2>
                    <button class="checkout-close-btn" id="close-checkout-btn" style="background: transparent; border: none; color: #8d8b8b; font-size: 24px; cursor: pointer;">&times;</button>
                </div>
                
                <div class="checkout-modal-body" style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; padding: 20px 0;">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div class="checkout-summary-box" style="border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 16px; background: rgba(8, 8, 8, 0.48);">
                            <h3 style="color: #00d4a8; font-size: 14px; margin: 0 0 12px 0; text-transform: uppercase; font-family: monospace;">Reservation Details</h3>
                            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #8d8b8b;">
                                <div style="display: flex; justify-content: space-between;"><span>Location:</span><strong id="pay-location" style="color: white;">--</strong></div>
                                <div style="display: flex; justify-content: space-between;"><span>Floor & Spot:</span><strong id="pay-spot" style="color: white;">--</strong></div>
                                <div style="display: flex; justify-content: space-between;"><span>Arrival Time:</span><strong id="pay-arrival" style="color: white;">--</strong></div>
                                <div style="display: flex; justify-content: space-between;"><span>Duration:</span><strong id="pay-duration" style="color: white;">--</strong></div>
                                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 8px; margin-top: 4px; font-size: 14px;"><span style="color: #00d4a8;">Total Price:</span><strong id="pay-total" style="color: #00d4a8; font-weight: bold;">--</strong></div>
                            </div>
                        </div>

                        <div class="checkout-payment-methods">
                            <h3 style="color: white; font-size: 13px; margin: 0 0 10px 0; font-family: sans-serif;">Select Payment Method</h3>
                            <div class="payment-options-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                <label class="payment-option-label active" style="display: flex; align-items: center; gap: 10px; border: 1px solid rgba(0, 212, 168, 0.3); border-radius: 8px; padding: 12px; background: rgba(0, 212, 168, 0.05); cursor: pointer; color: white; font-size: 13px;">
                                    <input type="radio" name="payment_method_sel" value="gcash" checked style="accent-color: #00d4a8;">
                                    <span>GCash</span>
                                </label>
                                <label class="payment-option-label" style="display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; padding: 12px; background: rgba(8, 8, 8, 0.48); cursor: pointer; color: #8d8b8b; font-size: 13px;">
                                    <input type="radio" name="payment_method_sel" value="maya" style="accent-color: #00d4a8;">
                                    <span>Maya</span>
                                </label>
                                <label class="payment-option-label" style="display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; padding: 12px; background: rgba(8, 8, 8, 0.48); cursor: pointer; color: #8d8b8b; font-size: 13px;">
                                    <input type="radio" name="payment_method_sel" value="paypal" style="accent-color: #00d4a8;">
                                    <span>PayPal</span>
                                </label>
                                <label class="payment-option-label" style="display: flex; align-items: center; gap: 10px; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px; padding: 12px; background: rgba(8, 8, 8, 0.48); cursor: pointer; color: #8d8b8b; font-size: 13px;">
                                    <input type="radio" name="payment_method_sel" value="card" style="accent-color: #00d4a8;">
                                    <span>Debit/Credit Card</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Payment details panels -->
                    <div style="border-left: 1px solid rgba(255, 255, 255, 0.08); padding-left: 20px; display: flex; flex-direction: column; justify-content: center; min-height: 200px;">
                        <!-- GCash/Maya Screen -->
                        <div class="payment-screen" id="screen-qr">
                            <p style="font-size: 12px; color: #8d8b8b; margin: 0 0 12px 0; text-align: center;">Scan QR to pay securely via GCash or Maya app.</p>
                            <div style="display: flex; justify-content: center; background: white; padding: 12px; border-radius: 8px; width: fit-content; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                <img id="checkout-qr-img" src="" alt="QR Code" style="width: 150px; height: 150px; display: block; object-fit: contain;">
                            </div>
                            <p style="font-size: 10px; color: #555; text-align: center; margin: 8px 0 0 0; text-transform: uppercase; font-family: monospace;">Scanable mockup payment code</p>
                        </div>

                        <!-- PayPal Screen -->
                        <div class="payment-screen" id="screen-paypal" style="display: none; flex-direction: column; gap: 10px;">
                            <p style="font-size: 12px; color: #8d8b8b; margin: 0 0 6px 0;">Log in with your PayPal account credentials.</p>
                            <input class="email-input-bar" type="email" placeholder="PayPal Email Address" style="padding: 12px 10px;">
                            <input class="email-input-bar" type="password" placeholder="Password" style="padding: 12px 10px;">
                        </div>

                        <!-- Card Screen -->
                        <div class="payment-screen" id="screen-card" style="display: none; flex-direction: column; gap: 10px;">
                            <p style="font-size: 12px; color: #8d8b8b; margin: 0 0 6px 0;">Enter your credit/debit card credentials.</p>
                            <input class="email-input-bar" type="text" placeholder="Cardholder Name" style="padding: 12px 10px;">
                            <input class="email-input-bar" type="text" placeholder="Card Number (e.g. 4111 2222 3333 4444)" style="padding: 12px 10px;">
                            <div style="display: flex; gap: 10px;">
                                <input class="email-input-bar" type="text" placeholder="MM/YY" style="padding: 12px 10px;">
                                <input class="email-input-bar" type="text" placeholder="CVC" style="padding: 12px 10px;">
                            </div>
                        </div>

                        <!-- Processing Screen -->
                        <div class="payment-screen" id="screen-processing" style="display: none; text-align: center;">
                            <div style="width: 40px; height: 40px; border: 3px solid rgba(0,212,168,0.1); border-top-color: #00d4a8; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                            <p style="color: #00d4a8; margin: 15px 0 0 0; font-weight: bold; font-family: monospace; font-size: 13px;">Processing Transaction...</p>
                        </div>
                    </div>
                </div>

                <div class="checkout-modal-footer" style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 16px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="how-it-works-btn" id="cancel-checkout-btn" style="padding: 10px 20px; font-size: 14px; border-radius: 8px;">Cancel</button>
                    <button class="confirm-booking-btn" id="pay-submit-btn" style="padding: 10px 24px; font-size: 14px; border-radius: 8px; width: auto; margin: 0;" disabled>Complete Payment</button>
                </div>
            </div>
        </div>

        <!-- Payment Success / Grace Period Alert Modal -->
        <div class="checkout-modal-backdrop" id="success-alert-modal" style="display: none; z-index: 1100;">
            <div class="checkout-modal-card" style="width: min(480px, 100%); text-align: center; gap: 16px;">
                <div style="background: rgba(0, 212, 168, 0.1); border: 2px solid #00d4a8; border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#00d4a8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h2 class="booking-panel-title" style="margin: 0; font-size: 22px; font-family: system-ui, sans-serif; text-align: center;">Payment Successful!</h2>
                
                <div style="border-top: 1px solid rgba(255, 255, 255, 0.08); border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding: 16px 0; font-size: 13px; color: #8d8b8b; text-align: left; line-height: 1.6; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="font-size: 16px;">📱</span>
                        <p style="margin: 0; color: #e2e6ed;">Please **prepare your QR code** (shown on the ticket) to scan or present to the parking attendant upon arrival.</p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 12px;">
                        <span style="font-size: 16px;">⚠️</span>
                        <p style="margin: 0; color: #ff9f43;"><strong style="color: #ffb066;">30-Minute Grace Period:</strong> You must arrive within **30 minutes** of your scheduled arrival time. Failure to do so will result in your reservation being marked as **Void**.</p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="font-size: 16px;">💸</span>
                        <p style="margin: 0; color: #8d8b8b;">Voided spots will be released to the public, and a **50% refund** will be issued back to your payment method.</p>
                    </div>
                </div>
                
                <button class="confirm-booking-btn" id="success-alert-close-btn" style="margin: 0; width: 100%; border-radius: 8px;">Got it, View Ticket</button>
            </div>
        </div>
    </section>
</body>
</html>
