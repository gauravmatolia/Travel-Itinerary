<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Assuming you've already established a database connection
require('db_connection.php');

// Get the tour_id from the URL or form
$tour_id = isset($_GET['tour_id']) ? $_GET['tour_id'] : null;

if ($tour_id) {
    // Fetch tour details from the database
    $tour_query = "SELECT * FROM tours WHERE tour_id = ?";
    $stmt = $conn->prepare($tour_query);
    $stmt->bind_param("i", $tour_id);
    $stmt->execute();
    $tour_result = $stmt->get_result();
    $tour = $tour_result->fetch_assoc();

    if (!$tour) {
        echo "Tour not found!";
        exit;
    }

    // Fetch locations for the tour
    $locations_query = "SELECT travel_locations.location_name, travel_locations.description, travel_locations.image_url 
                        FROM tour_locations 
                        JOIN travel_locations ON tour_locations.location_id = travel_locations.location_id 
                        WHERE tour_locations.tour_id = ?";
    $stmt = $conn->prepare($locations_query);
    $stmt->bind_param("i", $tour_id);
    $stmt->execute();
    $locations_result = $stmt->get_result();

    // Fetch reviews for the tour
    $reviews_query = "SELECT reviews.rating, reviews.comment, users.username, reviews.created_at 
                      FROM reviews 
                      JOIN users ON reviews.user_id = users.user_id 
                      WHERE reviews.tour_id = ? ORDER BY reviews.created_at DESC";
    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("i", $tour_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    
    // Assuming the user is logged in and their ID is stored in session
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
} else {
    echo "Tour ID is missing!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Details</title>
    <link rel="stylesheet" href="assets/styles/tours_details_styles.css">
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
}

body {
    background-color: #121212;
    color: #E0E0E0;
    line-height: 1.6;
}

/* Tour Details Section */
.tour-details {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 2rem;
    background-color: #1F1F1F;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.tour-details h1 {
    font-size: 2.8rem;
    color: #FFFFFF;
    margin-bottom: 1rem;
}

.tour-details .tour-image {
    width: 100%;
    height: auto;
    margin-bottom: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.tour-details p {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #B0B0B0;
}

.tour-details .tour-meta {
    display: flex;
    gap: 2rem;
    margin-top: 1.5rem;
    color: #B0B0B0;
}

.tour-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tour-meta .tour-rating {
    color: #FFD700;
}

/* Tour Locations Section */
.tour-locations {
    margin-top: 3rem;
    padding-bottom: 3rem;
}

.tour-locations h2 {
    font-size: 2rem;
    color: #FFFFFF;
    margin-bottom: 2rem;
}

.tour-locations ul {
    list-style-type: none;
    padding: 0;
}

.tour-locations li {
    margin-bottom: 2rem;
    background-color: #2C2C2C;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.tour-locations h3 {
    font-size: 1.8rem;
    color: #FFFFFF;
    margin-bottom: 1rem;
}

.tour-locations img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-top: 1rem;
}

/* Reviews Section */
.tour-reviews {
    margin-top: 3rem;
}

.tour-reviews h2 {
    font-size: 2rem;
    color: #FFFFFF;
    margin-bottom: 2rem;
}

.tour-reviews ul {
    list-style-type: none;
    padding: 0;
}

.tour-reviews li {
    margin-bottom: 2rem;
    background-color: #2C2C2C;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.tour-reviews p {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #B0B0B0;
}

.tour-reviews small {
    color: #777;
    font-size: 0.9rem;
}

.tour-reviews .review-form {
    margin-top: 2rem;
}

.tour-reviews .review-form textarea {
    width: 100%;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    border: 1px solid #444;
    font-size: 1rem;
    background-color: #333;
    color: #E0E0E0;
}

.tour-reviews .review-form select {
    padding: 0.5rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    border: 1px solid #444;
    font-size: 1rem;
    background-color: #333;
    color: #E0E0E0;
}

.tour-reviews .review-form button {
    padding: 1rem 2rem;
    background-color: #FF5722;
    color: white;
    border-radius: 4px;
    font-weight: bold;
    border: none;
    transition: background-color 0.3s;
}

.tour-reviews .review-form button:hover {
    background-color: #E64A19;
}

.tour-reviews .login-message {
    margin-top: 1rem;
    font-size: 1.1rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .tour-details h1 {
        font-size: 2.2rem;
    }

    .tour-meta {
        flex-direction: column;
        gap: 1rem;
    }

    .tour-locations h2, .tour-reviews h2 {
        font-size: 1.8rem;
    }

    .tour-locations ul, .tour-reviews ul {
        padding-left: 1rem;
    }

    .tour-locations li, .tour-reviews li {
        padding: 1rem;
    }
}

    </style>
</head>
<body>

<!-- Tour Details Section -->
<section class="tour-details">
    <h1><?php echo $tour['title']; ?></h1>
    <img src="<?php echo $tour['image_url']; ?>" alt="Tour Image" class="tour-image">
    <p><strong>Description:</strong> <?php echo $tour['description']; ?></p>
    <p><strong>Price:</strong> ₹<?php echo number_format($tour['price'], 2); ?></p>
    <p><strong>Start Date:</strong> <?php echo $tour['start_date']; ?></p>
    <p><strong>End Date:</strong> <?php echo $tour['end_date']; ?></p>
    <p><strong>Rating:</strong> <?php echo $tour['rating']; ?> ⭐</p>
</section>

<!-- Locations Section -->
<section class="tour-locations">
    <h2>Locations Included in the Tour</h2>
    <ul>
        <?php while ($location = $locations_result->fetch_assoc()): ?>
            <li>
                <h3><?php echo $location['location_name']; ?></h3>
                <p><?php echo $location['description']; ?></p>
                <img src="<?php echo $location['image_url']; ?>" alt="Location Image">
            </li>
        <?php endwhile; ?>
    </ul>
</section>

<!-- Reviews Section -->
<section class="tour-reviews">
    <h2>Reviews</h2>
    <?php if ($reviews_result->num_rows > 0): ?>
        <ul>
            <?php while ($review = $reviews_result->fetch_assoc()): ?>
                <li>
                    <p><strong><?php echo $review['username']; ?></strong> (Rating: <?php echo $review['rating']; ?>)</p>
                    <p><?php echo $review['comment']; ?></p>
                    <small>Reviewed on <?php echo $review['created_at']; ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No reviews yet. Be the first to review this tour!</p>
    <?php endif; ?>

    <!-- Add Review (if logged in) -->
    <?php if ($user_id): ?>
        <h3>Write a Review</h3>
        <form action="submit_review.php" method="POST">
            <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
            <label for="rating">Rating (1-5):</label>
            <select name="rating" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
            <br>
            <label for="comment">Your Review:</label>
            <textarea name="comment" rows="4" required></textarea>
            <br>
            <button type="submit">Submit Review</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to leave a review!</p>
    <?php endif; ?>
</section>

</body>
</html>
