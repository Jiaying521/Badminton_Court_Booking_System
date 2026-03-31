document.querySelector("form").addEventListener("submit", function(e){
    e.preventDefault(); // Prevent website form auto refresh

    const username = document.getElementById("username").value; //username = "user" (take username input value)
    const password = document.getElementById("password").value; //password = "1234" (take password input value)

    if(username === "user" && password === "1234"){
        localStorage.setItem("loggedInUser", username); // Store logged in user in local storage

        window.location.href = "SuperAdminDashboard.php"; // Jump to home page

    }else{
            alert("Invalid username or password. Please try again.");  // Show error message

    }
    
});