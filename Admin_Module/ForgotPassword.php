<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Care Connect Clinic</title>
    <link rel="stylesheet" href="LoginPage.css">
    <link rel="stylesheet" href="ForgotPassword.css">
</head>
<body>
    <h1>Care Connect Clinic</h1>
    
    <div class="login-box">
        <h2>Reset Password</h2>
        <p>Please enter your email to receive a reset link.</p>
        
        <form id="forgotPasswordForm">
            <input type="email" id="emailInput" placeholder="Enter your email" required>    
            <br><br>
            <input type="submit" id="login-btn" value="Send Reset Link">
        </form>

        <p>
            <a href="LoginPage.php">Back to Login</a>
        </p>
    </div>

    <script src="ForgotPassword.js"></script>
</body>
</html>