<?php
// register.php - 客户注册页面
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>CareConnect · Customer Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/common.css">
    <style>
        /* 页面特有样式 */
        .password-strength {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .strength-bar {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
            border-radius: 3px;
        }
        .otp-container {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .otp-container .form-control {
            flex: 2;
            min-width: 150px;
        }
        .otp-timer {
            font-size: 0.75rem;
            color: #5bb4e8;
            margin-top: 5px;
        }
        @media (max-width: 640px) {
            .otp-container { flex-direction: column; }
            .otp-container .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="container-sm">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-sun"></i> Customer Registration</h1>
            <p>Join CareConnect · Secure account with email verification</p>
        </div>
        <div class="card-body">
            <div id="alertMessage" class="alert" style="display: none;"></div>

            <form id="registerForm" method="POST" action="register_submit.php">
                <!-- 基本信息 -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user-circle"></i> Full Name *</label>
                    <input type="text" id="fullName" name="full_name" class="form-control" placeholder="e.g., Sarah Johnson" autocomplete="name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="sarah@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-phone-alt"></i> Phone Number *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+60 12 345 6789" required>
                    </div>
                </div>

                <!-- OTP 验证 -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-key"></i> Email Verification *</label>
                    <div class="otp-container">
                        <input type="text" id="otpCode" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6" autocomplete="off">
                        <button type="button" id="sendOtpBtn" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send OTP</button>
                    </div>
                    <div class="otp-timer" id="otpTimer"></div>
                    <input type="hidden" id="otpVerified" name="otp_verified" value="0">
                </div>

                <!-- 密码设置 -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a strong password" required>
                        <div class="password-strength">
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <span class="strength-text" id="strengthText">Weak</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm your password" required>
                    </div>
                </div>

                <!-- 个人信息 -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Date of Birth *</label>
                        <input type="date" id="dob" name="dob" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="gender" value="Female"> Female</label>
                            <label class="radio-option"><input type="radio" name="gender" value="Male"> Male</label>
                            <label class="radio-option"><input type="radio" name="gender" value="Other"> Other</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea id="address" name="address" class="form-control" rows="2" placeholder="Street, city, postal code"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-notes-medical"></i> Medical History</label>
                        <input type="text" id="medicalNotes" name="medical_notes" class="form-control" placeholder="e.g., penicillin allergy">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-heartbeat"></i> Emergency Contact</label>
                    <input type="text" id="emergencyContact" name="emergency_contact" class="form-control" placeholder="Name & Phone">
                </div>

                <div class="checkbox-group">
                    <label><input type="checkbox" id="newsletterOpt" name="newsletter" value="1"> <i class="fas fa-newspaper"></i> Receive health tips & appointment reminders</label>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">

                <button type="submit" class="btn btn-primary btn-block btn-large mt-3">
                    <i class="fas fa-cloud-sun"></i> Create Account
                </button>
            </form>
        </div>
    </div>
</div>

<script src="common.js"></script>
<script>
    // ==================== 页面专用 JavaScript ====================
    
    let otpTimerInterval = null;
    let remainingSeconds = 0;
    
    // 显示消息
    function showAlert(message, type = 'success') {
        const alertDiv = document.getElementById('alertMessage');
        if (!alertDiv) return;
        
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
        alertDiv.style.display = 'flex';
        
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 4000);
    }
    
    // 发送 OTP
    async function sendOTP() {
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            showAlert('Please enter your email address first.', 'error');
            return false;
        }
        
        const emailPattern = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showAlert('Please enter a valid email address.', 'error');
            return false;
        }
        
        const sendBtn = document.getElementById('sendOtpBtn');
        const originalText = sendBtn.innerHTML;
        
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-hourglass-half"></i> Sending...';
        
        try {
            const response = await fetch('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('OTP sent to your email! Please check your inbox.', 'success');
                startOTPTimer(60);
            } else {
                showAlert(data.message, 'error');
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        } catch (error) {
            showAlert('Network error. Please try again.', 'error');
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalText;
        }
        
        return true;
    }
    
    // 验证 OTP
    async function verifyOTP() {
        const email = document.getElementById('email').value.trim();
        const otp = document.getElementById('otpCode').value.trim();
        
        if (!otp) {
            showAlert('Please enter the OTP code.', 'error');
            return false;
        }
        
        try {
            const response = await fetch('verify_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, otp: otp })
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('otpVerified').value = '1';
                document.getElementById('otpCode').disabled = true;
                document.getElementById('sendOtpBtn').disabled = true;
                showAlert('✓ Email verified successfully!', 'success');
                return true;
            } else {
                showAlert(data.message, 'error');
                return false;
            }
        } catch (error) {
            showAlert('Verification failed. Please try again.', 'error');
            return false;
        }
    }
    
    // 启动计时器
    function startOTPTimer(seconds) {
        if (otpTimerInterval) clearInterval(otpTimerInterval);
        
        remainingSeconds = seconds;
        const timerDisplay = document.getElementById('otpTimer');
        const sendBtn = document.getElementById('sendOtpBtn');
        
        sendBtn.disabled = true;
        
        otpTimerInterval = setInterval(() => {
            if (remainingSeconds <= 0) {
                clearInterval(otpTimerInterval);
                timerDisplay.textContent = '';
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Resend OTP';
            } else {
                const mins = Math.floor(remainingSeconds / 60);
                const secs = remainingSeconds % 60;
                timerDisplay.textContent = `Resend available in ${mins}:${secs.toString().padStart(2, '0')}`;
                remainingSeconds--;
            }
        }, 1000);
    }
    
    // 密码强度检测
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        
        if (strength <= 1) {
            fill.style.width = '25%';
            fill.style.backgroundColor = '#ef4444';
            text.textContent = 'Weak';
            text.style.color = '#ef4444';
        } else if (strength <= 3) {
            fill.style.width = '50%';
            fill.style.backgroundColor = '#f59e0b';
            text.textContent = 'Medium';
            text.style.color = '#f59e0b';
        } else if (strength <= 4) {
            fill.style.width = '75%';
            fill.style.backgroundColor = '#10b981';
            text.textContent = 'Strong';
            text.style.color = '#10b981';
        } else {
            fill.style.width = '100%';
            fill.style.backgroundColor = '#10b981';
            text.textContent = 'Very Strong';
            text.style.color = '#10b981';
        }
    }
    
    // 表单验证
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        const otpVerified = document.getElementById('otpVerified').value;
        
        if (otpVerified !== '1') {
            e.preventDefault();
            showAlert('Please verify your email with OTP before registering.', 'error');
            return false;
        }
        
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showAlert('Passwords do not match.', 'error');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            showAlert('Password must be at least 6 characters long.', 'error');
            return false;
        }
        
        return true;
    });
    
    // 设置 DOB 最大日期
    function setDobMaxDate() {
        const dobInput = document.getElementById('dob');
        if (dobInput) {
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
            dobInput.max = maxDate.toISOString().split('T')[0];
        }
    }
    
    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
        setDobMaxDate();
        
        const sendBtn = document.getElementById('sendOtpBtn');
        if (sendBtn) sendBtn.addEventListener('click', sendOTP);
        
        const otpInput = document.getElementById('otpCode');
        if (otpInput) otpInput.addEventListener('blur', verifyOTP);
        
        const passwordInput = document.getElementById('password');
        if (passwordInput) passwordInput.addEventListener('input', checkPasswordStrength);
    });
</script>
</body>
</html>