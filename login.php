<?php



$login_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "travel_itinerary";

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $email = $_POST["email"];
    $password_input = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password_input, $user["password"])) {
            $login_message = "Login successful! Welcome, " . htmlspecialchars($user["name"]) . ".";
            session_start();    
            $_SESSION['user_id'] = $user['user_id'];  // or any unique user identifier
            $_SESSION['username'] = $user['username']; // Optional
            header("Location: tours.php");
            exit();
        } else {
            $login_message = "Incorrect password.";
        }
    } else {
        $login_message = "No account found with that email.";
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
    <link rel="stylesheet" href="assets/styles/login_styles.css">
</head>
<body>
    <div class="container">
        <form class="login-form" action="" method="POST">
            <h2>LOG IN</h2>

            <?php if (!empty($login_message)) {
                $color = strpos($login_message, "successful") !== false ? "green" : "red";
                echo "<p style='color: $color;'>$login_message</p>";
            } ?>

            <div class="input-group">
                <label for="email">Email Id</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit">SUBMIT</button>
            
            <div class="alternative-login">
                <p>Other Login Options</p>
                <div class="login-icons">
                    <div class="login-icon">
                        <img src="/api/placeholder/18/18" alt="Microsoft" />
                    </div>
                    <div class="login-icon">
                        <img src="/api/placeholder/18/18" alt="Google" />
                    </div>
                    <div class="login-icon">
                        <img src="/api/placeholder/18/18" alt="Facebook" />
                    </div>
                </div>
            </div>
            <div class="signin-link">
                Don't have an account?<br>
                <a href="signin.php">Sign in here</a>
            </div>
        </form>
    </div>
</body>
</html>
