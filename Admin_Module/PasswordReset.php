<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Badminton Hub</title>
    <link rel="stylesheet" href="LoginPage.css">
    <link rel="stylesheet" href="ForgotPassword.css">
</head>
<body>
    <h1>Password Reset</h1>

    <div class="login-box">
        <h2>Please enter your new password.</h2>
        
        <form id="NewPasswordForm">
            <!-- New Password Input -->
            <input type="password" id="newPassword" placeholder="New password" required>    
            <br><br>
            <!-- Confirm Password Input -->
            <input type="password" id="confirmPassword" placeholder="Confirm password" required>
            <br><br>
            <!-- Submit Button -->
            <input type="submit" id="login-btn" value="Set New Password">
        </form>
    </div>

    <!-- Link to external JavaScript -->
    <script src="PasswordReset.js"></script>
</body>
</html>