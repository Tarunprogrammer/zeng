<?php
// send_otp.php
// Generate OTP and send via Twilio SMS (or mock browser fallback)

header('Content-Type: application/json');
session_start();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

// Generate a random 6-digit OTP code
$otp_code = sprintf("%06d", mt_rand(100000, 999999));

// Store OTP details in PHP Session (valid for 5 minutes)
$_SESSION['otp_code'] = $otp_code;
$_SESSION['otp_phone'] = $phone;
$_SESSION['otp_expires'] = time() + 300; // 5 minutes expiry

$twilio_configured = !empty(TWILIO_SID) && !empty(TWILIO_TOKEN) && !empty(TWILIO_PHONE);

if ($twilio_configured) {
    // Send Real-time SMS via Twilio API using cURL
    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    
    $post_data = [
        'To' => $phone,
        'From' => TWILIO_PHONE,
        'Body' => "Your Zeng Customs verification code is: " . $otp_code
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP/Windows curl compatibility
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 201) {
        echo json_encode([
            'success' => true, 
            'realtime' => true, 
            'message' => 'OTP sent successfully to your phone in realtime!'
        ]);
    } else {
        // Detailed error response from Twilio API for debugging
        $error_response = json_decode($response, true);
        $error_msg = $error_response['message'] ?? 'Unknown error';
        
        echo json_encode([
            'success' => false, 
            'message' => 'Twilio failed to send SMS: ' . $error_msg . ' (Status ' . $http_status . ')'
        ]);
    }
} else {
    // Fallback mode: Send code in response for local browser mock display
    echo json_encode([
        'success' => true, 
        'realtime' => false, 
        'mock_code' => $otp_code,
        'message' => 'Mock SMS (Twilio not configured). Check your browser notification!'
    ]);
}
