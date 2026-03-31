const submitBtn = document.getElementById('login-btn');
const forgotForm = document.getElementById('forgotPasswordForm');

// Initialize cooldown on page load
window.onload = function() {
    const cooldownEnd = localStorage.getItem('resetCooldownEnd');
    if (cooldownEnd) {
        const currentTime = Date.now();
        const remaining = Math.floor((cooldownEnd - currentTime) / 1000);

        if (remaining > 0) {
            startCooldown(remaining);
        } else {
            localStorage.removeItem('resetCooldownEnd');
        }
    }
};

forgotForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // STOPS the page from refreshing or redirecting
    const email = document.getElementById('emailInput').value;

    submitBtn.disabled = true;
    submitBtn.value = "Sending...";

    try {
        const response = await fetch('Email_System/SendResetLogic.php', { 
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ email })
        });
        
        const result = await response.json(); // Parse the JSON from PHP
        console.log("Response:", result); 

        if (response.ok && result.status === 'success') {
            // ONLY start cooldown if the email was actually sent
            alert(result.message);
            const cooldownTime = 60; 
            const endTime = Date.now() + (cooldownTime * 1000);
            localStorage.setItem('resetCooldownEnd', endTime);
            startCooldown(cooldownTime);
        } else {
            // Email not found or error occurred - DO NOT start cooldown
            alert(result.message || "Failed to process request.");
            submitBtn.disabled = false;
            submitBtn.value = "Send Reset Link";
        }
    } catch (error) {
        console.error("Fetch Error:", error);
        alert("Server error. Please try again later.");
        submitBtn.disabled = false;
        submitBtn.value = "Send Reset Link";
    }
});

function startCooldown(seconds) {
    submitBtn.disabled = true;
    let remaining = seconds;

    const timer = setInterval(() => {
        submitBtn.value = `Resend in ${remaining}s`;
        remaining--;

        if (remaining < 0) {
            clearInterval(timer);
            submitBtn.disabled = false;
            submitBtn.value = "Send Reset Link";
            localStorage.removeItem('resetCooldownEnd');
        }
    }, 1000);
}