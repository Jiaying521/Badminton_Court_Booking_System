<?php
// Customer_Module/homepage.php
require_once __DIR__ . '/../config.php';

// 如果已经登录，直接跳转到 dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$isLoggedIn = false;
$user = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>CareConnect | Advanced Healthcare Booking</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #f6fafd 0%, #eef2f8 100%);
            color: #1a2c3e;
            scroll-behavior: smooth;
        }

        /* 高级玻璃导航栏 */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(0, 153, 255, 0.1);
        }

        .logo {
            font-size: 1.9rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0099ff, #2c6e9e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .logo span {
            background: none;
            color: #2c3e66;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #2c3e66;
            font-weight: 500;
            transition: 0.2s;
            font-size: 0.95rem;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #0099ff;
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #0099ff;
            padding: 0.5rem 1.4rem;
            border-radius: 40px;
            color: #0099ff;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-outline:hover {
            background: #0099ff;
            color: white;
            box-shadow: 0 6px 14px rgba(0, 153, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-solid {
            background: #0099ff;
            border: none;
            padding: 0.5rem 1.4rem;
            border-radius: 40px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            box-shadow: 0 4px 8px rgba(0, 153, 255, 0.2);
        }

        .btn-solid:hover {
            background: #0077cc;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 153, 255, 0.3);
        }

        /* 英雄区 */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4rem 5%;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(125deg, #0099ff, #1e4a76);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.2rem;
            color: #4a627a;
            margin-bottom: 2rem;
            line-height: 1.5;
            max-width: 500px;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 32px;
            box-shadow: 0 25px 40px -15px rgba(0, 153, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .hero-image img:hover {
            transform: scale(1.02);
        }

        /* 服务区域 */
        .services {
            padding: 5rem 5%;
            background: white;
            border-radius: 48px 48px 0 0;
            margin-top: 2rem;
        }

        .services h2 {
            font-size: 2.6rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            color: #1e2a3e;
        }

        .services-sub {
            text-align: center;
            color: #5b6e8c;
            margin-bottom: 3rem;
            font-size: 1.1rem;
        }

        .service-category {
            margin-bottom: 3rem;
        }

        .service-category h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-left: 6px solid #0099ff;
            padding-left: 1rem;
            color: #1e2a3e;
        }

        .service-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
        }

        .card {
            background: #ffffff;
            border-radius: 28px;
            padding: 2rem 1.5rem;
            width: 260px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid rgba(0, 153, 255, 0.08);
            text-align: center;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 40px rgba(0, 153, 255, 0.12);
            border-color: rgba(0, 153, 255, 0.2);
        }

        .card-icon {
            font-size: 3.2rem;
            margin-bottom: 1rem;
            background: #eef7ff;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 60px;
            margin-left: auto;
            margin-right: auto;
        }

        .card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0099ff;
            margin-bottom: 0.6rem;
        }

        .card p {
            color: #5b6e8c;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* ========= 全新弹窗样式 ========= */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            overflow-y: auto;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 2rem 2rem 2.5rem;
            width: 90%;
            max-width: 480px;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            animation: fadeInUp 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid rgba(0, 153, 255, 0.1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 1.2rem;
            font-size: 1.8rem;
            cursor: pointer;
            color: #94a3b8;
            transition: 0.2s;
            line-height: 1;
        }

        .close:hover {
            color: #0099ff;
            transform: scale(1.1);
        }

        .modal-content h2 {
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.8rem;
            background: linear-gradient(135deg, #0099ff, #1e4a76);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .modal-content label {
            font-weight: 600;
            color: #1e2a3e;
            font-size: 0.9rem;
            margin-top: 0.8rem;
            display: block;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 0.9rem 1rem;
            margin: 0.4rem 0 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 60px;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background-color: #f8fafc;
        }

        .modal-content input:focus,
        .modal-content select:focus {
            outline: none;
            border-color: #0099ff;
            box-shadow: 0 0 0 3px rgba(0, 153, 255, 0.2);
            background-color: white;
        }

        .btn-primary-modal {
            background: linear-gradient(105deg, #0099ff, #0077cc);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 60px;
            width: 100%;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 0.5rem;
            box-shadow: 0 4px 10px rgba(0, 153, 255, 0.3);
        }

        .btn-primary-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 153, 255, 0.4);
        }

        .btn-secondary-modal {
            background: white;
            border: 1.5px solid #0099ff;
            color: #0099ff;
            padding: 0.9rem;
            border-radius: 60px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 0.5rem;
        }

        .btn-secondary-modal:hover {
            background: #eef7ff;
            transform: translateY(-2px);
        }

        .hr-text {
            text-align: center;
            margin: 1rem 0;
            color: #94a3b8;
            font-size: 0.85rem;
            position: relative;
        }

        .hr-text::before,
        .hr-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }

        .hr-text::before {
            left: 0;
        }

        .hr-text::after {
            right: 0;
        }

        .toggle-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .toggle-link a {
            color: #0099ff;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .toggle-link a:hover {
            text-decoration: underline;
        }

        .error-msg {
            background: #fee2e2;
            border-left: 5px solid #e74c3c;
            color: #c0392b;
            font-weight: 600;
            padding: 0.7rem;
            margin: 1rem 0 0;
            border-radius: 16px;
            font-size: 0.85rem;
            text-align: left;
        }

        /* 页脚 */
        .main-footer {
            background: #0f212e;
            color: #cbd5e1;
            padding: 3rem 5% 1.5rem;
            margin-top: 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-col h4 {
            color: #0099ff;
            margin-bottom: 1rem;
        }

        .footer-col a {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: 0.2s;
        }

        .footer-col a:hover {
            color: #0099ff;
            padding-left: 5px;
        }

        .footer-bottom {
            text-align: center;
            border-top: 1px solid #2c3e50;
            padding-top: 1.5rem;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            .hero-text h1 {
                font-size: 2.5rem;
            }
            .hero {
                text-align: center;
            }
            .service-cards {
                justify-content: center;
            }
            .modal-content {
                margin: 10% auto;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="#" class="active">Home</a>
        <a href="#">About Us</a>
        <button class="btn-outline" id="loginBtn">Login</button>
        <button class="btn-solid" id="signupBtn">Sign Up</button>
    </div>
</nav>

<section class="hero">
    <div class="hero-text">
        <h1>Your Health,<br>Our Precision</h1>
        <p>Seamless clinic appointments, AI‑powered recommendations, and compassionate care — all in one place.</p>
        <button class="btn-solid" style="padding:0.8rem 2rem; font-size:1rem;" id="heroBookBtn">Book Appointment Now →</button>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&auto=format" alt="Modern clinic">
    </div>
</section>

<!-- 扩展服务区域 -->
<section class="services">
    <h2>Beyond Boundaries</h2>
    <div class="services-sub">Comprehensive medical services tailored to your needs</div>

    <div class="service-category">
        <h3>🏥 Primary & Preventive Care</h3>
        <div class="service-cards">
            <div class="card"><div class="card-icon">🩺</div><h4>General Consultation</h4><p>Expert diagnosis & treatment for common illnesses</p></div>
            <div class="card"><div class="card-icon">💉</div><h4>Vaccination Hub</h4><p>Flu, COVID‑19, travel & childhood vaccines</p></div>
            <div class="card"><div class="card-icon">📊</div><h4>Health Screening</h4><p>Comprehensive wellness & early detection</p></div>
            <div class="card"><div class="card-icon">🍎</div><h4>Nutrition Counseling</h4><p>Personalized diet plans & lifestyle coaching</p></div>
        </div>
    </div>

    <div class="service-category">
        <h3>🩻 Specialist & Digital Care</h3>
        <div class="service-cards">
            <div class="card"><div class="card-icon">🦷</div><h4>Dental Care</h4><p>Cleaning, fillings, orthodontics & more</p></div>
            <div class="card"><div class="card-icon">🧠</div><h4>Mental Health</h4><p>Counseling, therapy & stress management</p></div>
            <div class="card"><div class="card-icon">💻</div><h4>Telemedicine</h4><p>Video consultations from home</p></div>
            <div class="card"><div class="card-icon">🦵</div><h4>Physiotherapy</h4><p>Rehabilitation & pain relief</p></div>
        </div>
    </div>

    <div class="service-category">
        <h3>🏠 Home & Convenience</h3>
        <div class="service-cards">
            <div class="card"><div class="card-icon">🏡</div><h4>Home Care Nursing</h4><p>Post‑op & chronic care at home</p></div>
            <div class="card"><div class="card-icon">📦</div><h4>Pharmacy Delivery</h4><p>Medicines delivered to your doorstep</p></div>
            <div class="card"><div class="card-icon">🧪</div><h4>Lab Tests at Home</h4><p>Sample collection & digital reports</p></div>
            <div class="card"><div class="card-icon">🚑</div><h4>Emergency Hotline</h4><p>24/7 urgent assistance & ambulance</p></div>
        </div>
    </div>
</section>

<footer class="main-footer">
    <div class="footer-grid">
        <div class="footer-col"><h4>Contact Us</h4><p>📞 +603-1234 5678</p><p>✉️ support@careconnect.com</p><p>💬 WhatsApp: +60 12-345 6789</p><div class="social-icons">📘 📷 🎵 💼 ▶️</div></div>
        <div class="footer-col"><h4>Quick Links</h4><a href="#">Find a Doctor</a><a href="#">Book Appointment</a><a href="#">Health Packages</a><a href="#">Telemedicine</a></div>
        <div class="footer-col"><h4>Corporate</h4><a href="#">About Us</a><a href="#">Careers</a><a href="#">Sustainability</a><a href="#">Press</a></div>
        <div class="footer-col"><h4>Legal</h4><a href="#">Privacy Policy</a><a href="#">Terms of Use</a><a href="#">PDPA Notice</a></div>
    </div>
    <div class="footer-bottom"><p>© 2025 CareConnect – Redefining Healthcare Experience</p></div>
</footer>

<!-- 登录弹窗 -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeLogin">&times;</span>
        <h2>Login to Your Account</h2>
        <div id="loginPasswordMode">
            <input type="email" id="loginEmail" placeholder="Your email address" autocomplete="email">
            <input type="password" id="loginPassword" placeholder="Your password" autocomplete="current-password">
            <button class="btn-primary-modal" id="doPasswordLogin">Login with Password</button>
        </div>
        <div class="hr-text">———— OR ————</div>
        <div id="loginOtpMode">
            <input type="email" id="loginOtpEmail" placeholder="Your email address">
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
        <h2>Create Account</h2>
        <label>Full Name (as per IC)</label>
        <input type="text" id="regName" placeholder="Enter your full name">
        <label>Email</label>
        <input type="email" id="regEmail" placeholder="Your email address">
        <label>Password</label>
        <input type="password" id="regPassword" placeholder="Create a strong password">
        <label>NRIC/ Passport no.</label>
        <input type="text" id="regNric" placeholder="NRIC number">
        <label>Phone Number</label>
        <div style="display: flex; gap: 8px;">
            <select id="regPhoneCode" style="width: 30%;"><option value="+60">+60 (MY)</option><option value="+65">+65 (SG)</option><option value="+1">+1 (US)</option></select>
            <input type="tel" id="regPhone" placeholder="12345678" style="width: 70%;">
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
    // 此页面仅供未登录用户，因此直接设置 isLoggedIn = false
    const isLoggedIn = false;
    const baseUrl = (() => {
        let path = window.location.pathname;
        if (path.includes('/Customer_Module/')) {
            return path.substring(0, path.indexOf('/Customer_Module/') + 1);
        }
        return './';
    })();
    console.log('[DEBUG] Base URL:', baseUrl);

    // 弹窗控制
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    function openLogin() { loginModal.style.display = 'block'; }
    function openRegister() { registerModal.style.display = 'block'; }
    function closeAllModals() { loginModal.style.display = 'none'; registerModal.style.display = 'none'; }

    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');
    const heroBookBtn = document.getElementById('heroBookBtn');
    if (loginBtn) loginBtn.onclick = openLogin;
    if (signupBtn) signupBtn.onclick = openRegister;
    if (heroBookBtn) heroBookBtn.onclick = () => { openLogin(); };

    document.getElementById('closeLogin').onclick = () => loginModal.style.display = 'none';
    document.getElementById('closeRegister').onclick = () => registerModal.style.display = 'none';
    window.onclick = (e) => { if(e.target === loginModal) loginModal.style.display = 'none'; if(e.target === registerModal) registerModal.style.display = 'none'; };
    document.getElementById('switchToRegisterFromLogin').onclick = (e) => { e.preventDefault(); closeAllModals(); openRegister(); };
    document.getElementById('switchToLoginFromRegister').onclick = (e) => { e.preventDefault(); closeAllModals(); openLogin(); };

    // 辅助函数
    function setButtonLoading(btn, isLoading) { if (!btn) return; if (isLoading) { if (!btn._originalText) btn._originalText = btn.innerText; const loadingText = btn.getAttribute('data-loading-text'); btn.innerText = loadingText || 'Processing...'; btn.disabled = true; } else { btn.innerText = btn._originalText || btn.innerText; btn.disabled = false; } }
    function showError(element, message) { if (!element) return; element.innerText = message; element.style.display = 'block'; setTimeout(() => { if (element) element.style.display = 'none'; }, 5000); }

    // 注册逻辑
    let regStoredEmail = '', regIsVerified = false;
    const regName = document.getElementById('regName'), regEmail = document.getElementById('regEmail'), regPassword = document.getElementById('regPassword'), regNric = document.getElementById('regNric'), regPhoneCode = document.getElementById('regPhoneCode'), regPhone = document.getElementById('regPhone'), sendRegCodeBtn = document.getElementById('sendRegCodeBtn'), regVerifyCodeInput = document.getElementById('regVerifyCode'), verifyRegCodeBtn = document.getElementById('verifyRegCodeBtn'), registerFinalBtn = document.getElementById('registerFinalBtn'), regErrorSpan = document.getElementById('regError');
    if (sendRegCodeBtn) sendRegCodeBtn.onclick = async () => { const name = regName?.value.trim() || '', email = regEmail?.value.trim() || '', password = regPassword?.value || '', nric = regNric?.value.trim() || '', phoneFull = (regPhoneCode?.value || '') + (regPhone?.value.trim() || ''); if (!name || !email || !password || !nric || !regPhone?.value.trim()) { showError(regErrorSpan, "Please fill all fields before sending code."); return; } setButtonLoading(sendRegCodeBtn, true); try { const response = await fetch(baseUrl + 'send_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, type: 'register' }) }); if (!response.ok) throw new Error(`HTTP ${response.status}`); const data = await response.json(); if (data.success) { regStoredEmail = email; regIsVerified = false; showError(regErrorSpan, "Verification code sent to your email! Please enter it."); if (regVerifyCodeInput) regVerifyCodeInput.style.display = 'block'; if (verifyRegCodeBtn) verifyRegCodeBtn.style.display = 'block'; } else { showError(regErrorSpan, data.message); } } catch (err) { console.error(err); showError(regErrorSpan, "Network error: Could not send code."); } finally { setButtonLoading(sendRegCodeBtn, false); } };
    if (verifyRegCodeBtn) verifyRegCodeBtn.onclick = async () => { const code = regVerifyCodeInput?.value.trim() || '', email = regStoredEmail; if (!email || !code) { showError(regErrorSpan, "Please enter the code."); return; } setButtonLoading(verifyRegCodeBtn, true); try { const response = await fetch(baseUrl + 'verify_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, code, type: 'register' }) }); const data = await response.json(); if (data.success) { regIsVerified = true; if (registerFinalBtn) registerFinalBtn.disabled = false; showError(regErrorSpan, "Verified! You can now register."); } else { showError(regErrorSpan, data.message); } } catch (err) { showError(regErrorSpan, "Verification failed. Please try again."); } finally { setButtonLoading(verifyRegCodeBtn, false); } };
    if (registerFinalBtn) registerFinalBtn.onclick = async () => { if (!regIsVerified) { showError(regErrorSpan, "Please verify your code first."); return; } const name = regName?.value.trim() || '', email = regEmail?.value.trim() || '', password = regPassword?.value || '', nric = regNric?.value.trim() || '', phoneFull = (regPhoneCode?.value || '') + (regPhone?.value.trim() || ''), otpCode = regVerifyCodeInput?.value.trim() || ''; registerFinalBtn.disabled = true; registerFinalBtn.innerText = "Registering..."; try { const response = await fetch(baseUrl + 'register.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, name, password, nric, phone: phoneFull, otpCode }) }); const data = await response.json(); if (data.success) { alert("Registration successful! Please login."); closeAllModals(); if (regName) regName.value = ''; if (regEmail) regEmail.value = ''; if (regPassword) regPassword.value = ''; if (regNric) regNric.value = ''; if (regPhone) regPhone.value = ''; if (regVerifyCodeInput) regVerifyCodeInput.value = ''; if (registerFinalBtn) registerFinalBtn.disabled = true; regIsVerified = false; if (regErrorSpan) regErrorSpan.style.display = 'none'; } else { showError(regErrorSpan, data.message); } } catch (err) { showError(regErrorSpan, "Registration failed. Check console."); } finally { registerFinalBtn.disabled = false; registerFinalBtn.innerText = "Register"; } };

    // 登录逻辑
    const loginEmail = document.getElementById('loginEmail'), loginPassword = document.getElementById('loginPassword'), doPasswordLogin = document.getElementById('doPasswordLogin'), loginErrorSpan = document.getElementById('loginError'), loginOtpEmailInput = document.getElementById('loginOtpEmail'), sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn'), loginOtpCodeInput = document.getElementById('loginOtpCode'), verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');
    if (doPasswordLogin) doPasswordLogin.onclick = async () => { const email = loginEmail?.value.trim() || '', password = loginPassword?.value || ''; if (!email || !password) { showError(loginErrorSpan, "Please enter email and password."); return; } doPasswordLogin.disabled = true; doPasswordLogin.innerText = "Logging in..."; try { const response = await fetch(baseUrl + 'login_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password }) }); const data = await response.json(); if (data.success) { localStorage.setItem('current_user_id', 'session'); alert("Login successful!"); window.location.href = baseUrl + 'Customer_Module/dashboard.php'; } else { showError(loginErrorSpan, data.message); } } catch (err) { showError(loginErrorSpan, "Login failed. Check network."); } finally { doPasswordLogin.disabled = false; doPasswordLogin.innerText = "Login with Password"; } };
    if (sendLoginOtpBtn) sendLoginOtpBtn.onclick = async () => { const email = loginOtpEmailInput?.value.trim() || ''; if (!email) { showError(loginErrorSpan, "Enter your registered email address."); return; } setButtonLoading(sendLoginOtpBtn, true); try { const response = await fetch(baseUrl + 'send_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, type: 'login' }) }); const data = await response.json(); if (data.success) { if (loginOtpCodeInput) loginOtpCodeInput.style.display = 'block'; if (verifyLoginOtpBtn) verifyLoginOtpBtn.style.display = 'block'; showError(loginErrorSpan, "OTP sent to your email!"); } else { showError(loginErrorSpan, data.message); } } catch (err) { showError(loginErrorSpan, "Failed to send OTP."); } finally { setButtonLoading(sendLoginOtpBtn, false); } };
    if (verifyLoginOtpBtn) verifyLoginOtpBtn.onclick = async () => { const email = loginOtpEmailInput?.value.trim() || '', code = loginOtpCodeInput?.value.trim() || ''; if (!email || !code) { showError(loginErrorSpan, "Please enter the OTP code."); return; } setButtonLoading(verifyLoginOtpBtn, true); try { const verifyResp = await fetch(baseUrl + 'verify_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, code, type: 'login' }) }); const verifyData = await verifyResp.json(); if (!verifyData.success) { showError(loginErrorSpan, verifyData.message); return; } const loginResp = await fetch(baseUrl + 'login_otp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email }) }); const loginData = await loginResp.json(); if (loginData.success) { localStorage.setItem('current_user_id', 'session'); alert("OTP login successful!"); window.location.href = baseUrl + 'Customer_Module/dashboard.php'; } else { showError(loginErrorSpan, loginData.message); } } catch (err) { showError(loginErrorSpan, "OTP login failed."); } finally { setButtonLoading(verifyLoginOtpBtn, false); } };
</script>
</body>
</html>