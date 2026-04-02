// 1. SELECT ELEMENTS
const menuToggle = document.getElementById('menu-toggle');
const navMenu = document.getElementById('nav-menu');
const logoutBtn = document.getElementById("logout-btn");
const welcomeText = document.getElementById('welcome-text');

// 2. SECURITY CHECK (ROUTE GUARD)
const user = localStorage.getItem('loggedInUser');

if (!user) {
    // Note: If you use PHP Sessions, this localStorage check might conflict.
    // Ensure you set 'loggedInUser' in localStorage during login.
    alert("Access Denied! Please log in first.");
    window.location.href = "LoginPage.php"; 
} else {
    welcomeText.innerText = "Hello, " + user + "!";
}

// 3. MOBILE MENU LOGIC
// Open/Toggle Menu
menuToggle.addEventListener('click', (e) => {
    e.stopPropagation(); // Stop click from reaching the document
    navMenu.classList.toggle('active');
});

// CLOSE MENU BY CLICKING BLANK SPACE (Anywhere outside the menu)
document.addEventListener('click', (event) => {
    const isClickInsideMenu = navMenu.contains(event.target);
    const isClickOnButton = menuToggle.contains(event.target);

    // If menu is open and user clicks outside the menu and toggle button
    if (navMenu.classList.contains('active') && !isClickInsideMenu && !isClickOnButton) {
        navMenu.classList.remove('active');
    }
});

// 4. LOGOUT FUNCTION WITH CONFIRMATION
logoutBtn.addEventListener("click", function() {
    const isConfirmed = confirm("Are you sure you want to log out?");

    if (isConfirmed) {
        localStorage.removeItem("loggedInUser");
        window.location.href = "LoginPage.php";
    }
});