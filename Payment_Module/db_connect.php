<?php

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password is blank
$dbname = "clinic_db";

// Create the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection works
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    print "Database connected successfully! ";
}
?>