<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Badminton Hub</title>
    <link rel="stylesheet" href="LoginPage.css">
    <link rel="stylesheet" href="ForgotPassword.css">

    <style>
        /* Control spacing between password input fields */
        #NewPasswordForm input[type="password"] {
            display: block;
            width: 80%;             
            margin: 0 auto 25px;  
        }

        /* Optional: reduce extra spacing from <br><br> if still used */
        #NewPasswordForm br {
            display: none;
        }
    </style>
    
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