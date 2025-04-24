<?php
// Database connection details
$host = "localhost"; // Change to your database host if needed
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$db_name = "travel_itinerary"; // Replace with your actual database name

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $db_name);

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
