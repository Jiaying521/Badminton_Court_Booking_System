<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Badminton Hub</title>
    <link rel="stylesheet" href="LoginPage.css">
    <link rel="stylesheet" href="ForgotPassword.css">
</head>
<body>
    <h1>🏸 Badminton Hub</h1>
    
    <div class="login-box">
        <h2>Reset Password</h2>
        <p>Enter your email address and we'll send you a reset link.</p>
        
        <form id="forgotPasswordForm">
            <input type="email" id="emailInput" placeholder="Enter your email" required>
            <input type="submit" id="login-btn" value="Send Reset Link">
        </form>

        <p>
            <a href="LoginPage.php">← Back to Login</a>
        </p>
    </div>

    <script src="ForgotPassword.js"></script>
</body>
</html>
