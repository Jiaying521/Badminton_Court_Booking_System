// 1. SELECT ELEMENTS
const menuToggle = document.getElementById('menu-toggle');
const navMenu = document.getElementById('nav-menu');
const overlay = document.getElementById('overlay'); 
const logoutBtn = document.getElementById("logout-btn");
const welcomeText = document.getElementById('welcome-text');

// 2. SECURITY CHECK (ROUTE GUARD)
// Get the user from storage
const user = localStorage.getItem('loggedInUser');

// If no user is found, kick them out immediately
if (!user) {
    alert("Access Denied! Please log in first.");
    window.location.href = "LoginPage.php"; 
} else {
    // If user exists, show the welcome message
    welcomeText.innerText = "Hello, " + user + "!";
}

// 3. MOBILE MENU LOGIC
// Open Mobile Menu
menuToggle.addEventListener('click', () => {
    navMenu.classList.add('active');
    overlay.classList.add('active');
});

// Close Mobile Menu by clicking outside (overlay)
overlay.addEventListener('click', () => {
    navMenu.classList.remove('active');
    overlay.classList.remove('active');
});

// 4. LOGOUT FUNCTION WITH CONFIRMATION
logoutBtn.addEventListener("click", function() {
    
    // Display a browser confirmation box
    const isConfirmed = confirm("Are you sure you want to log out?");

    // If the user clicks "OK" (true)
    if (isConfirmed) {
        // Remove the user data from localStorage
        localStorage.removeItem("loggedInUser");

        // Redirect the user back to the login page
        window.location.href = "LoginPage.php";
    }
    // If "Cancel", nothing happens.
});