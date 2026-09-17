<?php
// index.php
// T-Shirt Customization Home Page
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeng Custom Prints - Custom T-Shirt Studio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="logo">Zeng <span>Customs</span></div>
        <nav class="nav-links">
            <a href="index.php" class="active">Design Studio</a>
            <?php if ($is_logged_in): ?>
                <?php if ($user_role === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <span style="color: var(--text-secondary); margin-left: 1.5rem;">Hello, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Sign In</a>
                <a href="register.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="app-container">
        <!-- T-Shirt Workspace Viewport (Left) -->
        <section class="viewport-card">
            <!-- Active Side Badge -->
            <div class="view-indicator" id="view-indicator">Front View</div>

            <div class="tshirt-wrapper" id="tshirt-wrapper">
                <!-- 3D Rotating Container -->
                <div class="tshirt-3d-container" id="tshirt-3d-container">
                    <!-- Front Face -->
                    <div class="tshirt-face tshirt-face-front" id="tshirt-face-front">
                        <div class="tshirt-svg-container" id="tshirt-svg-container-front">
                            <?php 
                            $svg_path = __DIR__ . '/assets/tshirt.svg';
                            if (file_exists($svg_path)) {
                                echo file_get_contents($svg_path);
                            } else {
                                echo "Error loading T-Shirt front template.";
                            }
                            ?>
                        </div>
                        <div class="printable-area" id="printable-area-front">
                            <div class="design-element" id="design-front" style="display: none;">
                                <img id="design-img-front" src="" alt="Front Design">
                                <div class="design-control-handle handle-resize" id="handle-resize-front" title="Resize"></div>
                                <div class="design-control-handle handle-delete" id="handle-delete-front" title="Delete Front Design"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Back Face -->
                    <div class="tshirt-face tshirt-face-back" id="tshirt-face-back">
                        <div class="tshirt-svg-container" id="tshirt-svg-container-back">
                            <?php 
                            $svg_back_path = __DIR__ . '/assets/tshirt_back.svg';
                            if (file_exists($svg_back_path)) {
                                echo file_get_contents($svg_back_path);
                            } else {
                                echo "Error loading T-Shirt back template.";
                            }
                            ?>
                        </div>
                        <div class="printable-area" id="printable-area-back">
                            <div class="design-element" id="design-back" style="display: none;">
                                <img id="design-img-back" src="" alt="Back Design">
                                <div class="design-control-handle handle-resize" id="handle-resize-back" title="Resize"></div>
                                <div class="design-control-handle handle-delete" id="handle-delete-back" title="Delete Back Design"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3D Rotation Controls -->
            <div class="rotation-controls-wrapper">
                <div class="rotation-btn-group">
                    <button type="button" class="rotation-btn active" id="snap-front-btn">Front</button>
                    <button type="button" class="rotation-btn" id="snap-back-btn">Back</button>
                </div>
                
                <div class="slider-container">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="rotation-icon" fill="currentColor">
                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                    </svg>
                    <input type="range" id="rotation-slider" class="rotation-slider" min="0" max="360" value="0" title="Rotate T-Shirt">
                </div>

                <button type="button" class="auto-rotate-toggle" id="auto-rotate-btn" title="Toggle Auto-Rotation">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="play-icon" id="play-icon" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="pause-icon" id="pause-icon" fill="currentColor" style="display: none;">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                </button>
            </div>
        </section>

        <!-- Configuration Controls (Right) -->
        <section class="controls-card">
            <form id="order-form" onsubmit="submitOrder(event)">
                <!-- Section 1: Color Select -->
                <div class="control-section">
                    <h3>Select Shirt Color <span id="color-name-label">White</span></h3>
                    <div class="color-options">
                        <div class="color-swatch active" style="background-color: #ffffff;" data-color="#ffffff" data-name="White"></div>
                        <div class="color-swatch" style="background-color: #18181b;" data-color="#18181b" data-name="Matte Black"></div>
                        <div class="color-swatch" style="background-color: #1e3a8a;" data-color="#1e3a8a" data-name="Navy Blue"></div>
                        <div class="color-swatch" style="background-color: #991b1b;" data-color="#991b1b" data-name="Crimson Red"></div>
                        <div class="color-swatch" style="background-color: #064e3b;" data-color="#064e3b" data-name="Forest Green"></div>
                        <div class="color-swatch" style="background-color: #581c87;" data-color="#581c87" data-name="Imperial Purple"></div>
                        
                        <!-- Custom Color Picker -->
                        <div class="color-picker-wrapper" id="custom-color-wrapper" title="Choose Custom Color">
                            <input type="color" id="custom-color-picker" value="#6366f1">
                        </div>
                    </div>
                    <!-- Hidden input to store chosen color -->
                    <input type="hidden" name="shirt_color" id="shirt_color_input" value="#ffffff">
                </div>

                <!-- Section 2: Size Select -->
                <div class="control-section">
                    <h3>Select Size</h3>
                    <div class="size-options">
                        <button type="button" class="size-btn" data-size="S">S</button>
                        <button type="button" class="size-btn active" data-size="M">M</button>
                        <button type="button" class="size-btn" data-size="L">L</button>
                        <button type="button" class="size-btn" data-size="XL">XL</button>
                        <button type="button" class="size-btn" data-size="XXL">XXL</button>
                    </div>
                    <!-- Hidden input to store chosen size -->
                    <input type="hidden" name="shirt_size" id="shirt_size_input" value="M">
                </div>

                <!-- Section 3: Design Select -->
                <div class="control-section">
                    <h3>Choose a Design <span>Drag &amp; drop or click to apply</span></h3>
                    <div class="designs-catalog">
                        <div class="design-thumb" data-design-src="assets/design_retro_sun.svg">
                            <img src="assets/design_retro_sun.svg" alt="Retro Sun" draggable="true" class="catalog-design-img">
                        </div>
                        <div class="design-thumb" data-design-src="assets/design_mountains.svg">
                            <img src="assets/design_mountains.svg" alt="Mountains" draggable="true" class="catalog-design-img">
                        </div>
                        <div class="design-thumb" data-design-src="assets/design_neon_mind.svg">
                            <img src="assets/design_neon_mind.svg" alt="Neon Mind" draggable="true" class="catalog-design-img">
                        </div>
                        <div class="design-thumb" data-design-src="assets/design_coffee_code.svg">
                            <img src="assets/design_coffee_code.svg" alt="Coffee Code" draggable="true" class="catalog-design-img">
                        </div>
                    </div>
                    
                    <div class="upload-wrapper">
                        <label for="custom-design-input" class="file-upload-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Upload Custom Design (PNG/JPG/SVG)</span>
                        </label>
                        <input type="file" id="custom-design-input" accept="image/*" onchange="handleCustomFileUpload(event)">
                    </div>
                    
                    <!-- Form parameters to track design composition -->
                    <!-- Front design -->
                    <input type="hidden" name="design_type" id="design_type_input" value="none">
                    <input type="hidden" name="design_src" id="design_src_input" value="">
                    <input type="hidden" name="design_x" id="design_x_input" value="50">
                    <input type="hidden" name="design_y" id="design_y_input" value="50">
                    <input type="hidden" name="design_scale" id="design_scale_input" value="0.5">
                    
                    <!-- Back design -->
                    <input type="hidden" name="back_design_type" id="back_design_type_input" value="none">
                    <input type="hidden" name="back_design_src" id="back_design_src_input" value="">
                    <input type="hidden" name="back_design_x" id="back_design_x_input" value="50">
                    <input type="hidden" name="back_design_y" id="back_design_y_input" value="50">
                    <input type="hidden" name="back_design_scale" id="back_design_scale_input" value="0.5">
                    
                    <input type="hidden" name="preview_image" id="preview_image_input" value="">
                </div>

                <!-- Section 4: Shipping Info -->
                <div class="customer-form">
                    <h3>Shipping &amp; Order Details</h3>
                    
                    <div class="form-group">
                        <label for="customer_name">Full Name</label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                               placeholder="John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email">Email Address</label>
                        <input type="email" id="customer_email" name="customer_email" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" 
                               placeholder="john@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone">Phone Number</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" 
                               placeholder="+1 (555) 000-0000">
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address</label>
                        <textarea id="shipping_address" name="shipping_address" required placeholder="123 Street Name, City, Zip Code"></textarea>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <button type="submit" class="order-btn" id="submit-order-btn">
                            <div class="spinner" id="btn-spinner"></div>
                            <span>Place Custom Order</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="order-btn" onclick="window.location.href='login.php'" style="background: linear-gradient(135deg, #ec4899 0%, #818cf8 100%);">
                            <span>Sign In to Place Order</span>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </main>

    <!-- Success Popup / Notification -->
    <div class="toast" id="toast-notification">
        <span id="toast-message">Customizer loaded!</span>
    </div>

    <!-- Script imports -->
    <script src="customizer.js"></script>
</body>
</html>
