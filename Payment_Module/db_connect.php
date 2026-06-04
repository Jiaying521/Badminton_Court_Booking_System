<?php
// Set up our local database credentials (XAMPP defaults)
$servername = "localhost"; // Runs right on your own laptop
$username = "root";        // XAMPP default admin username
$password = "";            // XAMPP default password is blank
$dbname = "badminton_hub"; // The exact name of your badminton project database

// Create a connection link to the MySQL server using the credentials above
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection link broke or failed
if ($conn->connect_error) {
    // If it fails, crash the page right here and print the exact error so we can debug it
    die("Connection failed: " . $conn->connect_error);
}
?>