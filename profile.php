<?php
// Start the session to access user data
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Fetch user information
$user_query = "SELECT username, email, profile_image, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Default profile image if none is set
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : "assets/Images/user_images/default.jpeg";

// Fetch favorite locations
$favorites_query = "SELECT tl.location_name, tl.image_url 
                   FROM favorites f
                   JOIN travel_locations tl ON f.location_id = tl.location_id
                   WHERE f.user_id = ? 
                   LIMIT 3";
$stmt = $conn->prepare($favorites_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites_result = $stmt->get_result();
$favorite_places = [];
while ($row = $favorites_result->fetch_assoc()) {
    $favorite_places[] = $row;
}

// Fetch upcoming trips (tours with start_date in the future)
// Fetch upcoming trips (booked by the user, with start_date in the future)
// $upcoming_trips_query = "SELECT t.title, t.start_date, t.end_date
//                         FROM bookings b
//                         JOIN tours t ON b.tour_id = t.tour_id
//                         WHERE b.user_id = ? AND t.start_date > CURDATE()
//                         ORDER BY t.start_date ASC
//                         LIMIT 2";
// $stmt = $conn->prepare($upcoming_trips_query);
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $upcoming_trips_result = $stmt->get_result();
// $upcoming_trips = [];
// while ($row = $upcoming_trips_result->fetch_assoc()) {
//     $upcoming_trips[] = $row;
// }

// Fetch upcoming trips (tours or itineraries) using the final_bookings view
$upcoming_trips_query = "
    SELECT 
        fb.item_title, 
        COALESCE(t.start_date, fb.start_date) AS start_date, 
        COALESCE(t.end_date, fb.end_date) AS end_date,
        fb.booking_type
    FROM final_bookings fb
    LEFT JOIN tours t 
        ON fb.booking_type = 'tour' COLLATE utf8mb4_unicode_ci AND fb.item_id = t.tour_id
    WHERE fb.user_id = ? 
      AND COALESCE(t.start_date, fb.start_date) > CURDATE()
    ORDER BY start_date ASC
    LIMIT 2
";


$stmt = $conn->prepare($upcoming_trips_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_trips_result = $stmt->get_result();
$upcoming_trips = [];

while ($row = $upcoming_trips_result->fetch_assoc()) {
    $upcoming_trips[] = $row;
}


// Fetch user memories grouped by location/caption
$memories_query = "SELECT image_url, caption
                  FROM memories
                  WHERE user_id = ?
                  ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($memories_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$memories_result = $stmt->get_result();
$memories = [];
while ($row = $memories_result->fetch_assoc()) {
    $caption = !empty($row['caption']) ? $row['caption'] : "My Travels";
    if (!isset($memories[$caption])) {
        $memories[$caption] = [];
    }
    $memories[$caption][] = $row['image_url'];
}

// Close the database connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TravelGo - My Profile</title>
  <link rel="stylesheet" href="assets/styles/profile_styles.css"/>
  <style>.profile-details {
  max-width: 600px;
  background-color: rgba(255, 255, 255, 0.05); /* Light transparent box */
  padding: 20px 25px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
  transition: transform 0.2s ease;
}

.profile-details:hover {
  transform: scale(1.02);
}

.profile-details h2 {
  font-size: 28px;
  margin-bottom: 12px;
  color: #ffffff;
  font-weight: 600;
}

.profile-details p {
  font-size: 15px;
  color: #e0e0e0;
  line-height: 1.6;
  margin-bottom: 15px;
}

.edit-profile-btn {
  display: inline-block;
  padding: 10px 20px;
  background-color: #fd8c5a;
  color: #fff;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

.edit-profile-btn:hover {
  background-color: #ffaf7c;
  transform: translateY(-2px);
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
    <section class="profile-section">
      <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Pic" class="profile-pic" />
      <div class="profile-details">
        <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
        <p>
          Email: <?php echo htmlspecialchars($user_data['email']); ?><br>
          Member since: <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>
        </p>
        <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
        <a href="my_bookings.php" class="edit-profile-btn">My Bookings</a>
      </div>
    </section>

    <section class="bottom-section">
      <!-- Favorite Places -->
      <div class="card fav-places">
        <h3>Favorite places</h3>
        <?php if (!empty($favorite_places)): ?>
        <ol>
          <?php foreach ($favorite_places as $place): ?>
          <li>
            <img src="<?php echo htmlspecialchars($place['image_url']); ?>" alt="<?php echo htmlspecialchars($place['location_name']); ?>" />
            <?php echo htmlspecialchars($place['location_name']); ?>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <p>No favorite places added yet. <a href="tours.php">Explore tours</a> to add favorites.</p>
        <?php endif; ?>
      </div>

      <!-- Upcoming Trips -->
      <div class="card upcoming-trips">
        <h3>Upcoming Trips</h3>
        <?php if (!empty($upcoming_trips)): ?>
          <?php foreach ($upcoming_trips as $trip): ?>
          <div class="trip">
            <strong><?php echo htmlspecialchars($trip['item_title']); ?></strong><br/>
            Start Date: <?php echo date('d/m/Y', strtotime($trip['start_date'])); ?><br/>
            End Date: <?php echo date('d/m/Y', strtotime($trip['end_date'])); ?>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
        <p>No upcoming trips. <a href="tours.php">Book a tour</a> to see your trips here.</p>
        <?php endif; ?>
      </div>

      <!-- Memories -->
      <div class="card memories">
        <h3>Memories</h3>
        <?php if (!empty($memories)): ?>
          <?php foreach ($memories as $caption => $images): ?>
          <div class="memory-group">
            <p># <?php echo htmlspecialchars($caption); ?></p>
            <div class="memory-images">
              <?php 
              $count = 0;
              foreach ($images as $image_url): 
                if ($count >= 3) break; 
              ?>
                <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Memory" />
              <?php 
                $count++;
              endforeach; 
              ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
        <p>No memories yet. Upload photos from your trips to create memories.</p>
        <a href="#" class="upload-btn">Upload Memory</a>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>