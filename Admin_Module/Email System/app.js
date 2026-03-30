// Run command: node app.js, then open http://localhost:3000/LoginPage.html to access the website.

const express = require('express');
const nodemailer = require('nodemailer');
const { v4: uuidv4 } = require('uuid');
const path = require('path'); // IMPORTANT: Needed for file paths

const app = express();

// 1. Middlewares
app.use(express.json());

// Tell Express to serve files from the PARENT folder (where your HTML files are)
app.use(express.static(path.join(__dirname, '..')));

console.log("Starting Hina's Clinic Server...");

// 2. Configure Gmail Transporter
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: "adminclinic2026@gmail.com",
        pass: "slkl oqmh ypkb dlvd" 
    }
});

// 3. Route for Forgot Password Request
app.post('/forgot-password', async (req, res) => {
    const { email } = req.body;
    const resetToken = uuidv4();
    const resetLink = `http://localhost:3000/reset-password?token=${resetToken}`;

    const mailOptions = {
        from: '"Hina\'s Clinic" <adminclinic2026@gmail.com>',
        to: email,
        subject: 'Password Reset Request',
        html: `
            <div style="font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;">
                <h2>Password Reset</h2>
                <p>We received a request to reset your password for Hina's Clinic System.</p>
                <p>Click the button below to choose a new password:</p>
                <a href="${resetLink}" style="display: inline-block; padding: 10px 20px; background-color: #9BD8FC; color: black; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Reset My Password
                </a>
                <p>If you did not request this, please ignore this email.</p>
                <hr>
                <p style="font-size: 12px; color: #777;">This link will expire in 1 hour.</p>
            </div>
        `
    };

    try {
        await transporter.sendMail(mailOptions);
        console.log("Reset email sent to: " + email);
        res.status(200).json({ message: "A reset link has been sent to " + email });
    } catch (error) {
        console.error("Email Error:", error);
        res.status(500).json({ message: "Failed to send email. Error: " + error.message });
    }
});

// 4. Route to handle the Reset Link click (Show the beautiful Page)
app.get('/reset-password', (req, res) => {
    // This sends the ResetPassword.html from the parent folder
    res.sendFile(path.join(__dirname, '..', 'ResetPassword.html'));
});

// 5. Route to save the new password
app.post('/update-password', (req, res) => {
    const { token, password } = req.body;

    console.log(`Updating password for Token: ${token}`);
    console.log(`New Password received: ${password}`);

    res.status(200).json({ 
        message: "Success! Your password has been updated. You can now login." 
    });
});

// 6. Start the Server
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`==========================================`);
    console.log(`Server is running at http://localhost:3000`);
    console.log(`Ready to send activate emails!`);
    console.log(`==========================================`);
});