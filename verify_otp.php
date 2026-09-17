<?php
// verify_otp.php
// Verify OTP, login existing user, or create a new user account

header('Content-Type: application/json');
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$entered_otp = trim($_POST['otp'] ?? '');
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS)) ?? '';

if (empty($phone) || empty($entered_otp)) {
    echo json_encode(['success' => false, 'message' => 'Phone number and verification code are required.']);
    exit;
}

// 1. Validate session codes
$session_otp = $_SESSION['otp_code'] ?? '';
$session_phone = $_SESSION['otp_phone'] ?? '';
$session_expires = $_SESSION['otp_expires'] ?? 0;

if (empty($session_otp) || empty($session_phone)) {
    echo json_encode(['success' => false, 'message' => 'Verification session expired. Please request a new OTP.']);
    exit;
}

if (time() > $session_expires) {
    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
    exit;
}

if ($session_otp !== $entered_otp || $session_phone !== $phone) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    exit;
}

try {
    // Check if user exists with this phone number
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = :phone");
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists - Login
        // Clear OTP session variables
        unset($_SESSION['otp_code'], $_SESSION['otp_phone'], $_SESSION['otp_expires']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'] ?? ''; // Google users might have email
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        
        echo json_encode(['success' => true, 'role' => $user['role']]);
    } else {
        // User does not exist - Registration Flow
        if (empty($name)) {
            // Frontend needs to request name
            echo json_encode([
                'success' => false, 
                'requires_name' => true, 
                'message' => 'First-time signup requires a full name. Please enter your name and submit again.'
            ]);
            exit;
        }

        // Determine role: if this is the first user in DB, make them Admin. Otherwise, Customer.
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $count_stmt->fetchColumn();
        $role = ($user_count === 0) ? 'admin' : 'customer';

        // Insert new user
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (name, phone, role) 
            VALUES (:name, :phone, :role)
        ");
        $insert_stmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':role' => $role
        ]);

        $user_id = $pdo->lastInsertId();

        // Clear OTP session variables
        unset($_SESSION['otp_code'], $_SESSION['otp_phone'], $_SESSION['otp_expires']);

        // Start session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_email'] = '';
        $_SESSION['user_phone'] = $phone;

        echo json_encode(['success' => true, 'role' => $role]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
