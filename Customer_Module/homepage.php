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
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; }
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:1rem 5%; background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; border-bottom:1px solid rgba(43,126,58,0.2); }
        .logo { font-size:1.9rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .nav-links { display:flex; gap:2rem; align-items:center; }
        .btn-outline { background:transparent; border:1.5px solid #2b7e3a; padding:0.5rem 1.4rem; border-radius:40px; color:#2b7e3a; cursor:pointer; font-weight:600; transition:0.2s; }
        .btn-outline:hover { background:#2b7e3a; color:white; transform:translateY(-2px); }
        .btn-solid { background:#2b7e3a; border:none; padding:0.5rem 1.4rem; border-radius:40px; color:white; cursor:pointer; font-weight:600; transition:0.2s; }
        .btn-solid:hover { background:#1f5a2a; transform:translateY(-2px); }
        .hero { display:flex; align-items:center; justify-content:space-between; padding:4rem 5%; gap:2rem; flex-wrap:wrap; }
        .hero-text h1 { font-size:3.5rem; font-weight:800; background:linear-gradient(125deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; margin-bottom:1rem; }
        .hero-text p { font-size:1.2rem; color:#4a6e4a; margin-bottom:2rem; max-width:500px; }
        .hero-image img { max-width:100%; border-radius:32px; box-shadow:0 25px 40px -15px rgba(43,126,58,0.3); }
        .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(8px); }
        .modal-content { background:#fff; margin:5% auto; padding:2rem; width:90%; max-width:480px; border-radius:32px; position:relative; animation:fadeInUp 0.4s; border:1px solid rgba(43,126,58,0.1); }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .close { position:absolute; right:1.5rem; top:1.2rem; font-size:1.8rem; cursor:pointer; color:#94a3b8; }
        .close:hover { color:#2b7e3a; }
        .modal-content h2 { font-size:1.8rem; font-weight:700; text-align:center; margin-bottom:1.8rem; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .modal-content input, .modal-content select { width:100%; padding:0.9rem 1rem; margin:0.4rem 0 1rem; border:1.5px solid #dde4dc; border-radius:60px; background:#fefdf8; }
        .modal-content input:focus { border-color:#2b7e3a; outline:none; }
        .btn-primary-modal { background:linear-gradient(105deg,#2b7e3a,#1f5a2a); color:white; border:none; padding:0.9rem; border-radius:60px; width:100%; font-weight:700; cursor:pointer; margin-top:0.5rem; }
        .btn-secondary-modal { background:white; border:1.5px solid #2b7e3a; color:#2b7e3a; padding:0.9rem; border-radius:60px; width:100%; font-weight:600; cursor:pointer; margin-top:0.5rem; }
        .hr-text { text-align:center; margin:1rem 0; color:#94a3b8; position:relative; }
        .hr-text::before, .hr-text::after { content:''; position:absolute; top:50%; width:40%; height:1px; background:#dde4dc; }
        .hr-text::before { left:0; }
        .hr-text::after { right:0; }
        .toggle-link { text-align:center; margin-top:1.5rem; }
        .toggle-link a { color:#2b7e3a; text-decoration:none; font-weight:600; cursor:pointer; }
        .error-msg { background:#fee2dd; border-left:5px solid #e67e22; color:#b45f1b; padding:0.7rem; margin-top:1rem; border-radius:16px; font-size:0.85rem; display:none; }
        .footer { background:#142614; color:#cbd5c0; text-align:center; padding:2rem; margin-top:3rem; }
        @media (max-width:768px) { .hero-text h1 { font-size:2.5rem; } .navbar { flex-direction:column; gap:1rem; } }
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
        <p>Premium badminton courts, flexible hours, and secure online booking.</p>
        <button class="btn-solid" id="heroBookBtn">Book Now →</button>
    </div>
    <div class="hero-image"><img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600" alt="Badminton court"></div>
</section>
<footer class="footer"><p>© 2025 BadmintonHub – Your Game, Our Court</p></footer>

<!-- Login Modal -->
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

<!-- Register Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content"><span class="close" id="closeRegister">&times;</span><h2>Create Account</h2>
        <label>Full Name</label><input type="text" id="regName" placeholder="Your full name">
        <label>Email</label><input type="email" id="regEmail" placeholder="Your email">
        <label>Password</label><input type="password" id="regPassword" placeholder="Create password">
        <label>NRIC / Passport</label><input type="text" id="regNric" placeholder="Identification number">
        <label>Phone</label><div style="display:flex; gap:8px;"><select id="regPhoneCode" style="width:30%;"><option value="+60">+60 (MY)</option><option value="+65">+65 (SG)</option></select><input type="tel" id="regPhone" placeholder="12345678" style="width:70%;"></div>
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

    // Register flow
    let regEmailStored = '', regVerified = false;
    const regName = document.getElementById('regName'), regEmail = document.getElementById('regEmail'), regPassword = document.getElementById('regPassword'), regNric = document.getElementById('regNric'), regPhoneCode = document.getElementById('regPhoneCode'), regPhone = document.getElementById('regPhone'), sendRegCodeBtn = document.getElementById('sendRegCodeBtn'), regVerifyCode = document.getElementById('regVerifyCode'), verifyRegCodeBtn = document.getElementById('verifyRegCodeBtn'), registerFinalBtn = document.getElementById('registerFinalBtn'), regError = document.getElementById('regError');
    sendRegCodeBtn.onclick = async () => {
        const name = regName.value.trim(), email = regEmail.value.trim(), password = regPassword.value, nric = regNric.value.trim(), phoneFull = regPhoneCode.value + regPhone.value.trim();
        if(!name || !email || !password || !nric || !regPhone.value.trim()) { showError(regError, "Please fill all fields."); return; }
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
            if(data.success) { regVerified = true; registerFinalBtn.disabled = false; showError(regError, "Verified! You can now register."); }
            else showError(regError, data.message);
        } catch(err) { showError(regError, "Verification failed."); }
        finally { setButtonLoading(verifyRegCodeBtn, false); }
    };
    registerFinalBtn.onclick = async () => {
        if(!regVerified) { showError(regError, "Verify your email first."); return; }
        const name = regName.value.trim(), email = regEmail.value.trim(), password = regPassword.value, nric = regNric.value.trim(), phoneFull = regPhoneCode.value + regPhone.value.trim(), otpCode = regVerifyCode.value.trim();
        registerFinalBtn.disabled = true; registerFinalBtn.innerText = "Registering...";
        try {
            const res = await fetch(baseUrl+'register.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ email, name, password, nric, phone:phoneFull, otpCode }) });
            const data = await res.json();
            if(data.success) { alert("Registration successful! Please login."); closeAll(); regName.value = regEmail.value = regPassword.value = regNric.value = regPhone.value = regVerifyCode.value = ''; registerFinalBtn.disabled = true; regVerified = false; }
            else showError(regError, data.message);
        } catch(err) { showError(regError, "Registration failed."); }
        finally { registerFinalBtn.disabled = false; registerFinalBtn.innerText = "Register"; }
    };

    // Login with password
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
    // Login OTP
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