<?php
// save_order.php
// Handle order submission from customizer via AJAX POST

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please sign in to place your order.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once 'config.php';

// Create uploads directory if it doesn't exist
$uploads_dir = __DIR__ . '/uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Extract and sanitize inputs
$customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_SPECIAL_CHARS);
$customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_VALIDATE_EMAIL);
$customer_phone = filter_input(INPUT_POST, 'customer_phone', FILTER_SANITIZE_SPECIAL_CHARS);
$shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_SPECIAL_CHARS);
$shirt_size = filter_input(INPUT_POST, 'shirt_size', FILTER_SANITIZE_SPECIAL_CHARS);
$shirt_color = filter_input(INPUT_POST, 'shirt_color', FILTER_SANITIZE_SPECIAL_CHARS);

// Front design inputs
$design_type = filter_input(INPUT_POST, 'design_type', FILTER_SANITIZE_SPECIAL_CHARS);
$design_src = filter_input(INPUT_POST, 'design_src', FILTER_SANITIZE_SPECIAL_CHARS);
$design_x = filter_input(INPUT_POST, 'design_x', FILTER_VALIDATE_FLOAT);
$design_y = filter_input(INPUT_POST, 'design_y', FILTER_VALIDATE_FLOAT);
$design_scale = filter_input(INPUT_POST, 'design_scale', FILTER_VALIDATE_FLOAT);

// Back design inputs
$back_design_type = filter_input(INPUT_POST, 'back_design_type', FILTER_SANITIZE_SPECIAL_CHARS);
$back_design_src = filter_input(INPUT_POST, 'back_design_src', FILTER_SANITIZE_SPECIAL_CHARS);
$back_design_x = filter_input(INPUT_POST, 'back_design_x', FILTER_VALIDATE_FLOAT);
$back_design_y = filter_input(INPUT_POST, 'back_design_y', FILTER_VALIDATE_FLOAT);
$back_design_scale = filter_input(INPUT_POST, 'back_design_scale', FILTER_VALIDATE_FLOAT);

$preview_image = $_POST['preview_image'] ?? ''; // Contains Base64 data of composed mockup

// Validation checks (at least one design must be active)
if (
    empty($customer_name) || !$customer_email || empty($customer_phone) || 
    empty($shipping_address) || empty($shirt_size) || empty($shirt_color) || 
    empty($preview_image) ||
    (empty($design_type) && empty($back_design_type)) ||
    ($design_type === 'none' && $back_design_type === 'none')
) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid order details. At least one design is required.']);
    exit;
}

try {
    $final_design_src = $design_src;

    // Handle front user custom design uploads passed as Base64 dataURL
    if ($design_type === 'custom' && isset($_POST['custom_design_data'])) {
        $base64_string = $_POST['custom_design_data'];
        
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $image_type = strtolower($type[1]);
            
            if (!in_array($image_type, ['jpg', 'jpeg', 'gif', 'png', 'svg', 'svg+xml'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid front image format. Only PNG, JPG, GIF, and SVG allowed.']);
                exit;
            }
            
            $data = substr($base64_string, strpos($base64_string, ',') + 1);
            $data = base64_decode($data);
            
            if ($data === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to decode front custom design image.']);
                exit;
            }
            
            $extension = ($image_type === 'svg+xml') ? 'svg' : $image_type;
            $filename = 'design_custom_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $file_path = $uploads_dir . '/' . $filename;
            
            file_put_contents($file_path, $data);
            $final_design_src = 'uploads/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image encoding for front custom design.']);
            exit;
        }
    }

    $final_back_design_src = $back_design_src;

    // Handle back user custom design uploads passed as Base64 dataURL
    if ($back_design_type === 'custom' && isset($_POST['back_custom_design_data'])) {
        $back_base64_string = $_POST['back_custom_design_data'];
        
        if (preg_match('/^data:image\/(\w+);base64,/', $back_base64_string, $back_type)) {
            $back_image_type = strtolower($back_type[1]);
            
            if (!in_array($back_image_type, ['jpg', 'jpeg', 'gif', 'png', 'svg', 'svg+xml'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid back image format. Only PNG, JPG, GIF, and SVG allowed.']);
                exit;
            }
            
            $back_data = substr($back_base64_string, strpos($back_base64_string, ',') + 1);
            $back_data = base64_decode($back_data);
            
            if ($back_data === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to decode back custom design image.']);
                exit;
            }
            
            $back_extension = ($back_image_type === 'svg+xml') ? 'svg' : $back_image_type;
            $back_filename = 'design_custom_back_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $back_extension;
            $back_file_path = $uploads_dir . '/' . $back_filename;
            
            file_put_contents($back_file_path, $back_data);
            $final_back_design_src = 'uploads/' . $back_filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image encoding for back custom design.']);
            exit;
        }
    }

    // Insert order record into database using PDO
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            customer_name, 
            customer_email, 
            customer_phone, 
            shipping_address, 
            shirt_size, 
            shirt_color, 
            design_type, 
            design_src, 
            design_x, 
            design_y, 
            design_scale,
            back_design_type,
            back_design_src,
            back_design_x,
            back_design_y,
            back_design_scale,
            preview_image
        ) VALUES (
            :user_id,
            :customer_name, 
            :customer_email, 
            :customer_phone, 
            :shipping_address, 
            :shirt_size, 
            :shirt_color, 
            :design_type, 
            :design_src, 
            :design_x, 
            :design_y, 
            :design_scale, 
            :back_design_type,
            :back_design_src,
            :back_design_x,
            :back_design_y,
            :back_design_scale,
            :preview_image
        )
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':customer_name' => $customer_name,
        ':customer_email' => $customer_email,
        ':customer_phone' => $customer_phone,
        ':shipping_address' => $shipping_address,
        ':shirt_size' => $shirt_size,
        ':shirt_color' => $shirt_color,
        ':design_type' => $design_type,
        ':design_src' => $final_design_src,
        ':design_x' => $design_x,
        ':design_y' => $design_y,
        ':design_scale' => $design_scale,
        ':back_design_type' => $back_design_type,
        ':back_design_src' => $final_back_design_src,
        ':back_design_x' => $back_design_x,
        ':back_design_y' => $back_design_y,
        ':back_design_scale' => $back_design_scale,
        ':preview_image' => $preview_image
    ]);

    $order_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true, 
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
