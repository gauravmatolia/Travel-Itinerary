<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tour_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tour_id <= 0) {
    echo "Invalid tour ID.";
    exit;
}

// Fetch tour details
$sql = "SELECT tour_id, tour_name, tour_description, price, duration_days, location, image_url, rating, max_group_size, difficulty_level, includes, excludes, departure_dates FROM tours WHERE tour_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Error preparing statement (tour details): " . $conn->error;
    exit;
}

$stmt->bind_param("i", $tour_id);

if (!$stmt->execute()) {
    echo "Error executing statement (tour details): " . $stmt->error;
    exit;
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Tour not found.";
    exit;
}

$tour = $result->fetch_assoc();
$stmt->close();

// Fetch specific travel locations for the tour
$locations_sql = "SELECT tl.location_name, tl.description
                  FROM tour_locations AS tol
                  JOIN travel_locations AS tl ON tol.location_id = tl.location_id
                  WHERE tol.tour_id = ?";

$locations_stmt = $conn->prepare($locations_sql);
if (!$locations_stmt) {
    echo "Error preparing statement (travel locations): " . $conn->error;
    exit;
}

$locations_stmt->bind_param("i", $tour_id);

if (!$locations_stmt->execute()) {
    echo "Error executing statement (travel locations): " . $locations_stmt->error;
    exit;
}

$locations_result = $locations_stmt->get_result();

$travel_locations = [];
while ($location = $locations_result->fetch_assoc()) {
    $travel_locations[] = $location;
}
$locations_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tour['tour_name']); ?> - Tour Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/styles/tours_details_styles.css">
    <style>
        .tour-locations {
            padding: 20px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .tour-locations h2 {
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .location-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #fff;
        }

        .location-item h3 {
            color: #555;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .location-item p {
            color: #777;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="tour-hero" style="background-image: url('<?php echo htmlspecialchars($tour['image_url']); ?>');">
        <div class="tour-title">
            <h1><?php echo htmlspecialchars($tour['tour_name']); ?></h1>
            <div class="tour-meta">
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($tour['duration_days']); ?> days</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($tour['location']); ?></span>
                </div>
                <div class="meta-item tour-rating">
                    <?php
                    $rating = $tour['rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<i class="' . ($i <= $rating ? 'fas' : 'far') . ' fa-star"></i>';
                    }
                    ?>
                    <span>(<?php echo number_format($rating, 1); ?>)</span>
                </div>
            </div>
        </div>
    </div>
    <section class="tour-overview">
        <div class="overview-grid">
            <div class="overview-details">
                <h2>Tour Overview</h2>
                <div class="tour-description">
                    <?php echo nl2br(htmlspecialchars($tour['tour_description'])); ?>
                </div>
            </div>
            <div class="overview-card">
                <div class="price-box">
                    <div class="price">₹<?php echo number_format($tour['price']); ?></div>
                    <div class="price-meta">per person</div>
                </div>
                <a href="booking.php?tour_id=<?php echo $tour['tour_id']; ?>" class="book-btn">Book Now</a>
                <div class="tour-info">
                    <div class="info-item">
                        <div>Duration</div>
                        <div><strong><?php echo htmlspecialchars($tour['duration_days']); ?> days</strong></div>
                    </div>
                    <div class="info-item">
                        <div>Max Group Size</div>
                        <div><strong><?php echo htmlspecialchars($tour['max_group_size']); ?> people</strong></div>
                    </div>
                    <div class="info-item">
                        <div>Difficulty</div>
                        <div><strong><?php echo htmlspecialchars($tour['difficulty_level']); ?></strong></div>
                    </div>
                    <div class="info-item">
                        <div>Departure Days</div>
                        <div><strong><?php echo htmlspecialchars($tour['departure_dates']); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($travel_locations)): ?>
    <section class="tour-locations">
        <h2>Travel Locations</h2>
        <?php foreach ($travel_locations as $location): ?>
        <div class="location-item">
            <h3><?php echo htmlspecialchars($location['location_name']); ?></h3>
            <?php if (!empty($location['description'])): ?>
            <p><?php echo htmlspecialchars($location['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="tour-includes-excludes">
        <div class="includes">
            <h2>Includes</h2>
            <p><?php echo nl2br(htmlspecialchars($tour['includes'])); ?></p>
        </div>
        <div class="excludes">
            <h2>Excludes</h2>
            <p><?php echo nl2br(htmlspecialchars($tour['excludes'])); ?></p>
        </div>
    </section>

    
</body>
</html>