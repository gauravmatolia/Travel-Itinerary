<?php
// Assuming you have already set up the database connection
// Replace with your actual database connection details
$conn = new mysqli("localhost", "root", "", "travel_itinerary");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelGo</title>
    <link rel="stylesheet" href="assets/styles/home_styles.css">
</head>
<body>
    <div class="hero">
        <?php include('navbar.php'); ?>

        <div class="hero-content">
            <h1>Explore<br>India</h1>
        </div>
    </div>

    <!-- Second Division - Popular Tours -->
    <div class="tours-section">         
        <h2 class="section-title" style="border-top: 1px white solid; padding-top:20px;">Popular Tours</h2>         
        <div class="card-container">
            <?php
            // Fetch popular tours from the database
            $query = "SELECT tour_id, title, image_url FROM tours WHERE is_public = 1 ORDER BY rating DESC LIMIT 4"; // Limit to 4 popular tours
            $result = $conn->query($query);

            // Check if any tours are returned
            if ($result->num_rows > 0) {
                while ($tour = $result->fetch_assoc()) {
                    // For each tour, create a card
                    echo '<a href="tour_details.php?tour_id=' . $tour['tour_id'] . '" class="tour-card">';
                    echo '<img src="' . $tour['image_url'] . '" alt="' . $tour['title'] . '" class="card-img">';
                    echo '<div class="card-content">';
                    echo '<h3 class="card-title">' . $tour['title'] . '</h3>';
                    echo '</div>';
                    echo '</a>';
                }
            } else {
                echo "<p>No popular tours available at the moment.</p>";
            }
            ?>
        </div>     
    </div>

    <!-- Third Division - Explore Section -->
    <div class="explore-section">
        <div class="explore-content">
            <h2>Discover<br>India in<br>a New<br>Way</h2>
        </div>
    </div>
    <?php include 'footer.php'; ?>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
