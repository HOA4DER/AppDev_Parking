<?php
$host = '127.0.0.1';
$db   = 'siksik_parking';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect to MySQL server first without selecting DB
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Use the database
    $pdo->exec("USE `$db`");
    
    // Create tables
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `first_name` VARCHAR(50) NOT NULL,
        `last_name` VARCHAR(50) NOT NULL,
        `phone_number` VARCHAR(15) NOT NULL,
        `plate_number` VARCHAR(10) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Bookings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `receipt_number` VARCHAR(30) NOT NULL UNIQUE,
        `location_id` VARCHAR(50) NOT NULL,
        `location_name` VARCHAR(100) NOT NULL,
        `floor` VARCHAR(50) NOT NULL,
        `spot_label` VARCHAR(10) NOT NULL,
        `spot_type` VARCHAR(20) NOT NULL,
        `arrival_time` TIME NOT NULL,
        `duration_hours` INT NOT NULL,
        `hourly_rate` DECIMAL(10, 2) NOT NULL,
        `total_amount` DECIMAL(10, 2) NOT NULL,
        `status` VARCHAR(20) DEFAULT 'confirmed',
        `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Dynamic database migrations
    $columns = $pdo->query("DESCRIBE `bookings`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('booking_date', $columns)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `booking_date` DATE NULL AFTER `user_id`");
        $pdo->exec("UPDATE `bookings` SET `booking_date` = DATE(issued_at)");
        $pdo->exec("ALTER TABLE `bookings` MODIFY COLUMN `booking_date` DATE NOT NULL");
    }
    if (!in_array('payment_method', $columns)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'gcash' AFTER `hourly_rate`");
    }
    if (!in_array('payment_status', $columns)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `payment_status` VARCHAR(20) DEFAULT 'paid' AFTER `payment_method`");
    }
    if (!in_array('is_overnight', $columns)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `is_overnight` TINYINT(1) DEFAULT 0 AFTER `payment_status`");
    }
    if (!in_array('plate_number', $columns)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `plate_number` VARCHAR(15) NULL AFTER `user_id`");
        $pdo->exec("UPDATE `bookings` b JOIN `users` u ON b.user_id = u.id SET b.plate_number = u.plate_number WHERE b.plate_number IS NULL");
    }

    // 2. Users table migrations
    $user_cols = $pdo->query("DESCRIBE `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('has_multiple_vehicles', $user_cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `has_multiple_vehicles` TINYINT(1) DEFAULT 0 AFTER `plate_number`");
    }

    // 3. Create user_vehicles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_vehicles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `plate_number` VARCHAR(15) NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
