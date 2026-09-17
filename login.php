<?php
// login.php
// Password-less login & sign up page via Phone OTP and Google Auth

require_once 'config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Portal - Zeng Customs</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Identity Services script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <header>
        <div class="logo">Zeng <span>Customs</span></div>
        <nav class="nav-links">
            <a href="index.php">Design Studio</a>
        </nav>
    </header>

    <!-- Simulated SMS Toast Alert -->
    <div id="sms-simulated-alert" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(-200px);
        background: #1e1b4b;
        border: 1.5px solid #818cf8;
        border-radius: var(--radius-md);
        box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 20px rgba(99, 102, 241, 0.25);
        padding: 1.25rem 1.75rem;
        z-index: 10000;
        width: 90%;
        max-width: 400px;
        transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 1rem;
    ">
        <div style="font-size: 2rem;">💬</div>
        <div>
            <div style="font-weight: 700; color: #818cf8; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase;">Simulated SMS Gateway</div>
            <div id="sms-text" style="font-size: 0.95rem; margin-top: 0.25rem; font-weight: 500;">Your OTP code is: <strong id="sms-code-placeholder" style="color: #f43f5e; font-size: 1.1rem; font-family: monospace;">123456</strong></div>
        </div>
    </div>

    <main class="auth-container">
        <div class="auth-card">
            <h2>Authentication</h2>
            <p>Access your custom design profile via Phone OTP or Gmail.</p>

            <div id="error-box" class="auth-error" style="display: none;"></div>

            <!-- OTP Login / Sign up Form -->
            <form id="otp-form" onsubmit="handleOtpSubmit(event)">
                <!-- Name input: visible only during registration -->
                <div class="form-group" id="name-group" style="display: none;">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="John Doe">
                    <span style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem; display: block;">First-time registration: Please enter your name.</span>
                </div>

                <!-- Phone number input -->
                <div class="form-group" id="phone-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required placeholder="e.g. +1234567890">
                </div>

                <!-- Verification code input -->
                <div class="form-group" id="otp-group" style="display: none;">
                    <label for="otp">Verification Code</label>
                    <input type="text" id="otp" name="otp" placeholder="6-digit code" maxlength="6" pattern="\d{6}">
                </div>

                <!-- Action Button 1: Send OTP -->
                <button type="button" class="order-btn" id="send-otp-btn" onclick="sendOtpCode()" style="margin-top: 1rem;">
                    <div class="spinner" id="phone-spinner" style="display: none;"></div>
                    <span>Send Verification Code</span>
                </button>

                <!-- Action Button 2: Verify OTP -->
                <button type="submit" class="order-btn" id="verify-otp-btn" style="margin-top: 1rem; display: none;">
                    <div class="spinner" id="otp-spinner" style="display: none;"></div>
                    <span>Verify &amp; Authenticate</span>
                </button>
            </form>

            <!-- Separator -->
            <div style="margin: 1.5rem 0; display: flex; align-items: center; justify-content: center; gap: 0.5rem; color: var(--text-secondary); font-size: 0.85rem;">
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
                <span>OR</span>
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
            </div>

            <!-- Google Sign-In Button -->
            <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
                <div id="g_id_onload"
                     data-client_id="<?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?>"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleGoogleLogin"
                     data-auto_prompt="false">
                </div>

                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="filled_black"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="350">
                </div>
            </div>

            <div class="auth-footer" id="form-reset-option" style="display: none;">
                Entered a wrong number? <a href="#" onclick="resetAuthForm(event)">Change Number</a>
            </div>
        </div>
    </main>

    <!-- Authentication JavaScript Handlers -->
    <script>
        const errorBox = document.getElementById('error-box');
        const phoneInput = document.getElementById('phone');
        const nameInput = document.getElementById('name');
        const otpInput = document.getElementById('otp');
        const phoneGroup = document.getElementById('phone-group');
        const nameGroup = document.getElementById('name-group');
        const otpGroup = document.getElementById('otp-group');
        const sendOtpBtn = document.getElementById('send-otp-btn');
        const verifyOtpBtn = document.getElementById('verify-otp-btn');
        const phoneSpinner = document.getElementById('phone-spinner');
        const otpSpinner = document.getElementById('otp-spinner');
        const formResetOption = document.getElementById('form-reset-option');

        // Step 1: Send OTP to Phone
        async function sendOtpCode() {
            const phone = phoneInput.value.trim();
            if (!phone) {
                showError("Please enter a valid phone number.");
                return;
            }

            hideError();
            sendOtpBtn.disabled = true;
            phoneSpinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('phone', phone);

                const response = await fetch('send_otp.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reveal OTP verification input
                    otpGroup.style.display = 'block';
                    verifyOtpBtn.style.display = 'flex';
                    formResetOption.style.display = 'block';
                    
                    // Hide send button and lock phone input
                    sendOtpBtn.style.display = 'none';
                    phoneInput.readOnly = true;
                    phoneInput.style.opacity = '0.6';

                    // If mock fallback is triggered, show simulated SMS popup
                    if (!result.realtime && result.mock_code) {
                        triggerSimulatedSMS(result.mock_code);
                    }
                } else {
                    showError(result.message || "Failed to transmit verification code.");
                    sendOtpBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                showError("An error occurred. Please try again.");
                sendOtpBtn.disabled = false;
            } finally {
                phoneSpinner.style.display = 'none';
            }
        }

        // Step 2: Verify OTP code & authenticate
        async function handleOtpSubmit(event) {
            event.preventDefault();
            
            const phone = phoneInput.value.trim();
            const otp = otpInput.value.trim();
            const name = nameInput.value.trim();

            if (!otp || otp.length !== 6) {
                showError("Please enter a 6-digit verification code.");
                return;
            }

            hideError();
            verifyOtpBtn.disabled = true;
            otpSpinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('phone', phone);
                formData.append('otp', otp);
                if (name) {
                    formData.append('name', name);
                }

                const response = await fetch('verify_otp.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect
                    if (result.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else if (result.requires_name) {
                    // Display name input for registration
                    nameGroup.style.display = 'block';
                    nameInput.required = true;
                    showError(result.message);
                    verifyOtpBtn.disabled = false;
                } else {
                    showError(result.message || "Failed to verify OTP code.");
                    verifyOtpBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                showError("Verification error. Please try again.");
                verifyOtpBtn.disabled = false;
            } finally {
                otpSpinner.style.display = 'none';
            }
        }

        // Reset auth form to enter a different number
        function resetAuthForm(e) {
            e.preventDefault();
            
            // Hide inputs
            otpGroup.style.display = 'none';
            nameGroup.style.display = 'none';
            verifyOtpBtn.style.display = 'none';
            formResetOption.style.display = 'none';
            
            // Reveal send button and unlock phone input
            sendOtpBtn.style.display = 'flex';
            sendOtpBtn.disabled = false;
            phoneInput.readOnly = false;
            phoneInput.style.opacity = '1.0';
            
            // Reset values
            otpInput.value = '';
            nameInput.value = '';
            nameInput.required = false;
            hideError();
            
            // Dismiss SMS notification if visible
            dismissSMSNotification();
        }

        // Trigger SMS popup overlay animation
        function triggerSimulatedSMS(code) {
            const smsAlert = document.getElementById('sms-simulated-alert');
            const placeholder = document.getElementById('sms-code-placeholder');
            placeholder.textContent = code;
            smsAlert.style.transform = 'translateX(-50%) translateY(0)';
            
            // Auto hide after 8 seconds
            setTimeout(dismissSMSNotification, 8000);
        }

        function dismissSMSNotification() {
            const smsAlert = document.getElementById('sms-simulated-alert');
            smsAlert.style.transform = 'translateX(-50%) translateY(-200px)';
        }

        // Google OAuth Callback Handler
        async function handleGoogleLogin(googleResponse) {
            try {
                const formData = new FormData();
                formData.append('credential', googleResponse.credential);
                
                const response = await fetch('google_auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    if (result.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else {
                    showError(result.message || "Google authentication failed.");
                }
            } catch (error) {
                console.error("Google login error:", error);
                showError("An error occurred during Google sign-in.");
            }
        }

        // Helpers
        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.style.display = 'block';
        }

        function hideError() {
            errorBox.textContent = '';
            errorBox.style.display = 'none';
        }
    </script>
</body>
</html>
