<?php
// config.php
// Database configuration for T-Shirt Customizer website

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tshirt_shop');
define('DB_PORT', '3306');
// Google OAuth 2.0 Credentials (Replace with your actual credentials from Google Developer Console)
define('GOOGLE_CLIENT_ID', '309602879836-l2c9as6eu4jk5clpgjjl640epni91f9s.apps.googleusercontent.com');

// Twilio API Credentials for Real-time SMS (Leave empty to use simulated browser popup fallback)
define('TWILIO_SID', 'AC753e3b87658c3f5c92f524210ae5a8f7');
define('TWILIO_TOKEN', 'e6568ef718c153025422363b0f84251b');
define('TWILIO_PHONE', '+919121919410');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In production, you would log the error and show a generic message.
    // For development, we display the error.
    die("Database connection failed: " . $e->getMessage());
}
