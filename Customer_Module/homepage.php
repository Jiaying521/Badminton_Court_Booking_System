<?php
require_once __DIR__ . '/../config.php';
// 注释掉自动跳转，让用户每次都要手动登录
// if (isLoggedIn()) redirect('dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BadmintonHub | Book Courts Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* 样式部分与之前相同，省略重复代码，保持原有风格 */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; line-height:1.5; }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:1rem 5%; background:rgba(255,255,255,0.95); backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; border-bottom:1px solid rgba(43,126,58,0.2); }
        .logo { font-size:1.9rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .nav-links { display:flex; gap:2rem; align-items:center; }
        .btn-outline { background:transparent; border:1.5px solid #2b7e3a; padding:0.5rem 1.4rem; border-radius:40px; color:#2b7e3a; cursor:pointer; font-weight:600; transition:all 0.2s; }
        .btn-outline:hover { background:#2b7e3a; color:white; transform:translateY(-2px); }
        .btn-solid { background:#2b7e3a; border:none; padding:0.5rem 1.4rem; border-radius:40px; color:white; cursor:pointer; font-weight:600; transition:all 0.2s; box-shadow:0 2px 6px rgba(43,126,58,0.2); }
        .btn-solid:hover { background:#1f5a2a; transform:translateY(-2px); }
        .hero { display:flex; align-items:center; justify-content:space-between; padding:4rem 5%; gap:3rem; flex-wrap:wrap; max-width:1400px; margin:0 auto; }
        .hero-text h1 { font-size:3.8rem; font-weight:800; background:linear-gradient(125deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; margin-bottom:1.2rem; }
        .hero-text p { font-size:1.2rem; color:#4a6e4a; margin-bottom:2rem; max-width:500px; }
        .hero-image img { max-width:100%; border-radius:32px; box-shadow:0 25px 40px -15px rgba(43,126,58,0.3); transition:transform 0.3s; }
        .hero-image img:hover { transform:scale(1.02); }
        .features { padding:4rem 5%; background:white; border-radius:48px 48px 0 0; margin-top:2rem; }
        .features h2 { text-align:center; font-size:2.5rem; font-weight:700; color:#1e3a2a; margin-bottom:3rem; }
        .features-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:2rem; max-width:1200px; margin:0 auto; }
        .feature-card { background:#fefdf8; border-radius:28px; padding:2rem; text-align:center; transition:0.3s; border:1px solid rgba(43,126,58,0.1); }
        .feature-card:hover { transform:translateY(-6px); border-color:rgba(43,126,58,0.3); box-shadow:0 16px 32px rgba(43,126,58,0.1); }
        .feature-icon { font-size:3rem; background:#eaf5e6; width:80px; height:80px; display:flex; align-items:center; justify-content:center; border-radius:60px; margin:0 auto 1.2rem; }
        .feature-card h3 { font-size:1.4rem; font-weight:700; color:#2b7e3a; margin-bottom:0.8rem; }
        .feature-card p { color:#5a6e5c; font-size:0.95rem; }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(8px); }
        .modal-content { background:#fff; margin:5% auto; padding:2rem; width:90%; max-width:480px; border-radius:32px; position:relative; animation:fadeInUp 0.4s; border:1px solid rgba(43,126,58,0.1); }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .close { position:absolute; right:1.5rem; top:1.2rem; font-size:1.8rem; cursor:pointer; color:#94a3b8; }
        .close:hover { color:#2b7e3a; }
        .modal-content h2 { font-size:1.8rem; font-weight:700; text-align:center; margin-bottom:1.8rem; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .modal-content input, .modal-content select { width:100%; padding:0.9rem 1rem; margin:0.4rem 0 1rem; border:1.5px solid #dde4dc; border-radius:60px; background:#fefdf8; font-family:'Inter',sans-serif; }
        .modal-content input:focus { border-color:#2b7e3a; outline:none; }
        .btn-primary-modal { background:linear-gradient(105deg,#2b7e3a,#1f5a2a); color:white; border:none; padding:0.9rem; border-radius:60px; width:100%; font-weight:700; cursor:pointer; margin-top:0.5rem; transition:0.2s; }
        .btn-primary-modal:hover { transform:translateY(-2px); box-shadow:0 6px 14px rgba(43,126,58,0.3); }
        .btn-secondary-modal { background:white; border:1.5px solid #2b7e3a; color:#2b7e3a; padding:0.9rem; border-radius:60px; width:100%; font-weight:600; cursor:pointer; margin-top:0.5rem; transition:0.2s; }
        .btn-secondary-modal:hover { background:#eaf5e6; transform:translateY(-2px); }
        .hr-text { text-align:center; margin:1rem 0; color:#94a3b8; position:relative; }
        .hr-text::before, .hr-text::after { content:''; position:absolute; top:50%; width:40%; height:1px; background:#dde4dc; }
        .hr-text::before { left:0; }
        .hr-text::after { right:0; }
        .toggle-link { text-align:center; margin-top:1.5rem; }
        .toggle-link a { color:#2b7e3a; text-decoration:none; font-weight:600; cursor:pointer; }
        .error-msg { background:#fee2dd; border-left:5px solid #e67e22; color:#b45f1b; padding:0.7rem; margin-top:1rem; border-radius:16px; font-size:0.85rem; display:none; }
        /* 密码强度条样式 */
        .strength-meter { margin-top: -0.8rem; margin-bottom: 1rem; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .strength-meter-fill { height: 100%; width: 0%; transition: width 0.2s, background 0.2s; border-radius: 3px; }
        .strength-text { font-size: 0.75rem; margin-top: 0.2rem; text-align: right; color: #5a6e5c; }
        /* 用户名实时验证状态 */
        .username-status { font-size: 0.75rem; margin-top: -0.8rem; margin-bottom: 0.5rem; }
        .username-valid { color: #2b7e3a; }
        .username-invalid { color: #e67e22; }
        .footer { background:#0f1f12; color:#cbd5c0; padding:3rem 5% 1.5rem; margin-top:4rem; }
        .footer-container { max-width:1400px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:2rem; margin-bottom:2rem; }
        .footer-col h3, .footer-col h4 { color:#2b7e3a; margin-bottom:1rem; }
        .footer-col p { margin-bottom:0.5rem; display:flex; align-items:center; gap:0.6rem; font-size:0.9rem; }
        .footer-col a { color:#cbd5c0; text-decoration:none; display:block; margin-bottom:0.6rem; transition:0.2s; font-size:0.9rem; }
        .footer-col a:hover { color:#2b7e3a; padding-left:5px; }
        .social-icons { display:flex; gap:1rem; margin-top:1rem; }
        .social-icons a { background:#2c4a2e; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:0.2s; }
        .social-icons a:hover { background:#2b7e3a; transform:translateY(-3px); }
        .footer-bottom { text-align:center; border-top:1px solid #2c4a2e; padding-top:1.5rem; font-size:0.85rem; }
        @media (max-width:768px) { .hero-text h1 { font-size:2.5rem; } .navbar { flex-direction:column; gap:1rem; } .features h2 { font-size:2rem; } .footer-container { grid-template-columns:1fr; text-align:center; } .footer-col p { justify-content:center; } .social-icons { justify-content:center; } }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">BadmintonHub</div>
    <div class="nav-links">
        <button class="btn-outline" id="loginBtn">Login</button>
        <button class="btn-solid" id="signupBtn">Sign Up</button>
    </div>
</nav>
<section class="hero">
    <div class="hero-text">
        <h1>Smash & Play<br>Book Courts Instantly</h1>
        <p>Premium badminton courts, flexible hours, and secure online booking. Play your best game today.</p>
        <button class="btn-solid" id="heroBookBtn" style="padding:0.8rem 2rem; font-size:1rem;">Book Now →</button>
    </div>
    <div class="hero-image"><img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600" alt="Badminton court"></div>
</section>
<section class="features">
    <h2>Why Choose BadmintonHub?</h2>
    <div class="features-grid">
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-calendar-check"></i></div><h3>Easy Booking</h3><p>Select court, pick time, pay online – done in under a minute.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-shuttlecock"></i></div><h3>Premium Courts</h3><p>Professional wooden floors, LED lighting, and top-notch facilities.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-tachometer-alt"></i></div><h3>Real-time Availability</h3><p>Live slot updates – never double-book again.</p></div>
        <div class="feature-card"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><h3>Secure Payments</h3><p>Multiple payment options with full encryption.</p></div>
    </div>
</section>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col"><h3>BadmintonHub</h3><p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, Kuala Lumpur</p><p><i class="fas fa-phone-alt"></i> +603-1234 5678</p><p><i class="fas fa-envelope"></i> support@badmintonhub.com</p><div class="social-icons"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-whatsapp"></i></a></div></div>
        <div class="footer-col"><h4>Quick Links</h4><a href="#">Find a Court</a><a href="#">Book Session</a><a href="#">Membership</a><a href="#">Coaching</a><a href="#">Tournaments</a></div>
        <div class="footer-col"><h4>Support</h4><a href="#">FAQs</a><a href="#">Cancellation Policy</a><a href="#">Privacy Policy</a><a href="#">Terms of Use</a><a href="#">Contact Us</a></div>
        <div class="footer-col"><h4>Operating Hours</h4><p>Monday - Friday: 8:00 AM - 10:00 PM</p><p>Saturday - Sunday: 9:00 AM - 9:00 PM</p><p>Public Holidays: 10:00 AM - 6:00 PM</p></div>
    </div>
    <div class="footer-bottom"><p>&copy; 2025 BadmintonHub – Your Game, Our Court. All rights reserved.</p></div>
</footer>

<!-- Login Modal (unchanged) -->
<div id="loginModal" class="modal">
    <div class="modal-content"><span class="close" id="closeLogin">&times;</span><h2>Welcome Back</h2>
        <div id="loginPasswordMode">
            <input type="email" id="loginEmail" placeholder="Email address">
            <input type="password" id="loginPassword" placeholder="Password">
            <button class="btn-primary-modal" id="doPasswordLogin">Login with Password</button>
        </div>
        <div class="hr-text">———— OR ————</div>
        <div id="loginOtpMode">
            <input type="email" id="loginOtpEmail" placeholder="Email address">
            <button class="btn-secondary-modal" id="sendLoginOtpBtn">Send OTP Code</button>
            <input type="text" id="loginOtpCode" placeholder="Enter 6-digit OTP" style="display:none;">
            <button class="btn-primary-modal" id="verifyLoginOtpBtn" style="display:none;">Verify & Login</button>
        </div>
        <div class="toggle-link"><a id="switchToRegisterFromLogin">No account? Sign up</a></div>
        <div id="loginError" class="error-msg"></div>
    </div>
</div>

<!-- Register Modal (modified) -->
<div id="registerModal" class="modal">
    <div class="modal-content"><span class="close" id="closeRegister">&times;</span><h2>Create Account</h2>
        <label>Name <span style="color:#e67e22;">*</span></label>
        <input type="text" id="regName" placeholder="Your display name">
        <div id="nameStatus" class="username-status"></div>

        <label>Email <span style="color:#e67e22;">*</span></label>
        <input type="email" id="regEmail" placeholder="Your email">

        <label>Password <span style="color:#e67e22;">*</span></label>
        <input type="password" id="regPassword" placeholder="At least 6 characters + 1 symbol">
        <div class="strength-meter"><div class="strength-meter-fill" id="passwordStrengthFill"></div></div>
        <div id="passwordStrengthText" class="strength-text"></div>

        <label>Phone <span style="color:#e67e22;">*</span></label>
        <div style="display:flex; gap:8px;">
            <select id="regPhoneCode" style="width:30%;"><option value="+60">+60 (MY)</option><option value="+65">+65 (SG)</option></select>
            <input type="tel" id="regPhone" placeholder="12345678" style="width:70%;">
        </div>

        <button class="btn-secondary-modal" id="sendRegCodeBtn">Send Code</button>
        <input type="text" id="regVerifyCode" placeholder="Verification code" style="display:none;">
        <button class="btn-secondary-modal" id="verifyRegCodeBtn" style="display:none;">Verify</button>
        <button class="btn-primary-modal" id="registerFinalBtn" disabled>Register</button>
        <div class="toggle-link"><a id="switchToLoginFromRegister">Already have an account? Log in</a></div>
        <div id="regError" class="error-msg"></div>
    </div>
</div>

<script>
    const baseUrl = './';
    // Modal elements
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    function openLogin() { loginModal.style.display = 'block'; }
    function openRegister() { registerModal.style.display = 'block'; }
    function closeAll() { loginModal.style.display = 'none'; registerModal.style.display = 'none'; }
    document.getElementById('loginBtn').onclick = openLogin;
    document.getElementById('signupBtn').onclick = openRegister;
    document.getElementById('heroBookBtn').onclick = openLogin;
    document.getElementById('closeLogin').onclick = () => loginModal.style.display = 'none';
    document.getElementById('closeRegister').onclick = () => registerModal.style.display = 'none';
    window.onclick = (e) => { if(e.target === loginModal) loginModal.style.display = 'none'; if(e.target === registerModal) registerModal.style.display = 'none'; };
    document.getElementById('switchToRegisterFromLogin').onclick = (e) => { e.preventDefault(); closeAll(); openRegister(); };
    document.getElementById('switchToLoginFromRegister').onclick = (e) => { e.preventDefault(); closeAll(); openLogin(); };

    function setButtonLoading(btn, isLoading) {
        if(!btn) return;
        if(isLoading) { btn._orig = btn.innerText; btn.innerText = btn.getAttribute('data-loading')||'Processing...'; btn.disabled=true; }
        else { btn.innerText = btn._orig; btn.disabled=false; }
    }
    function showError(el, msg) { el.innerText = msg; el.style.display = 'block'; setTimeout(()=>el.style.display='none',5000); }

    // ---------- 用户名唯一性实时检查 ----------
    const regNameInput = document.getElementById('regName');
    const nameStatusDiv = document.getElementById('nameStatus');
    let nameValid = false;
    regNameInput.addEventListener('blur', async function() {
        const name = this.value.trim();
        if(name.length < 2) {
            nameStatusDiv.innerHTML = '<span class="username-invalid">Name must be at least 2 characters</span>';
            nameValid = false;
            return;
        }
        try {
            const res = await fetch(baseUrl + 'check_username.php?name=' + encodeURIComponent(name));
            const data = await res.json();
            if(data.exists) {
                nameStatusDiv.innerHTML = '<span class="username-invalid">❌ This name is already taken</span>';
                nameValid = false;
            } else {
                nameStatusDiv.innerHTML = '<span class="username-valid">✓ Name available</span>';
                nameValid = true;
            }
        } catch(e) {
            nameStatusDiv.innerHTML = '<span class="username-invalid">Error checking name</span>';
            nameValid = false;
        }
    });

    // ---------- 密码强度检测 ----------
    const regPasswordInput = document.getElementById('regPassword');
    const strengthFill = document.getElementById('passwordStrengthFill');
    const strengthText = document.getElementById('passwordStrengthText');
    let passwordValid = false;

    function checkPasswordStrength(pwd) {
        let score = 0;
        if(pwd.length >= 6) score++;
        if(pwd.length >= 8) score++;
        if(/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) score++;
        if(/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
        if(/\d/.test(pwd)) score++;
        // 强度等级
        if(pwd.length === 0) return { percent: 0, text: '', valid: false };
        let percent = 0, text = '', valid = false;
        if(score <= 2) { percent = 25; text = 'Weak'; valid = false; }
        else if(score === 3) { percent = 50; text = 'Fair'; valid = false; }
        else if(score === 4) { percent = 75; text = 'Good'; valid = true; }
        else { percent = 100; text = 'Strong'; valid = true; }
        // 额外要求：至少6位且至少一个符号
        const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
        if(pwd.length >= 6 && hasSymbol && score >= 3) valid = true;
        else valid = false;
        if(pwd.length < 6) { text = 'Too short (min 6)'; valid = false; }
        else if(!hasSymbol) { text = 'Need at least 1 symbol (!@#$...)'; valid = false; }
        return { percent, text, valid };
    }

    regPasswordInput.addEventListener('input', function() {
        const pwd = this.value;
        const result = checkPasswordStrength(pwd);
        strengthFill.style.width = result.percent + '%';
        if(result.percent <= 25) strengthFill.style.background = '#e67e22';
        else if(result.percent <= 50) strengthFill.style.background = '#f1c40f';
        else if(result.percent <= 75) strengthFill.style.background = '#2b7e3a';
        else strengthFill.style.background = '#2b7e3a';
        strengthText.innerText = result.text;
        passwordValid = result.valid;
        validateRegisterButton();
    });

    // 全局验证注册按钮启用状态
    function validateRegisterButton() {
        const nameOk = nameValid;
        const emailOk = regEmail.value.trim().includes('@');
        const phoneOk = regPhone.value.trim().length > 5;
        const otpVerified = regVerified; // 全局变量
        const registerBtn = document.getElementById('registerFinalBtn');
        if(nameOk && emailOk && phoneOk && passwordValid && otpVerified) {
            registerBtn.disabled = false;
        } else {
            registerBtn.disabled = true;
        }
    }

    // 监听邮箱和手机变化
    const regEmail = document.getElementById('regEmail');
    const regPhone = document.getElementById('regPhone');
    regEmail.addEventListener('input', validateRegisterButton);
    regPhone.addEventListener('input', validateRegisterButton);

    // ---------- OTP 注册流程 ----------
    let regEmailStored = '', regVerified = false;
    const regName = regNameInput, regPassword = regPasswordInput, regPhoneCode = document.getElementById('regPhoneCode'), sendRegCodeBtn = document.getElementById('sendRegCodeBtn'), regVerifyCode = document.getElementById('regVerifyCode'), verifyRegCodeBtn = document.getElementById('verifyRegCodeBtn'), registerFinalBtn = document.getElementById('registerFinalBtn'), regError = document.getElementById('regError');

    sendRegCodeBtn.onclick = async () => {
        const name = regName.value.trim(), email = regEmail.value.trim(), password = regPassword.value, phoneFull = regPhoneCode.value + regPhone.value.trim();
        if(!nameValid) { showError(regError, "Please choose a valid unique name."); return; }
        if(!passwordValid) { showError(regError, "Password must be at least 6 characters and contain at least one symbol (!@#$...)."); return; }
        if(!name || !email || !password || !regPhone.value.trim()) { showError(regError, "Please fill all fields."); return; }
        setButtonLoading(sendRegCodeBtn, true);
        try {
            const res = await fetch(baseUrl+'send_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, type:'register' }) });
            const data = await res.json();
            if(data.success) { regEmailStored = email; regVerified = false; showError(regError, "OTP sent to your email."); regVerifyCode.style.display = 'block'; verifyRegCodeBtn.style.display = 'block'; }
            else showError(regError, data.message);
        } catch(err) { showError(regError, "Network error."); }
        finally { setButtonLoading(sendRegCodeBtn, false); }
    };
    verifyRegCodeBtn.onclick = async () => {
        const code = regVerifyCode.value.trim(), email = regEmailStored;
        if(!email || !code) { showError(regError, "Enter the code."); return; }
        setButtonLoading(verifyRegCodeBtn, true);
        try {
            const res = await fetch(baseUrl+'verify_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, code, type:'register' }) });
            const data = await res.json();
            if(data.success) { regVerified = true; showError(regError, "Verified! You can now register."); validateRegisterButton(); }
            else showError(regError, data.message);
        } catch(err) { showError(regError, "Verification failed."); }
        finally { setButtonLoading(verifyRegCodeBtn, false); }
    };
    registerFinalBtn.onclick = async () => {
        if(!regVerified) { showError(regError, "Verify your email first."); return; }
        if(!nameValid) { showError(regError, "Name is invalid or already taken."); return; }
        if(!passwordValid) { showError(regError, "Password does not meet requirements."); return; }
        const name = regName.value.trim(), email = regEmail.value.trim(), password = regPassword.value, phoneFull = regPhoneCode.value + regPhone.value.trim(), otpCode = regVerifyCode.value.trim();
        registerFinalBtn.disabled = true; registerFinalBtn.innerText = "Registering...";
        try {
            const res = await fetch(baseUrl+'register.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ name, email, password, phone:phoneFull, otpCode }) });
            const data = await res.json();
            if(data.success) { alert("Registration successful! Please login."); closeAll(); regName.value = regEmail.value = regPassword.value = regPhone.value = regVerifyCode.value = ''; registerFinalBtn.disabled = true; regVerified = false; nameValid = false; passwordValid = false; }
            else showError(regError, data.message);
        } catch(err) { showError(regError, "Registration failed."); }
        finally { registerFinalBtn.disabled = false; registerFinalBtn.innerText = "Register"; }
    };

    // Login with password (unchanged)
    const loginEmail = document.getElementById('loginEmail'), loginPassword = document.getElementById('loginPassword'), doPasswordLogin = document.getElementById('doPasswordLogin'), loginError = document.getElementById('loginError');
    doPasswordLogin.onclick = async () => {
        const email = loginEmail.value.trim(), password = loginPassword.value;
        if(!email || !password) { showError(loginError, "Enter email and password."); return; }
        doPasswordLogin.disabled = true; doPasswordLogin.innerText = "Logging in...";
        try {
            const res = await fetch(baseUrl+'login_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, password }) });
            const data = await res.json();
            if(data.success) { window.location.href = baseUrl+'dashboard.php'; }
            else showError(loginError, data.message);
        } catch(err) { showError(loginError, "Login failed."); }
        finally { doPasswordLogin.disabled = false; doPasswordLogin.innerText = "Login with Password"; }
    };
    // Login OTP (unchanged)
    const loginOtpEmail = document.getElementById('loginOtpEmail'), sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn'), loginOtpCode = document.getElementById('loginOtpCode'), verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');
    sendLoginOtpBtn.onclick = async () => {
        const email = loginOtpEmail.value.trim();
        if(!email) { showError(loginError, "Enter email."); return; }
        setButtonLoading(sendLoginOtpBtn, true);
        try {
            const res = await fetch(baseUrl+'send_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, type:'login' }) });
            const data = await res.json();
            if(data.success) { loginOtpCode.style.display = 'block'; verifyLoginOtpBtn.style.display = 'block'; showError(loginError, "OTP sent."); }
            else showError(loginError, data.message);
        } catch(err) { showError(loginError, "Failed to send OTP."); }
        finally { setButtonLoading(sendLoginOtpBtn, false); }
    };
    verifyLoginOtpBtn.onclick = async () => {
        const email = loginOtpEmail.value.trim(), code = loginOtpCode.value.trim();
        if(!email || !code) { showError(loginError, "Enter OTP."); return; }
        setButtonLoading(verifyLoginOtpBtn, true);
        try {
            const verifyRes = await fetch(baseUrl+'verify_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, code, type:'login' }) });
            const verifyData = await verifyRes.json();
            if(!verifyData.success) { showError(loginError, verifyData.message); return; }
            const loginRes = await fetch(baseUrl+'login_otp.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email }) });
            const loginData = await loginRes.json();
            if(loginData.success) window.location.href = baseUrl+'dashboard.php';
            else showError(loginError, loginData.message);
        } catch(err) { showError(loginError, "OTP login failed."); }
        finally { setButtonLoading(verifyLoginOtpBtn, false); }
    };
</script>
</body>
</html>