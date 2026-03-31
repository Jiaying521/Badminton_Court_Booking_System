<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Care Connect Clinic Login Page</title>

    <link rel="stylesheet" href="LoginPage.css">
</head>

<body>
    <h1>Welcome to Care Connect Clinic</h1>
    
    <div class="login-box">
        <h2>Login as Admin </h2>
        <form action = " " method = "post">
            <input type = "text" id = "username" name = "username" placeholder="Username" required>
            <br><br>
            <input type = "password" id = "password" name = "password" placeholder="Password" required>
            <br><br>    
            <input type = "submit" id ="login-btn" value = "Login">
        </form>

        <p>
            <a href="ForgotPassword.php">Forgot Password?</a>
        </p>

    </div>

    <script src="LoginPage.js"></script> <!-- JavaScript File -->

</body>

</html>