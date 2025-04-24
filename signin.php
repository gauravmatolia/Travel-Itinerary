<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = "localhost"; 
    $username = "root";   
    $password = "";      
    $database = "travel_itinerary";  

    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->select_db($database);

    $sql_create_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        mobile VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL
    )";

    if (!$conn->query($sql_create_table)) {
        die("Error creating table: " . $conn->error);
    }

    $username = $_POST["name"];
    $email = $_POST["email"];
    $mobile = $_POST["mobile"];
    $password_plain = $_POST["password"];

    // Hash the password before saving to the database
    $hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, mobile, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $email, $mobile, $hashed_password);

    if ($stmt->execute()) {
        $success_message = "Sign-up successful!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelGo</title>
    <link rel="stylesheet" href="assets/styles/signin_styles.css">
</head>
<body>
    <div class="container">
        <form class="sign-in-form" action="" method="POST">
            <h2>SIGN IN</h2>

            <?php if (!empty($success_message)) { echo "<p style='color: green;'>$success_message</p>"; } ?>
            <?php if (!empty($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
            
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="Enter your name" required>
        
            <label for="email">Email Id</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
        
            <label for="mobile">Mobile Number</label>
            <input type="tel" id="mobile" name="mobile" placeholder="Enter your mobile number" required>
        
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        
            <div class="checkbox-container">
                <input type="checkbox" id="terms" required>
                <label for="terms">I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>.</label>
            </div>
        
            <button type="submit">SUBMIT</button>

            <div class="login-link">
                Already have an account?<br>
                <a href="login.php">Login in here</a>
            </div>
        </form>
    </div>
</body>
</html>
