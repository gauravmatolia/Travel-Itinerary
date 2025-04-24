<?php
// Start the session to access user data
session_start();

// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_itinerary";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize variables for form validation
$username = $email = $profile_image = "";
$username_err = $email_err = $password_err = $image_err = "";
$success_message = "";
$error = "";

// Fetch current user information
$user_query = "SELECT username, email, profile_image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Set current values
$username = $user_data['username'];
$email = $user_data['email'];
$current_profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : "assets/Images/user_images/default.jpeg";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Check if username already exists (skip the current user)
        $check_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $param_username, $user_id);
        $param_username = trim($_POST["username"]);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $username_err = "This username is already taken.";
        } else {
            $username = trim($_POST["username"]);
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email format is valid
        if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        } else {
            // Check if email already exists (skip the current user)
            $check_query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("si", $param_email, $user_id);
            $param_email = trim($_POST["email"]);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $email_err = "This email is already registered.";
            } else {
                $email = trim($_POST["email"]);
            }
        }
    }
    
    // Validate new password (optional)
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $password_err = "Password must have at least 6 characters.";
        } elseif ($new_password != $confirm_password) {
            $password_err = "Password did not match.";
        }
    }
    
    // Handle profile image upload
    $profile_image = $current_profile_image; // Default to current image
    
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES["profile_image"]["type"], $allowed_types)) {
            $image_err = "Only JPEG, JPG, PNG, and GIF files are allowed.";
        } elseif ($_FILES["profile_image"]["size"] > $max_file_size) {
            $image_err = "File size must be less than 5MB.";
        } else {
            // Define the root directory path for image storage
            $root_dir = $_SERVER['DOCUMENT_ROOT']; // Getting the root directory of the server
            $upload_rel_dir = "/assets/Images/user_images/"; // Relative path to images directory
            $upload_abs_dir = $root_dir . $upload_rel_dir; // Absolute path to images directory
            
            // Create directories recursively if they don't exist
            if (!file_exists($upload_abs_dir)) {
                $old_umask = umask(0); // Set umask to 0 to ensure proper permissions
                if (!mkdir($upload_abs_dir, 0777, true)) {
                    $image_err = "Failed to create directories for image upload.";
                }
                umask($old_umask); // Restore original umask
            }
            
            // Ensure the directory has proper permissions
            if (file_exists($upload_abs_dir) && !is_writable($upload_abs_dir)) {
                chmod($upload_abs_dir, 0777);
            }
            
            // Generate a unique filename
            $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = $user_id . "_" . time() . "." . $file_extension;
            $target_abs_file = $upload_abs_dir . $new_filename; // Absolute path for file writing
            $target_rel_file = $upload_rel_dir . $new_filename; // Relative path for database storage
            
            // Try to upload the file
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_abs_file)) {
                // File uploaded successfully, store the relative path in the database
                $profile_image = ltrim($target_rel_file, '/'); // Remove leading slash for consistency
                
                // Delete old profile image if it's not the default
                $old_file = $root_dir . '/' . $current_profile_image;
                if ($current_profile_image != "assets/Images/user_images/default.jpeg" && file_exists($old_file)) {
                    @unlink($old_file);
                }
            } else {
                // Get specific error details
                $error_details = error_get_last();
                $image_err = "Failed to upload image. Error: ";
                $image_err .= isset($error_details['message']) ? $error_details['message'] : "Unknown error";
                $image_err .= ". Tried to save to: " . $target_abs_file;
            }
        }
    }
    
    // Check input errors before updating the database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($image_err)) {
        
        // Prepare an update statement with or without password change
        if (!empty($new_password)) {
            $sql = "UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssi", $username, $email, $param_password, $profile_image, $user_id);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $email, $profile_image, $user_id);
        }
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            
            // Update session variables if needed
            $_SESSION['username'] = $username;
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TravelGo - Edit Profile</title>
  <link rel="stylesheet" href="assets/styles/profile_styles.css" />
  <style>
    .edit-profile-container {
      max-width: 800px;
      margin: 20px auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: black;
    }
    
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
    }
    
    .current-image {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 10px;
    }
    
    .btn-submit {
      background-color: #4CAF50;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .btn-cancel {
      background-color: #f44336;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      text-transform: uppercase;
      margin-right: 10px;
    }
    
    .error-text {
      color: #f44336;
      font-size: 14px;
      margin-top: 5px;
    }
    
    .success-message {
      background-color: #dff0d8;
      color: #3c763d;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .error-message {
      background-color: #f8d7da;
      color: #721c24;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    
    .button-group {
      margin-top: 30px;
      display: flex;
    }
  </style>
</head>
<body>
  <header>
    <nav>
      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="tours.php">Tours</a></li>
        <li><a href="aboutus.php">About Us</a></li>
        <li><a href="contactus.php">Contact Us</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
    <div class="line"></div>
  </header>

  <main>
    <div class="edit-profile-container">
      <h2 style="color: orange;">Edit Profile</h2><br><br>
      
      <?php if (!empty($success_message)): ?>
      <div class="success-message">
        <?php echo $success_message; ?>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($error)): ?>
      <div class="error-message">
        <?php echo $error; ?>
      </div>
      <?php endif; ?>
      
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label>Current Profile Image:</label>
          <div>
            <img src="<?php echo htmlspecialchars($current_profile_image); ?>" alt="Current Profile Image" class="current-image">
          </div>
          <label for="profile_image">Upload New Image:</label>
          <input type="file" name="profile_image" id="profile_image">
          <?php if (!empty($image_err)): ?>
            <span class="error-text"><?php echo $image_err; ?></span>
          <?php endif; ?>
          <small>Allowed formats: JPEG, JPG, PNG, GIF (Max size: 5MB)</small>
        </div>
        
        <div class="form-group">
          <label for="username">Username:</label>
          <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>">
          <?php if (!empty($username_err)): ?>
            <span class="error-text"><?php echo $username_err; ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
          <?php if (!empty($email_err)): ?>
            <span class="error-text"><?php echo $email_err; ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label for="new_password">New Password (leave blank to keep current):</label>
          <input type="password" name="new_password" id="new_password">
          <?php if (!empty($password_err)): ?>
            <span class="error-text"><?php echo $password_err; ?></span>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm New Password:</label>
          <input type="password" name="confirm_password" id="confirm_password">
        </div>
        
        <div class="button-group">
          <a href="profile.php" class="btn-cancel">Cancel</a>
          <button type="submit" class="btn-submit">Save Changes</button>
        </div>
      </form>
    </div>
  </main>
</body>
</html>