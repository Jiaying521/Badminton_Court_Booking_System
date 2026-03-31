document.querySelector("form").addEventListener("submit", async function(e){
    e.preventDefault(); 

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const submitBtn = document.querySelector("input[type='submit']");

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
            localStorage.setItem("loggedInUser", result.username); 
            window.location.href = "SuperAdminDashboard.php"; 
        } else {
            alert(result.message);
            submitBtn.value = "Login";
            submitBtn.disabled = false;
        }
    } catch (error) {
        alert("Server error. Please try again later.");
        submitBtn.value = "Login";
        submitBtn.disabled = false;
    }
});