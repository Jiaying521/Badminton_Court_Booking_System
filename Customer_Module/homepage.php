<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareConnect | 诊所预约系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f8ff;
            color: #1e2a3e;
        }

        /* 导航栏 */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0099ff;
        }
        .logo span {
            color: #2c3e66;
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
            transition: 0.3s;
            padding-bottom: 4px;
        }
        .nav-links a:hover {
            color: #0099ff;
        }
        /* 当前页面高亮样式 */
        .nav-links a.active {
            color: #0099ff;
            font-weight: 600;
            border-bottom: 2px solid #0099ff;
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid #0099ff;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            color: #0099ff;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-outline:hover {
            background: #0099ff;
            color: white;
        }
        .btn-solid {
            background: #0099ff;
            border: none;
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-solid:hover {
            background: #0077cc;
            transform: scale(1.02);
        }

        /* Hero 区域 */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 3rem 5%;
            background: linear-gradient(135deg, #ffffff 0%, #e6f4ff 100%);
            flex-wrap: wrap;
        }
        .hero-text {
            flex: 1;
            min-width: 280px;
        }
        .hero-text h1 {
            font-size: 3rem;
            color: #0099ff;
            margin-bottom: 1rem;
        }
        .hero-text p {
            font-size: 1.2rem;
            color: #4a627a;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        .hero-image {
            flex: 1;
            text-align: center;
        }
        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 30px -10px rgba(0,153,255,0.2);
            width: 450px;
            object-fit: cover;
        }

        /* 服务卡片区 */
        .services {
            padding: 4rem 5%;
            text-align: center;
            background: white;
        }
        .services h2 {
            font-size: 2.2rem;
            color: #1e2a3e;
            margin-bottom: 2rem;
        }
        .service-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
        }
        .card {
            background: #f9fcff;
            border-radius: 16px;
            padding: 2rem 1.5rem;
            width: 260px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
            border-bottom: 3px solid #0099ff;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,153,255,0.15);
        }
        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .card h3 {
            color: #0099ff;
            margin-bottom: 0.8rem;
        }
        .card p {
            color: #5b6e8c;
            font-size: 0.9rem;
        }

        /* 弹窗基础样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(3px);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 450px;
            border-radius: 28px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            position: relative;
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .close {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.8rem;
            cursor: pointer;
            color: #aaa;
        }
        .close:hover { color: #0099ff; }
        .modal-content h2 {
            color: #0099ff;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0 1rem;
            border: 1px solid #ccc;
            border-radius: 40px;
            font-size: 1rem;
            outline: none;
        }
        .modal-content input:focus {
            border-color: #0099ff;
        }
        .modal-content button {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary-modal {
            background: #0099ff;
            color: white;
            border: none;
        }
        .btn-secondary-modal {
            background: white;
            border: 1px solid #0099ff;
            color: #0099ff;
        }
        .hr-text {
            text-align: center;
            margin: 1rem 0;
            color: #aaa;
        }
        .toggle-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .toggle-link a {
            color: #0099ff;
            text-decoration: none;
            cursor: pointer;
        }
        .error-msg {
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: -0.5rem;
            margin-bottom: 0.5rem;
        }

        /* 页脚样式 */
        .main-footer {
            background: #1e2a3e;
            color: #cbd5e1;
            padding: 3rem 5% 1.5rem;
            margin-top: 2rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .footer-col h4 {
            color: #0099ff;
            margin-bottom: 1.2rem;
            font-size: 1.2rem;
        }
        .footer-col p {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        .footer-col a {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            margin-bottom: 0.6rem;
            transition: 0.3s;
        }
        .footer-col a:hover {
            color: #0099ff;
            padding-left: 5px;
        }
        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .social-icons a {
            font-size: 1.5rem;
            color: #cbd5e1;
        }
        .social-icons a:hover {
            color: #0099ff;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #334155;
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
            }
            .nav-links {
                gap: 1rem;
            }
            .btn-outline, .btn-solid {
                padding: 0.3rem 0.8rem;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .social-icons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- 导航栏 -->
<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <!-- Home 链接添加 active 类，表示当前页面 -->
        <a href="#" class="active">Home</a>
        <a href="#">About Us</a>
        <button class="btn-outline" id="loginBtn">Login</button>
        <button class="btn-solid" id="signupBtn">Sign Up</button>
    </div>
</nav>

<!-- Hero 区域 -->
<section class="hero">
    <div class="hero-text">
        <h1>Your Health, Our Priority</h1>
        <p>Convenient clinic appointments, trusted care.<br>Book your visit in seconds.</p>
        <button class="btn-solid" style="padding: 0.8rem 2rem; font-size: 1rem;" id="heroBookBtn">Book Appointment Now →</button>
    </div>
    <div class="hero-image">
        <!-- 选用了现代诊所环境的图片，符合医疗主题 -->
        <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&auto=format" alt="Modern clinic interior">
    </div>
</section>

<!-- 服务区域 -->
<section class="services">
    <h2>Our Services</h2>
    <div class="service-cards">
        <div class="card">
            <div class="card-icon">🏥</div>
            <h3>General Consultation</h3>
            <p>Expert diagnosis and treatment for common illnesses.</p>
        </div>
        <div class="card">
            <div class="card-icon">🩺</div>
            <h3>Health Screening</h3>
            <p>Comprehensive check-ups to monitor your wellness.</p>
        </div>
        <div class="card">
            <div class="card-icon">💉</div>
            <h3>Vaccination</h3>
            <p>Stay protected with flu, COVID-19 & travel vaccines.</p>
        </div>
        <div class="card">
            <div class="card-icon">🚑</div>
            <h3>Emergency Care</h3>
            <p>Immediate attention for urgent medical needs.</p>
        </div>
    </div>
</section>

<!-- 页脚 -->
<footer class="main-footer">
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Contact Us</h4>
            <p>📞 General Line: +603-1234 5678</p>
            <p>✉️ Email: support@careconnect.com</p>
            <p>💬 WhatsApp: +60 12-345 6789</p>
            <div class="social-icons">
                <a href="#">📘</a>
                <a href="#">📷</a>
                <a href="#">🎵</a>
                <a href="#">💼</a>
                <a href="#">▶️</a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="#">Find a Doctor</a>
            <a href="#">Book an Appointment</a>
            <a href="#">Health Packages</a>
            <a href="#">Campaigns & Promotions</a>
            <a href="#">Latest Events</a>
            <a href="#">Health Insights</a>
        </div>
        <div class="footer-col">
            <h4>Corporate</h4>
            <a href="#">Who We Are</a>
            <a href="#">Board of Directors</a>
            <a href="#">Annual Reports</a>
            <a href="#">Careers</a>
            <a href="#">Sustainability</a>
        </div>
        <div class="footer-col">
            <h4>Our Services</h4>
            <a href="#">Telemedicine</a>
            <a href="#">Pharmacy Delivery</a>
            <a href="#">Home Care</a>
            <a href="#">Health Screening Centers</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2025 CareConnect | A trusted partner in healthcare. All rights reserved.</p>
        <p>PDPA Notice | Terms & Conditions</p>
    </div>
</footer>

<!-- ================= 登录弹窗（OTP 邮箱）================= -->
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
            <button class="btn-secondary-modal" id="sendLoginOtpBtn">Send OTP Code</button>
            <input type="text" id="loginOtpCode" placeholder="Enter 6-digit OTP" style="display:none;">
            <button class="btn-primary-modal" id="verifyLoginOtpBtn" style="display:none;">Verify & Login</button>
        </div>
        <div class="toggle-link">
            <a id="switchToRegisterFromLogin">No account? Sign up</a>
        </div>
        <div id="loginError" class="error-msg" style="text-align:center;"></div>
    </div>
</div>

<!-- ================= 注册弹窗 (OTP 邮箱) ================= -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeRegister">&times;</span>
        <h2>Register</h2>
        <label>Email</label>
        <input type="email" id="regEmail" placeholder="Type your email">
        
        <label>Password</label>
        <input type="password" id="regPassword" placeholder="Type your password">
        
        <label>NRIC/ Passport no.</label>
        <input type="text" id="regNric" placeholder="Type your NRIC">
        
        <label>Phone Number</label>
        <div style="display: flex; gap: 8px;">
            <select id="regPhoneCode" style="width: 30%;">
                <option value="+60">+60 (MY)</option>
                <option value="+65">+65 (SG)</option>
                <option value="+1">+1 (US)</option>
            </select>
            <input type="tel" id="regPhone" placeholder="eg 12345678" style="width: 70%;">
        </div>
        
        <button class="btn-secondary-modal" id="sendRegCodeBtn">Send Code</button>
        <input type="text" id="regVerifyCode" placeholder="Verification code">
        <button class="btn-secondary-modal" id="verifyRegCodeBtn">Verify</button>
        
        <button class="btn-primary-modal" id="registerFinalBtn" disabled>Register</button>
        <div class="toggle-link">
            <a id="switchToLoginFromRegister">Already have an account? Log in</a>
        </div>
        <div id="regError" class="error-msg" style="text-align:center;"></div>
    </div>
</div>

<script>
    // ---------- 弹窗控制 ----------
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    function openLogin() { loginModal.style.display = 'block'; }
    function openRegister() { registerModal.style.display = 'block'; }
    function closeAllModals() { loginModal.style.display = 'none'; registerModal.style.display = 'none'; }

    document.getElementById('loginBtn').onclick = openLogin;
    document.getElementById('signupBtn').onclick = openRegister;
    document.getElementById('heroBookBtn').onclick = () => {
        if(isLoggedIn()) alert("You are logged in. Redirect to booking page (demo).");
        else openLogin();
    };
    document.getElementById('closeLogin').onclick = () => loginModal.style.display = 'none';
    document.getElementById('closeRegister').onclick = () => registerModal.style.display = 'none';
    window.onclick = (e) => { if(e.target === loginModal) loginModal.style.display = 'none'; if(e.target === registerModal) registerModal.style.display = 'none'; };

    document.getElementById('switchToRegisterFromLogin').onclick = (e) => { e.preventDefault(); closeAllModals(); openRegister(); };
    document.getElementById('switchToLoginFromRegister').onclick = (e) => { e.preventDefault(); closeAllModals(); openLogin(); };

    function isLoggedIn() { return !!localStorage.getItem('current_user_id'); }

    // ========== 注册流程 (OTP) ==========
    let regStoredEmail = '';
    let regIsVerified = false;
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

    // 根据你的网站根目录修改 baseUrl，例如：http://localhost/CLINIC_BOOKING_SYSTEM/
    const baseUrl = window.location.origin + '/CLINIC_BOOKING_SYSTEM/';

    sendRegCodeBtn.onclick = async () => {
        const email = regEmail.value.trim();
        const password = regPassword.value;
        const nric = regNric.value.trim();
        const phoneFull = regPhoneCode.value + regPhone.value.trim();

        if (!email || !password || !nric || !regPhone.value.trim()) {
            regErrorSpan.innerText = "Please fill all fields before sending code.";
            return;
        }

        const response = await fetch(baseUrl + 'send_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, type: 'register' })
        });
        const data = await response.json();
        if (data.success) {
            regStoredEmail = email;
            regIsVerified = false;
            regErrorSpan.innerText = "Verification code sent to your email! Please enter it.";
            regVerifyCodeInput.style.display = 'block';
            verifyRegCodeBtn.style.display = 'block';
        } else {
            regErrorSpan.innerText = data.message;
        }
    };

    verifyRegCodeBtn.onclick = async () => {
        const code = regVerifyCodeInput.value.trim();
        const email = regStoredEmail;
        if (!email || !code) {
            regErrorSpan.innerText = "Please enter the code.";
            return;
        }
        const response = await fetch(baseUrl + 'verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, code, type: 'register' })
        });
        const data = await response.json();
        if (data.success) {
            regIsVerified = true;
            registerFinalBtn.disabled = false;
            regErrorSpan.innerText = "Verified! You can now register.";
        } else {
            regErrorSpan.innerText = data.message;
        }
    };

    registerFinalBtn.onclick = async () => {
        if (!regIsVerified) {
            regErrorSpan.innerText = "Please verify your code first.";
            return;
        }
        const email = regEmail.value.trim();
        const password = regPassword.value;
        const nric = regNric.value.trim();
        const phoneFull = regPhoneCode.value + regPhone.value.trim();
        const otpCode = regVerifyCodeInput.value.trim();

        const response = await fetch(baseUrl + 'register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, nric, phone: phoneFull, otpCode })
        });
        const data = await response.json();
        if (data.success) {
            alert("Registration successful! Please login.");
            closeAllModals();
            // 重置表单
            regEmail.value = ''; regPassword.value = ''; regNric.value = ''; regPhone.value = '';
            regVerifyCodeInput.value = ''; registerFinalBtn.disabled = true;
            regIsVerified = false;
            regErrorSpan.innerText = '';
        } else {
            regErrorSpan.innerText = data.message;
        }
    };

    // ========== 登录 ==========
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');
    const doPasswordLogin = document.getElementById('doPasswordLogin');
    const loginErrorSpan = document.getElementById('loginError');
    const loginOtpEmailInput = document.getElementById('loginOtpEmail');
    const sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn');
    const loginOtpCodeInput = document.getElementById('loginOtpCode');
    const verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');

    doPasswordLogin.onclick = async () => {
        const email = loginEmail.value.trim();
        const password = loginPassword.value;
        const response = await fetch(baseUrl + 'login_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await response.json();
        if (data.success) {
            localStorage.setItem('current_user_id', 'session');
            alert("Login successful!");
            location.reload();
        } else {
            loginErrorSpan.innerText = data.message;
        }
    };

    sendLoginOtpBtn.onclick = async () => {
        const email = loginOtpEmailInput.value.trim();
        if (!email) {
            loginErrorSpan.innerText = "Enter your registered email address.";
            return;
        }
        const response = await fetch(baseUrl + 'send_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, type: 'login' })
        });
        const data = await response.json();
        if (data.success) {
            loginOtpCodeInput.style.display = 'block';
            verifyLoginOtpBtn.style.display = 'block';
            loginErrorSpan.innerText = "OTP sent to your email!";
        } else {
            loginErrorSpan.innerText = data.message;
        }
    };

    verifyLoginOtpBtn.onclick = async () => {
        const email = loginOtpEmailInput.value.trim();
        const code = loginOtpCodeInput.value.trim();
        if (!email || !code) {
            loginErrorSpan.innerText = "Please enter the OTP code.";
            return;
        }
        // 验证 OTP
        const verifyResp = await fetch(baseUrl + 'verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, code, type: 'login' })
        });
        const verifyData = await verifyResp.json();
        if (!verifyData.success) {
            loginErrorSpan.innerText = verifyData.message;
            return;
        }
        // 验证通过后执行登录
        const loginResp = await fetch(baseUrl + 'login_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const loginData = await loginResp.json();
        if (loginData.success) {
            localStorage.setItem('current_user_id', 'session');
            alert("OTP login successful!");
            location.reload();
        } else {
            loginErrorSpan.innerText = loginData.message;
        }
    };

    // 登录后修改导航栏（简单示例）
    if(isLoggedIn()) {
        const navLinksDiv = document.querySelector('.nav-links');
        navLinksDiv.innerHTML = `
            <a href="#" class="active">Home</a>
            <a href="#">About Us</a>
            <span style="color:#0099ff;">Hi, User</span>
            <button class="btn-outline" id="logoutBtn">Logout</button>
            <button class="btn-solid" id="dashboardBtn">Dashboard</button>
        `;
        document.getElementById('logoutBtn')?.addEventListener('click', () => {
            localStorage.removeItem('current_user_id');
            location.reload();
        });
        document.getElementById('dashboardBtn')?.addEventListener('click', () => {
            alert("Customer dashboard (appointments, doctors, payments) coming soon. You are logged in.");
        });
    }
</script>
</body>
</html>