document.querySelector("form").addEventListener("submit", async function(e){
    e.preventDefault(); 

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const submitBtn = document.querySelector("input[type='submit']");

    // Prevent multiple clicks by disabling the button
    submitBtn.value = "Logging in...";
    submitBtn.disabled = true;

    try {
        const response = await fetch('LoginLogic.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ username, password })
        });

        const result = await response.json();

        if (result.success) {
            // Save username and userRole into LocalStorage for persistent sessions
            localStorage.setItem("loggedInUser", result.username); 
            localStorage.setItem("userRole", result.role);
            
            // Redirect to the unified dashboard page
            window.location.href = "SuperAdminDashboard.php"; 
        } else {
            // Display error message from server
            alert(result.message);
            submitBtn.value = "Login";
            submitBtn.disabled = false;
        }
    } catch (error) {
        // Handle server connection issues
        alert("Server error. Please try again later.");
        submitBtn.value = "Login";
        submitBtn.disabled = false;
    }
});