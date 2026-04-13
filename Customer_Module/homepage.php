<?php
// Customer_Module/homepage.php
require_once __DIR__ . '/../config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$user = null;
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT id, email, name, nric, phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>CareConnect | Appointment Booking System</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { background-color:#f0f8ff; color:#1e2a3e; }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:1rem 5%; background:white; box-shadow:0 2px 10px rgba(0,0,0,0.05); position:sticky; top:0; z-index:100; }
        .logo { font-size:1.8rem; font-weight:bold; color:#0099ff; }
        .logo span { color:#2c3e66; }
        .nav-links { display:flex; gap:2rem; align-items:center; }
        .nav-links a { text-decoration:none; color:#2c3e66; font-weight:500; transition:0.3s; padding-bottom:4px; }
        .nav-links a:hover { color:#0099ff; }
        .nav-links a.active { color:#0099ff; font-weight:600; border-bottom:2px solid #0099ff; }
        .btn-outline { background:transparent; border:1.5px solid #0099ff; padding:0.4rem 1.2rem; border-radius:30px; color:#0099ff; cursor:pointer; font-weight:600; transition:0.3s; }
        .btn-outline:hover { background:#0099ff; color:white; }
        .btn-solid { background:#0099ff; border:none; padding:0.4rem 1.2rem; border-radius:30px; color:white; cursor:pointer; font-weight:600; transition:0.3s; }
        .btn-solid:hover { background:#0077cc; transform:scale(1.02); }
        .hero { display:flex; align-items:center; justify-content:space-between; padding:3rem 5%; background:linear-gradient(135deg,#ffffff 0%,#e6f4ff 100%); flex-wrap:wrap; }
        .hero-text { flex:1; min-width:280px; }
        .hero-text h1 { font-size:3rem; color:#0099ff; margin-bottom:1rem; }
        .hero-text p { font-size:1.2rem; color:#4a627a; margin-bottom:2rem; line-height:1.5; }
        .hero-image { flex:1; text-align:center; }
        .hero-image img { max-width:100%; border-radius:20px; box-shadow:0 20px 30px -10px rgba(0,153,255,0.2); width:450px; object-fit:cover; }
        .services { padding:4rem 5%; text-align:center; background:white; }
        .services h2 { font-size:2.2rem; color:#1e2a3e; margin-bottom:2rem; }
        .service-cards { display:flex; flex-wrap:wrap; justify-content:center; gap:2rem; }
        .card { background:#f9fcff; border-radius:16px; padding:2rem 1.5rem; width:260px; box-shadow:0 5px 15px rgba(0,0,0,0.05); transition:0.3s; border-bottom:3px solid #0099ff; }
        .card:hover { transform:translateY(-8px); box-shadow:0 15px 30px rgba(0,153,255,0.15); }
        .card-icon { font-size:3rem; margin-bottom:1rem; }
        .card h3 { color:#0099ff; margin-bottom:0.8rem; }
        .card p { color:#5b6e8c; font-size:0.9rem; }
        
        /* 弹窗样式改进：更宽、可滚动、错误提示更明显 */
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(3px); overflow-y:auto; }
        .modal-content { background-color:white; margin:5% auto; padding:1.8rem; width:90%; max-width:550px; border-radius:28px; box-shadow:0 20px 35px rgba(0,0,0,0.3); position:relative; animation:fadeInUp 0.3s ease; max-height:85vh; overflow-y:auto; }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(30px);} to { opacity:1; transform:translateY(0);} }
        .close { position:absolute; right:1.5rem; top:1rem; font-size:1.8rem; cursor:pointer; color:#aaa; transition:0.2s; }
        .close:hover { color:#0099ff; }
        .modal-content h2 { color:#0099ff; margin-bottom:1.2rem; text-align:center; font-size:1.8rem; }
        .modal-content label { font-weight:600; color:#2c3e66; margin-top:0.5rem; display:block; }
        .modal-content input, .modal-content select { width:100%; padding:0.8rem; margin:0.3rem 0 0.8rem; border:1px solid #ccc; border-radius:40px; font-size:1rem; outline:none; transition:0.2s; }
        .modal-content input:focus { border-color:#0099ff; box-shadow:0 0 0 2px rgba(0,153,255,0.2); }
        .modal-content button { width:100%; padding:0.8rem; margin:0.5rem 0; border-radius:40px; font-weight:bold; cursor:pointer; transition:0.2s; }
        .btn-primary-modal { background:#0099ff; color:white; border:none; }
        .btn-primary-modal:hover { background:#0077cc; transform:scale(1.02); }
        .btn-secondary-modal { background:white; border:1.5px solid #0099ff; color:#0099ff; }
        .btn-secondary-modal:hover { background:#e6f4ff; }
        .hr-text { text-align:center; margin:1rem 0; color:#aaa; position:relative; }
        .toggle-link { text-align:center; margin-top:1rem; font-size:0.9rem; }
        .toggle-link a { color:#0099ff; text-decoration:none; cursor:pointer; font-weight:500; }
        /* 更明显的错误提示 */
        .error-msg { background:#ffe6e6; border-left:5px solid #e74c3c; color:#c0392b; font-weight:bold; padding:0.6rem; margin:0.5rem 0; border-radius:8px; font-size:0.85rem; text-align:left; }
        
        .main-footer { background:#1e2a3e; color:#cbd5e1; padding:3rem 5% 1.5rem; margin-top:2rem; }
        .footer-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:2rem; margin-bottom:2rem; }
        .footer-col h4 { color:#0099ff; margin-bottom:1.2rem; font-size:1.2rem; }
        .footer-col p { margin-bottom:0.5rem; line-height:1.5; }
        .footer-col a { color:#cbd5e1; text-decoration:none; display:block; margin-bottom:0.6rem; transition:0.3s; }
        .footer-col a:hover { color:#0099ff; padding-left:5px; }
        .social-icons { display:flex; gap:1rem; margin-top:1rem; }
        .social-icons a { font-size:1.5rem; color:#cbd5e1; }
        .social-icons a:hover { color:#0099ff; }
        .footer-bottom { text-align:center; padding-top:1.5rem; border-top:1px solid #334155; font-size:0.85rem; }
        @media (max-width:768px) { 
            .hero { flex-direction:column; text-align:center; } 
            .nav-links { gap:1rem; } 
            .btn-outline,.btn-solid { padding:0.3rem 0.8rem; } 
            .footer-grid { grid-template-columns:1fr; text-align:center; } 
            .social-icons { justify-content:center; }
            .modal-content { width:95%; margin:10% auto; padding:1.5rem; max-height:80vh; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="#" class="active">Home</a>
        <a href="#">About Us</a>
        <?php if ($isLoggedIn && $user): ?>
            <span style="color:#0099ff;">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="dashboard.php">Dashboard</a>
            <button class="btn-outline" id="logoutNavBtn">Logout</button>
        <?php else: ?>
            <button class="btn-outline" id="loginBtn">Login</button>
            <button class="btn-solid" id="signupBtn">Sign Up</button>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-text">
        <h1>Your Health, Our Priority</h1>
        <p>Convenient clinic appointments, trusted care.<br>Book your visit in seconds.</p>
        <button class="btn-solid" style="padding:0.8rem 2rem; font-size:1rem;" id="heroBookBtn">Book Appointment Now →</button>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&auto=format" alt="Modern clinic interior">
    </div>
</section>

<section class="services">
    <h2>Our Services</h2>
    <div class="service-cards">
        <div class="card"><div class="card-icon">🏥</div><h3>General Consultation</h3><p>Expert diagnosis and treatment for common illnesses.</p></div>
        <div class="card"><div class="card-icon">🩺</div><h3>Health Screening</h3><p>Comprehensive check-ups to monitor your wellness.</p></div>
        <div class="card"><div class="card-icon">💉</div><h3>Vaccination</h3><p>Stay protected with flu, COVID-19 & travel vaccines.</p></div>
        <div class="card"><div class="card-icon">🚑</div><h3>Emergency Care</h3><p>Immediate attention for urgent medical needs.</p></div>
    </div>
</section>

<footer class="main-footer">
    <div class="footer-grid">
        <div class="footer-col"><h4>Contact Us</h4><p>📞 General Line: +603-1234 5678</p><p>✉️ Email: support@careconnect.com</p><p>💬 WhatsApp: +60 12-345 6789</p><div class="social-icons"><a href="#">📘</a><a href="#">📷</a><a href="#">🎵</a><a href="#">💼</a><a href="#">▶️</a></div></div>
        <div class="footer-col"><h4>Quick Links</h4><a href="#">Find a Doctor</a><a href="#">Book an Appointment</a><a href="#">Health Packages</a><a href="#">Campaigns & Promotions</a><a href="#">Latest Events</a><a href="#">Health Insights</a></div>
        <div class="footer-col"><h4>Corporate</h4><a href="#">Who We Are</a><a href="#">Board of Directors</a><a href="#">Annual Reports</a><a href="#">Careers</a><a href="#">Sustainability</a></div>
        <div class="footer-col"><h4>Our Services</h4><a href="#">Telemedicine</a><a href="#">Pharmacy Delivery</a><a href="#">Home Care</a><a href="#">Health Screening Centers</a></div>
    </div>
    <div class="footer-bottom"><p>© 2025 CareConnect | A trusted partner in healthcare. All rights reserved.</p><p>PDPA Notice | Terms & Conditions</p></div>
</footer>

<!-- 登录弹窗 -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeLogin">&times;</span>
        <h2>Login to Your Account</h2>
        <div id="loginPasswordMode">
            <input type="email" id="loginEmail" placeholder="Email address" autocomplete="email">
            <input type="password" id="loginPassword" placeholder="Password" autocomplete="current-password">
            <button class="btn-primary-modal" id="doPasswordLogin">Login with Password</button>
        </div>
        <div class="hr-text">———— OR ————</div>
        <div id="loginOtpMode">
            <input type="email" id="loginOtpEmail" placeholder="Email address">
            <button class="btn-secondary-modal" id="sendLoginOtpBtn" data-loading-text="Sending OTP...">Send OTP Code</button>
            <input type="text" id="loginOtpCode" placeholder="Enter 6-digit OTP" style="display:none;">
            <button class="btn-primary-modal" id="verifyLoginOtpBtn" data-loading-text="Verifying..." style="display:none;">Verify & Login</button>
        </div>
        <div class="toggle-link"><a id="switchToRegisterFromLogin">No account? Sign up</a></div>
        <div id="loginError" class="error-msg" style="display:none;"></div>
    </div>
</div>

<!-- 注册弹窗 -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeRegister">&times;</span>
        <h2>Register</h2>
        <label>Full Name (as per IC)</label>
        <input type="text" id="regName" placeholder="Enter your full name exactly as on IC">
        <label>Email</label>
        <input type="email" id="regEmail" placeholder="Type your email">
        <label>Password</label>
        <input type="password" id="regPassword" placeholder="Type your password">
        <label>NRIC/ Passport no.</label>
        <input type="text" id="regNric" placeholder="Type your NRIC">
        <label>Phone Number</label>
        <div style="display: flex; gap: 8px;">
            <select id="regPhoneCode" style="width: 30%;"><option value="+60">+60 (MY)</option><option value="+65">+65 (SG)</option><option value="+1">+1 (US)</option></select>
            <input type="tel" id="regPhone" placeholder="eg 12345678" style="width: 70%;">
        </div>
        <button class="btn-secondary-modal" id="sendRegCodeBtn" data-loading-text="Sending...">Send Code</button>
        <input type="text" id="regVerifyCode" placeholder="Verification code" style="display:none;">
        <button class="btn-secondary-modal" id="verifyRegCodeBtn" data-loading-text="Verifying..." style="display:none;">Verify</button>
        <button class="btn-primary-modal" id="registerFinalBtn" disabled>Register</button>
        <div class="toggle-link"><a id="switchToLoginFromRegister">Already have an account? Log in</a></div>
        <div id="regError" class="error-msg" style="display:none;"></div>
    </div>
</div>

<script>
    // 弹窗控制
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    function openLogin() { loginModal.style.display = 'block'; }
    function openRegister() { registerModal.style.display = 'block'; }
    function closeAllModals() { loginModal.style.display = 'none'; registerModal.style.display = 'none'; }

    // 安全绑定元素事件，避免 null 错误
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');
    const heroBookBtn = document.getElementById('heroBookBtn');
    const closeLogin = document.getElementById('closeLogin');
    const closeRegister = document.getElementById('closeRegister');
    const switchToRegister = document.getElementById('switchToRegisterFromLogin');
    const switchToLogin = document.getElementById('switchToLoginFromRegister');

    if (loginBtn) loginBtn.onclick = openLogin;
    if (signupBtn) signupBtn.onclick = openRegister;
    if (heroBookBtn) {
        heroBookBtn.onclick = () => {
            if(isLoggedIn()) window.location.href = 'book_appointment.php';
            else openLogin();
        };
    }
    if (closeLogin) closeLogin.onclick = () => loginModal.style.display = 'none';
    if (closeRegister) closeRegister.onclick = () => registerModal.style.display = 'none';
    window.onclick = (e) => { if(e.target === loginModal) loginModal.style.display = 'none'; if(e.target === registerModal) registerModal.style.display = 'none'; };

    if (switchToRegister) switchToRegister.onclick = (e) => { e.preventDefault(); closeAllModals(); openRegister(); };
    if (switchToLogin) switchToLogin.onclick = (e) => { e.preventDefault(); closeAllModals(); openLogin(); };

    function isLoggedIn() { return !!localStorage.getItem('current_user_id'); }

    // 自动检测 baseUrl
    function getBaseUrl() {
        let currentPath = window.location.pathname;
        if (currentPath.includes('/Customer_Module/')) {
            let root = currentPath.substring(0, currentPath.indexOf('/Customer_Module/') + 1);
            return root;
        }
        return './';
    }
    const baseUrl = getBaseUrl();
    console.log('[DEBUG] Base URL:', baseUrl);

    // 辅助函数：设置按钮加载状态
    function setButtonLoading(btn, isLoading) {
        if (!btn) return;
        if (isLoading) {
            if (!btn._originalText) btn._originalText = btn.innerText;
            const loadingText = btn.getAttribute('data-loading-text');
            btn.innerText = loadingText || 'Processing...';
            btn.disabled = true;
        } else {
            btn.innerText = btn._originalText || btn.innerText;
            btn.disabled = false;
        }
    }

    // 显示错误信息（自动5秒消失）
    function showError(element, message) {
        if (!element) return;
        element.innerText = message;
        element.style.display = 'block';
        setTimeout(() => {
            if (element) element.style.display = 'none';
        }, 5000);
    }

    // 注册相关元素
    const regName = document.getElementById('regName');
    const regEmail = document.getElementById('regEmail');
    const regPassword = document.getElementById('regPassword');
    const regNric = document.getElementById('regNric');
    const regPhoneCode = document.getElementById('regPhoneCode');
    const regPhone = document.getElementById('regPhone');
    const sendRegCodeBtn = document.getElementById('sendRegCodeBtn');
    const regVerifyCodeInput = document.getElementById('regVerifyCode');
    const verifyRegCodeBtn = document.getElementById('verifyRegCodeBtn');
    const registerFinalBtn = document.getElementById('registerFinalBtn');
    const regErrorSpan = document.getElementById('regError');

    let regStoredEmail = '';
    let regIsVerified = false;

    if (sendRegCodeBtn) {
        sendRegCodeBtn.onclick = async () => {
            const name = regName ? regName.value.trim() : '';
            const email = regEmail ? regEmail.value.trim() : '';
            const password = regPassword ? regPassword.value : '';
            const nric = regNric ? regNric.value.trim() : '';
            const phoneFull = (regPhoneCode ? regPhoneCode.value : '') + (regPhone ? regPhone.value.trim() : '');

            if (!name || !email || !password || !nric || !(regPhone && regPhone.value.trim())) {
                showError(regErrorSpan, "Please fill all fields before sending code.");
                return;
            }

            setButtonLoading(sendRegCodeBtn, true);
            try {
                const response = await fetch(baseUrl + 'send_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, type: 'register' })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    regStoredEmail = email;
                    regIsVerified = false;
                    showError(regErrorSpan, "Verification code sent to your email! Please enter it.");
                    if (regVerifyCodeInput) regVerifyCodeInput.style.display = 'block';
                    if (verifyRegCodeBtn) verifyRegCodeBtn.style.display = 'block';
                } else {
                    showError(regErrorSpan, data.message);
                }
            } catch (err) {
                console.error("Send OTP error:", err);
                showError(regErrorSpan, "Network error: Could not send code.");
            } finally {
                setButtonLoading(sendRegCodeBtn, false);
            }
        };
    }

    if (verifyRegCodeBtn) {
        verifyRegCodeBtn.onclick = async () => {
            const code = regVerifyCodeInput ? regVerifyCodeInput.value.trim() : '';
            const email = regStoredEmail;
            if (!email || !code) {
                showError(regErrorSpan, "Please enter the code.");
                return;
            }
            setButtonLoading(verifyRegCodeBtn, true);
            try {
                const response = await fetch(baseUrl + 'verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, code, type: 'register' })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    regIsVerified = true;
                    if (registerFinalBtn) registerFinalBtn.disabled = false;
                    showError(regErrorSpan, "Verified! You can now register.");
                } else {
                    showError(regErrorSpan, data.message);
                }
            } catch (err) {
                console.error("Verify OTP error:", err);
                showError(regErrorSpan, "Verification failed. Please try again.");
            } finally {
                setButtonLoading(verifyRegCodeBtn, false);
            }
        };
    }

    if (registerFinalBtn) {
        registerFinalBtn.onclick = async () => {
            if (!regIsVerified) {
                showError(regErrorSpan, "Please verify your code first.");
                return;
            }
            const name = regName ? regName.value.trim() : '';
            const email = regEmail ? regEmail.value.trim() : '';
            const password = regPassword ? regPassword.value : '';
            const nric = regNric ? regNric.value.trim() : '';
            const phoneFull = (regPhoneCode ? regPhoneCode.value : '') + (regPhone ? regPhone.value.trim() : '');
            const otpCode = regVerifyCodeInput ? regVerifyCodeInput.value.trim() : '';

            registerFinalBtn.disabled = true;
            registerFinalBtn.innerText = "Registering...";
            try {
                const response = await fetch(baseUrl + 'register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, name, password, nric, phone: phoneFull, otpCode })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    alert("Registration successful! Please login.");
                    closeAllModals();
                    if (regName) regName.value = '';
                    if (regEmail) regEmail.value = '';
                    if (regPassword) regPassword.value = '';
                    if (regNric) regNric.value = '';
                    if (regPhone) regPhone.value = '';
                    if (regVerifyCodeInput) regVerifyCodeInput.value = '';
                    if (registerFinalBtn) registerFinalBtn.disabled = true;
                    regIsVerified = false;
                    if (regErrorSpan) regErrorSpan.style.display = 'none';
                } else {
                    showError(regErrorSpan, data.message);
                }
            } catch (err) {
                console.error("Registration error:", err);
                showError(regErrorSpan, "Registration failed. Check console.");
            } finally {
                if (registerFinalBtn) {
                    registerFinalBtn.disabled = false;
                    registerFinalBtn.innerText = "Register";
                }
            }
        };
    }

    // 登录相关元素
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');
    const doPasswordLogin = document.getElementById('doPasswordLogin');
    const loginErrorSpan = document.getElementById('loginError');
    const loginOtpEmailInput = document.getElementById('loginOtpEmail');
    const sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn');
    const loginOtpCodeInput = document.getElementById('loginOtpCode');
    const verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');

    if (doPasswordLogin) {
        doPasswordLogin.onclick = async () => {
            const email = loginEmail ? loginEmail.value.trim() : '';
            const password = loginPassword ? loginPassword.value : '';
            if (!email || !password) {
                showError(loginErrorSpan, "Please enter email and password.");
                return;
            }
            doPasswordLogin.disabled = true;
            doPasswordLogin.innerText = "Logging in...";
            try {
                const response = await fetch(baseUrl + 'login_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    localStorage.setItem('current_user_id', 'session');
                    alert("Login successful!");
                    window.location.href = baseUrl + 'Customer_Module/dashboard.php';
                } else {
                    showError(loginErrorSpan, data.message);
                }
            } catch (err) {
                console.error("Password login error:", err);
                showError(loginErrorSpan, "Login failed. Check network.");
            } finally {
                doPasswordLogin.disabled = false;
                doPasswordLogin.innerText = "Login with Password";
            }
        };
    }

    if (sendLoginOtpBtn) {
        sendLoginOtpBtn.onclick = async () => {
            const email = loginOtpEmailInput ? loginOtpEmailInput.value.trim() : '';
            if (!email) {
                showError(loginErrorSpan, "Enter your registered email address.");
                return;
            }
            setButtonLoading(sendLoginOtpBtn, true);
            try {
                const response = await fetch(baseUrl + 'send_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, type: 'login' })
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                if (data.success) {
                    if (loginOtpCodeInput) loginOtpCodeInput.style.display = 'block';
                    if (verifyLoginOtpBtn) verifyLoginOtpBtn.style.display = 'block';
                    showError(loginErrorSpan, "OTP sent to your email!");
                } else {
                    showError(loginErrorSpan, data.message);
                }
            } catch (err) {
                console.error("Send login OTP error:", err);
                showError(loginErrorSpan, "Failed to send OTP.");
            } finally {
                setButtonLoading(sendLoginOtpBtn, false);
            }
        };
    }

    if (verifyLoginOtpBtn) {
        verifyLoginOtpBtn.onclick = async () => {
            const email = loginOtpEmailInput ? loginOtpEmailInput.value.trim() : '';
            const code = loginOtpCodeInput ? loginOtpCodeInput.value.trim() : '';
            if (!email || !code) {
                showError(loginErrorSpan, "Please enter the OTP code.");
                return;
            }
            setButtonLoading(verifyLoginOtpBtn, true);
            try {
                const verifyResp = await fetch(baseUrl + 'verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, code, type: 'login' })
                });
                if (!verifyResp.ok) throw new Error(`HTTP ${verifyResp.status}`);
                const verifyData = await verifyResp.json();
                if (!verifyData.success) {
                    showError(loginErrorSpan, verifyData.message);
                    return;
                }
                const loginResp = await fetch(baseUrl + 'login_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                if (!loginResp.ok) throw new Error(`HTTP ${loginResp.status}`);
                const loginData = await loginResp.json();
                if (loginData.success) {
                    localStorage.setItem('current_user_id', 'session');
                    alert("OTP login successful!");
                    window.location.href = baseUrl + 'Customer_Module/dashboard.php';
                } else {
                    showError(loginErrorSpan, loginData.message);
                }
            } catch (err) {
                console.error("OTP login error:", err);
                showError(loginErrorSpan, "OTP login failed.");
            } finally {
                setButtonLoading(verifyLoginOtpBtn, false);
            }
        };
    }

    // 退出登录（仅在元素存在时绑定）
    const logoutNavBtn = document.getElementById('logoutNavBtn');
    if (logoutNavBtn) {
        logoutNavBtn.onclick = async () => {
            await fetch(baseUrl + 'logout.php', { method: 'POST' });
            window.location.href = baseUrl + 'Customer_Module/homepage.php';
        };
    }
</script>
</body>
</html>