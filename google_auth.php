<?php
// google_auth.php
// Handle Google OAuth 2.0 JWT token validation and login/registration

header('Content-Type: application/json');
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$credential = $_POST['credential'] ?? '';

if (empty($credential)) {
    echo json_encode(['success' => false, 'message' => 'No credential token received.']);
    exit;
}

// Base64URL decoding helper
function base64url_decode($data) {
    $b64 = strtr($data, '-_', '+/');
    $len = strlen($data) % 4;
    if ($len > 0) {
        $b64 .= str_repeat('=', 4 - $len);
    }
    return base64_decode($b64);
}

try {
    // A JWT token has 3 parts: Header, Payload, Signature
    $parts = explode('.', $credential);
    if (count($parts) !== 3) {
        echo json_encode(['success' => false, 'message' => 'Invalid token structure.']);
        exit;
    }

    // Decode Payload
    $payload_json = base64url_decode($parts[1]);
    $payload = json_decode($payload_json, true);

    if (!$payload) {
        echo json_encode(['success' => false, 'message' => 'Failed to parse token payload.']);
        exit;
    }

    // Security Verifications
    // 1. Verify Audience matches our Google Client ID (if not a dummy one)
    if (GOOGLE_CLIENT_ID !== '100867530999-dummyclientid.apps.googleusercontent.com' && $payload['aud'] !== GOOGLE_CLIENT_ID) {
        echo json_encode(['success' => false, 'message' => 'Audience verification failed.']);
        exit;
    }

    // 2. Verify Issuer is Google
    if (!in_array($payload['iss'], ['accounts.google.com', 'https://accounts.google.com'])) {
        echo json_encode(['success' => false, 'message' => 'Issuer verification failed.']);
        exit;
    }

    // 3. Verify Token is not expired (allow 5 min clock skew drift)
    if (time() > ($payload['exp'] + 300)) {
        echo json_encode(['success' => false, 'message' => 'Token has expired.']);
        exit;
    }

    // Extract User Profile details
    $email = filter_var($payload['email'], FILTER_VALIDATE_EMAIL);
    $name = filter_var($payload['name'], FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$email || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Failed to extract valid profile details.']);
        exit;
    }

    // Database Lookup
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists - Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        echo json_encode(['success' => true, 'role' => $user['role']]);
    } else {
        // User does not exist - Register new account
        // First user is Admin, rest are Customers
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $count_stmt->fetchColumn();
        $role = ($user_count === 0) ? 'admin' : 'customer';

        // Secure randomized password
        $random_password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($random_password, PASSWORD_BCRYPT);

        // Insert
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role) 
            VALUES (:name, :email, :password, :role)
        ");
        $insert_stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashed_password,
            ':role' => $role
        ]);

        $user_id = $pdo->lastInsertId();

        // Start session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_email'] = $email;

        echo json_encode(['success' => true, 'role' => $role]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
