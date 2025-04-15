
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database config
    $host = 'localhost';
    $user = 'root';
    $db = 'travel_itinerary';
    $pass = '';

    // Create DB connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get form data
    $firstName = $conn->real_escape_string($_POST['firstName']);
    $lastName = $conn->real_escape_string($_POST['lastName']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);

    // Insert query
    $sql = "INSERT INTO contact_form (first_name, last_name, email, phone, subject, message)
            VALUES ('$firstName', '$lastName', '$email', '$phone', '$subject', '$message')";

    if ($conn->query($sql) === TRUE) {
        // Redirect back to the same page with a success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="assets/styles/contactUs_styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="tours.html">Tours</a></li>
            <li><a href="aboutus.html">About Us</a></li>
            <li><a href="contactus.html">Contact Us</a></li>
            <li><a href="signin.html">Sign In</a></li>
        </ul>
        <div class="search-icon"><img src="home_images/search0.svg" alt="search_icon"></div>
    </nav>
    <div class="line"></div>

    <!-- Contact Container -->
    <div class="contact-container">
        <!-- Left Side - Contact Information -->
        <div class="contact-info">
            <h2>Contact Information</h2>
            
            <div class="contact-details">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>+91 9876543210</span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@yoursite.com</span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 St, Anyplace, Anystate, Maharashtra</span>
                </div>
            </div>
            
            <div class="social-icons">
                <div class="social-icon">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-icon">
                    <i class="fab fa-twitter"></i>
                </div>
                <div class="social-icon">
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Contact Form -->
        <div class="contact-form">
            <form action="" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">FIRST NAME</label>
                        <input type="text" id="firstName" name="firstName" placeholder="John" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">LAST NAME</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Doe" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <input type="email" id="email" name="email" placeholder="yourname@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">PHONE NUMBER</label>
                        <input type="tel" id="phone" name="phone" placeholder="+1 2345 6789" required>
                    </div>
                </div>

                <div class="subject-options">
                    <label class="subject-label">SELECT SUBJECT?</label>
                    <div class="radio-group" required>
                        <div class="radio-option">
                            <input type="radio" id="generalEnquiry" name="subject" value="General Enquiry" required>
                            <label for="generalEnquiry">General Enquiry</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="bookingQuery" name="subject" value="Booking Query">
                            <label for="bookingQuery">Booking Query</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="feedbackReview" name="subject" value="Feedback/Review">
                            <label for="feedbackReview">Feedback/Review</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="others" name="subject" value="Others">
                            <label for="others">Others</label>
                        </div>
                    </div>
                </div>

                <div class="message-group">
                    <label for="message">MESSAGE</label>
                    <textarea id="message" name="message" placeholder="Write your message..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Message</button>
            </form>          
        </div>
    </div>
</body>
</html>

