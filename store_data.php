<?php
// Database connection details
$host = "localhost";  // Change if using an external database
$username = "root";   // Change if you have a different username
$password = "";       // Change if you have set a password
$database = "user_database";  // Your database name

// Create connection to MySQL (without selecting a database)
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it does not exist
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database created or already exists. <br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Create table if it does not exist
$sql_create_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mobile VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql_create_table) === TRUE) {
    echo "Table created or already exists. <br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $mobile = $_POST["mobile"];
    $password = $_POST["password"]; // Storing password as plain text (not recommended for production)

    // Prepare SQL query
    $sql = "INSERT INTO users (name, email, mobile, password) VALUES (?, ?, ?, ?)";

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $mobile, $password);

    if ($stmt->execute()) {
        echo "Sign-up successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
