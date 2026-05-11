<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:500px; margin:0 auto; }
        .card { background:white; border-radius:32px; padding:2rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); }
        h1 { color:#2b7e3a; margin-bottom:0.5rem; }
        .back-link { display:inline-block; margin-top:1rem; color:#2b7e3a; text-decoration:none; }
        label { font-weight:600; display:block; margin-top:1rem; color:#1e2a2e; }
        input { width:100%; padding:0.8rem; margin-top:0.3rem; border:1.5px solid #dde4dc; border-radius:16px; background:#fefdf8; }
        input:focus { outline:none; border-color:#2b7e3a; }
        .strength-meter { margin-top:-0.8rem; margin-bottom:0.5rem; height:6px; background:#e0e0e0; border-radius:3px; overflow:hidden; }
        .strength-meter-fill { height:100%; width:0%; transition:width 0.2s; }
        .password-match { font-size:0.75rem; margin-top:0.2rem; }
        .valid { color:#2b7e3a; }
        .invalid { color:#e67e22; }
        .error-msg { background:#fee2dd; border-left:5px solid #e67e22; color:#b45f1b; padding:0.7rem; margin-top:1rem; border-radius:16px; font-size:0.85rem; display:none; }
        .success-msg { background:#d4edda; border-left:5px solid #2b7e3a; color:#155724; padding:0.7rem; margin-top:1rem; border-radius:16px; font-size:0.85rem; display:none; }
        button { background:#2b7e3a; color:white; border:none; padding:0.9rem; border-radius:50px; width:100%; font-weight:700; font-size:1rem; margin-top:1.5rem; cursor:pointer; transition:0.2s; }
        button:hover { background:#1f5a2a; transform:translateY(-2px); }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1><i class="fas fa-key"></i> Change Password</h1>
        <p>Change your account password</p>
        
        <form id="changePasswordForm">
            <label>Current Password</label>
            <input type="password" id="currentPassword" required>
            
            <label>New Password</label>
            <input type="password" id="newPassword" required>
            <div class="strength-meter"><div class="strength-meter-fill" id="strengthFill"></div></div>
            <div id="strengthText" class="strength-text" style="font-size:0.7rem; text-align:right;"></div>
            
            <label>Confirm New Password</label>
            <input type="password" id="confirmPassword" required>
            <div id="passwordMatch" class="password-match"></div>
            
            <div id="errorMsg" class="error-msg"></div>
            <div id="successMsg" class="success-msg"></div>
            
            <button type="submit">Update Password</button>
        </form>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</div>

<script>
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatchDiv = document.getElementById('passwordMatch');
    
    function checkPasswordStrength(pwd) {
        let score = 0;
        if(pwd.length >= 6) score++;
        if(pwd.length >= 8) score++;
        if(/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) score++;
        if(/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
        if(/\d/.test(pwd)) score++;
        if(pwd.length === 0) return { percent: 0, text: '', valid: false };
        let percent = 0, text = '', valid = false;
        if(score <= 2) { percent = 25; text = 'Weak'; valid = false; }
        else if(score === 3) { percent = 50; text = 'Fair'; valid = false; }
        else if(score === 4) { percent = 75; text = 'Good'; valid = true; }
        else { percent = 100; text = 'Strong'; valid = true; }
        const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(pwd);
        if(pwd.length >= 6 && hasSymbol && score >= 3) valid = true;
        else valid = false;
        if(pwd.length < 6) { text = 'Too short (min 6)'; valid = false; }
        else if(!hasSymbol) { text = 'Need at least 1 symbol (!@#$...)'; valid = false; }
        return { percent, text, valid };
    }
    
    newPassword.addEventListener('input', function() {
        const pwd = this.value;
        const result = checkPasswordStrength(pwd);
        strengthFill.style.width = result.percent + '%';
        if(result.percent <= 25) strengthFill.style.background = '#e67e22';
        else if(result.percent <= 50) strengthFill.style.background = '#f1c40f';
        else if(result.percent <= 75) strengthFill.style.background = '#2b7e3a';
        else strengthFill.style.background = '#2b7e3a';
        strengthText.innerText = result.text;
        
        if(confirmPassword.value.length > 0) {
            if(pwd === confirmPassword.value) {
                passwordMatchDiv.innerHTML = '<span class="valid">✓ Passwords match</span>';
            } else {
                passwordMatchDiv.innerHTML = '<span class="invalid">✗ Passwords do not match</span>';
            }
        }
    });
    
    confirmPassword.addEventListener('input', function() {
        if(this.value.length > 0 && this.value === newPassword.value) {
            passwordMatchDiv.innerHTML = '<span class="valid">✓ Passwords match</span>';
        } else if(this.value.length > 0) {
            passwordMatchDiv.innerHTML = '<span class="invalid">✗ Passwords do not match</span>';
        } else {
            passwordMatchDiv.innerHTML = '';
        }
    });
    
    document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword').value;
        const newPwd = newPassword.value;
        const confirmPwd = confirmPassword.value;
        
        if(newPwd !== confirmPwd) {
            showError('New passwords do not match');
            return;
        }
        
        const strength = checkPasswordStrength(newPwd);
        if(!strength.valid) {
            showError('Password does not meet requirements: ' + strength.text);
            return;
        }
        
        try {
            const res = await fetch('change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPwd,
                    confirm_password: confirmPwd
                })
            });
            const data = await res.json();
            if(data.success) {
                showSuccess('Password changed successfully!');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            } else {
                showError(data.message);
            }
        } catch(err) {
            showError('Network error');
        }
    });
    
    function showError(msg) {
        const errorDiv = document.getElementById('errorMsg');
        errorDiv.innerText = msg;
        errorDiv.style.display = 'block';
        setTimeout(() => errorDiv.style.display = 'none', 5000);
    }
    
    function showSuccess(msg) {
        const successDiv = document.getElementById('successMsg');
        successDiv.innerText = msg;
        successDiv.style.display = 'block';
        setTimeout(() => successDiv.style.display = 'none', 5000);
    }
</script>
</body>
</html>