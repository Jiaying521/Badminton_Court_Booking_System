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
        }
        .nav-links a:hover {
            color: #0099ff;
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

        footer {
            text-align: center;
            padding: 2rem;
            background: #e6f4ff;
            color: #4a627a;
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
        }
    </style>
</head>
<body>

<!-- 导航栏 -->
<nav class="navbar">
    <div class="logo">Care<span>Connect</span></div>
    <div class="nav-links">
        <a href="#">Home</a>
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
        <!-- 通用诊所主题图片：干净明亮，微笑医生与患者，适合医疗场景 -->
        <img src="https://images.unsplash.com/photo-1666214280557-35e9a8a6a0e5?w=600&auto=format" alt="Clinic care">
        <!-- 若图片加载失败，备用：https://images.unsplash.com/photo-1579684385127-1ef15d508a02?w=600 也是诊所主题 -->
    </div>
</section>

<!-- 服务区域 - 通用诊所服务 -->
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

<footer>
    <p>© 2025 CareConnect | Compassionate healthcare, always within reach</p>
</footer>

<!-- ================= 登录弹窗 ================= -->
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
            <input type="tel" id="loginPhone" placeholder="Phone number (e.g., +60123456789)">
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

<!-- ================= 注册弹窗 (按照图片设计) ================= -->
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
    // ---------- 模拟数据存储 (localStorage) ----------
    let users = JSON.parse(localStorage.getItem('clinic_users')) || [];
    function saveUsers() { localStorage.setItem('clinic_users', JSON.stringify(users)); }

    // 模拟发送验证码
    function sendVerificationCode(contact, type) {
        const code = Math.floor(100000 + Math.random() * 900000);
        console.log(`[模拟${type}] 验证码: ${code}`);
        alert(`[模拟] 您的验证码是: ${code} (请查看控制台或此弹窗)`);
        return code;
    }

    // 弹窗控制
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

    // ========== 注册流程 ==========
    let regStep = 'form';
    let regStoredCode = null;
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

    sendRegCodeBtn.onclick = () => {
        let email = regEmail.value.trim();
        let phoneFull = regPhoneCode.value + regPhone.value.trim();
        if(!email || !regPassword.value || !regNric.value || !regPhone.value.trim()) {
            regErrorSpan.innerText = "Please fill all fields before sending code.";
            return;
        }
        if(users.find(u => u.email === email)) {
            regErrorSpan.innerText = "Email already registered.";
            return;
        }
        let code = sendVerificationCode(email, "REGISTRATION");
        regStoredCode = code;
        regStep = 'verify';
        regErrorSpan.innerText = "Verification code sent! Please enter it.";
        regVerifyCodeInput.style.display = 'block';
        verifyRegCodeBtn.style.display = 'block';
    };

    verifyRegCodeBtn.onclick = () => {
        let entered = regVerifyCodeInput.value.trim();
        if(entered == regStoredCode) {
            regStep = 'ready';
            registerFinalBtn.disabled = false;
            regErrorSpan.innerText = "Verified! You can now register.";
        } else {
            regErrorSpan.innerText = "Invalid verification code.";
        }
    };

    registerFinalBtn.onclick = () => {
        if(regStep !== 'ready') {
            regErrorSpan.innerText = "Please verify your code first.";
            return;
        }
        let email = regEmail.value.trim();
        let password = regPassword.value;
        let nric = regNric.value.trim();
        let phoneFull = regPhoneCode.value + regPhone.value.trim();
        if(users.find(u => u.email === email)) {
            regErrorSpan.innerText = "Email already exists!";
            return;
        }
        const newUser = {
            id: Date.now(),
            email: email,
            password: password,
            nric: nric,
            phone: phoneFull,
            profile: {}
        };
        users.push(newUser);
        saveUsers();
        alert("Registration successful! Please login.");
        closeAllModals();
        regEmail.value = ''; regPassword.value = ''; regNric.value = ''; regPhone.value = '';
        regVerifyCodeInput.value = ''; registerFinalBtn.disabled = true; regStep = 'form';
        regStoredCode = null;
        regErrorSpan.innerText = '';
    };

    // ========== 登录: 密码 & OTP ==========
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');
    const doPasswordLogin = document.getElementById('doPasswordLogin');
    const loginErrorSpan = document.getElementById('loginError');
    const loginPhoneInput = document.getElementById('loginPhone');
    const sendLoginOtpBtn = document.getElementById('sendLoginOtpBtn');
    const loginOtpCodeInput = document.getElementById('loginOtpCode');
    const verifyLoginOtpBtn = document.getElementById('verifyLoginOtpBtn');
    let loginOtpSessionCode = null;

    doPasswordLogin.onclick = () => {
        let email = loginEmail.value.trim();
        let pwd = loginPassword.value;
        let user = users.find(u => u.email === email && u.password === pwd);
        if(user) {
            localStorage.setItem('current_user_id', user.id);
            alert(`Welcome back, ${user.email}!`);
            loginModal.style.display = 'none';
            location.reload();
        } else {
            loginErrorSpan.innerText = "Invalid email or password.";
        }
    };

    sendLoginOtpBtn.onclick = () => {
        let phone = loginPhoneInput.value.trim();
        if(!phone) { loginErrorSpan.innerText = "Enter phone number with country code."; return; }
        let existingUser = users.find(u => u.phone === phone);
        if(!existingUser) {
            loginErrorSpan.innerText = "Phone number not registered. Please sign up.";
            return;
        }
        let code = sendVerificationCode(phone, "LOGIN OTP");
        loginOtpSessionCode = code;
        loginOtpCodeInput.style.display = 'block';
        verifyLoginOtpBtn.style.display = 'block';
        loginErrorSpan.innerText = "OTP sent!";
    };

    verifyLoginOtpBtn.onclick = () => {
        let entered = loginOtpCodeInput.value.trim();
        let phone = loginPhoneInput.value.trim();
        if(entered == loginOtpSessionCode) {
            let user = users.find(u => u.phone === phone);
            if(user) {
                localStorage.setItem('current_user_id', user.id);
                alert("OTP login successful!");
                loginModal.style.display = 'none';
                location.reload();
            } else {
                loginErrorSpan.innerText = "User not found.";
            }
        } else {
            loginErrorSpan.innerText = "Invalid OTP.";
        }
    };

    // 登录后修改导航栏
    if(isLoggedIn()) {
        const userId = localStorage.getItem('current_user_id');
        const user = users.find(u => u.id == userId);
        if(user) {
            const navLinksDiv = document.querySelector('.nav-links');
            navLinksDiv.innerHTML = `
                <a href="#">Home</a>
                <a href="#">About Us</a>
                <span style="color:#0099ff;">Hi, ${user.email.split('@')[0]}</span>
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
    }
</script>
</body>
</html>