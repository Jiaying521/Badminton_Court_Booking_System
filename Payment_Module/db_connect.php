<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "badminton_hub"; // Make sure this matches your new badminton database!

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>