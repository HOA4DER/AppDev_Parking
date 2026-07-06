<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['is_signed_in']) || empty($_SESSION['user_id'])) {
    header('Location: main.php');
    exit;
}

$user_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Manila');
$current_time = date('H:i:s');
$current_date = date('Y-m-d');

try {
    // 1. Fetch user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: main.php');
        exit;
    }

    // 2. Fetch all bookings for categorization
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
            
            // Grace period: 30 minutes (1800 seconds) after arrival
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
                // Classify past bookings: even IDs as Completed, odd IDs as Void
                if (intval($booking['id']) % 2 === 0) {
                    $completed_bookings[] = $booking;
                    $total_spent += floatval($booking['total_amount']);
                } else {
                    $void_bookings[] = $booking;
                    $total_spent += (floatval($booking['total_amount']) * 0.5); // 50% refund returned
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
    <script src="script.js" defer></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            margin-top: 28px;
            align-items: start;
        }
        .profile-sidebar {
            box-sizing: border-box;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            background: rgba(17, 19, 24, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            display: flex;
            flex-direction: column;
            gap: 20px;
            color: white;
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(0, 212, 168, 0.12);
            border: 1px solid rgba(0, 212, 168, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: #00d4a8;
            margin: 0 auto;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 16px;
            font-size: 13px;
        }
        .profile-info div {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .profile-info span {
            color: #555;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .profile-info strong {
            color: #e2e6ed;
            font-family: system-ui, sans-serif;
        }
        .dashboard-main {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }
        .stat-card {
            box-sizing: border-box;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            background: rgba(17, 19, 24, 0.6);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .stat-val {
            font-size: 28px;
            font-weight: bold;
            color: #00d4a8;
            font-family: system-ui, sans-serif;
        }
        .stat-lbl {
            font-size: 11px;
            color: #8d8b8b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tabs-header {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 8px;
            margin-top: 12px;
        }
        .tab-btn {
            background: transparent;
            border: none;
            color: #8d8b8b;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }
        .tab-btn.active {
            color: white;
            background: rgba(0, 212, 168, 0.12);
            border: 1px solid rgba(0, 212, 168, 0.2);
        }
        .tab-content-panel {
            display: none;
        }
        .tab-content-panel.active {
            display: block;
        }
        .empty-dashboard-state {
            border: 1px dashed rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 48px;
            text-align: center;
            color: #8d8b8b;
        }
        
        /* Modal Style for receipt view */
        .receipt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        .receipt-modal-card {
            width: min(420px, calc(100% - 32px));
            box-sizing: border-box;
            background: #111318;
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
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
                <a class="nav-bar-btn" href="book.php">Book Parking</a>
                <a class="nav-bar-btn" href="index.php#pricing">Pricing</a>
                <a class="nav-bar-btn" href="index.php#about-us">About Us</a>
                <a class="nav-bar-btn" href="index.php#contact">Contact</a>
            </div>

            <div class="sign-in-container" style="gap: 12px;">
                <a class="SignIn-btn" href="dashboard.php">
                    Dashboard
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
                    
                    <div class="profile-info">
                        <div>
                            <span>Phone Number</span>
                            <strong><?php echo htmlspecialchars($user['phone_number']); ?></strong>
                        </div>
                        <div>
                            <span>Plate Number</span>
                            <strong><?php echo htmlspecialchars($user['plate_number']); ?></strong>
                        </div>
                        <div>
                            <span>Account Created</span>
                            <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
                        </div>
                    </div>
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
                <div style="display: flex; justify-content: space-between;"><span>Overnight Parking:</span><strong id="m-overnight" style="color: white;">--</strong></div>
                <div style="display: flex; justify-content: space-between;"><span>Payment Method:</span><strong id="m-payment" style="color: white; text-transform: uppercase;">--</strong></div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 8px; margin-top: 4px; font-size: 14px;"><span style="color: #00d4a8;">Total Price:</span><strong id="m-total" style="color: #00d4a8; font-weight: bold;">--</strong></div>
            </div>
            
            <button onclick="downloadReceiptFromDashboard()" class="download-receipt-btn" style="margin: 0; width: 100%;">Download Text Ticket</button>
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
            const period = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return hours + ':' + minutes + ' ' + period;
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
    </script>
</body>
</html>
