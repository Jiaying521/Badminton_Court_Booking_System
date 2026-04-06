const passwordForm = document.getElementById('NewPasswordForm');
const submitBtn = document.getElementById('login-btn');

passwordForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // 1. Extract the token from the URL query string (?token=xxxx)
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    // Basic check for token existence
    if (!token) {
        alert("Invalid or missing reset token. Please request a new reset link.");
        return;
    }

    // 2. Client-side validation: Check if passwords match
    if (newPassword !== confirmPassword) {
        alert("Passwords do not match. Please try again.");
        return;
    }

    // 3. Client-side validation: Check password length & password strength
    const strongPasswordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    if (!strongPasswordRegex.test(newPassword)) {
        alert("Security requirement: At least 8 characters, including letters, numbers, and symbols (@$!%*?&).");
        return; 
    }

    // 4. Update UI Feedback for the user
    submitBtn.disabled = true;
    submitBtn.value = "Updating Password...";

    try {
        // 5. Send token and new password to the PHP logic
        const response = await fetch('UpdatePasswordLogic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                password: newPassword 
            })
        });

        const text = await response.text();
        console.log("Raw Server Response:", text); // Helpful for debugging
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            throw new Error("Invalid server response. The session might have timed out or a server error occurred.");
        }

        if (response.ok && result.status === 'success') {
            alert(result.message);
            // Redirect user back to the login page on success
            window.location.href = 'LoginPage.php'; 
        } else {
            // Display error message from PHP (e.g., token expired, invalid, OR REUSED PASSWORD)
            alert(result.message || "Failed to reset password.");
            submitBtn.disabled = false;
            submitBtn.value = "Set New Password";
        }

    } catch (error) {
        console.error("Fetch Error:", error);
        alert("Error: " + error.message);
        submitBtn.disabled = false;
        submitBtn.value = "Set New Password";
    }
});