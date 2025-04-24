<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : "";
$limit = 3;

// Modify SQL query to include search if present
if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT tour_id, title, description, price, image_url, rating FROM tours WHERE is_public = TRUE AND title LIKE ? ORDER BY price ASC LIMIT ?");
    $searchPattern = "%" . $searchTerm . "%";
    $stmt->bind_param("si", $searchPattern, $limit);
} else {
    $stmt = $conn->prepare("SELECT tour_id, title, description, price, image_url, rating FROM tours WHERE is_public = TRUE ORDER BY price ASC LIMIT ?");
    $stmt->bind_param("i", $limit);
}

$stmt->execute();
$result = $stmt->get_result();

$tours = [];
while ($row = $result->fetch_assoc()) {
    $tours[] = $row;
}

$stmt->close();

// Count total number of tours matching search
if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tours WHERE is_public = TRUE AND title LIKE ?");
    $stmt->bind_param("s", $searchPattern);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tours WHERE is_public = TRUE");
}
$stmt->execute();
$countResult = $stmt->get_result();
$totalTours = $countResult->fetch_assoc()['total'];
$hasMoreTours = $totalTours > $limit;

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TravelGo</title>
    <link rel="stylesheet" href="assets/styles/tours_styles.css">
</head>
<body>
<?php include('navbar2.php'); ?>
<br><br><br>

<div class="container">
    <h1>Recommended Tours</h1>

    <!-- <form method="get" style="margin-bottom: 20px;">
        <input type="text" name="search" placeholder="Search tours..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Search</button>
    </form> -->

    <div class="tours" id="tours-container">
        <?php if (count($tours) > 0): ?>
            <?php foreach ($tours as $tour): ?>
                <div class="tour-card">
                    <div class="tour-overlay"></div>
                    <div class="tour-image-container">
                        <img src="<?php echo htmlspecialchars($tour['image_url']); ?>" class="tour-image" alt="<?php echo htmlspecialchars($tour['title']); ?>">
                    </div>
                    <div class="tour-details">
                        <h2 class="tour-title"><?php echo htmlspecialchars($tour['title']); ?></h2>
                        <p class="tour-description"><?php echo htmlspecialchars($tour['description']); ?></p>
                        <br>
                        <a href="tour_details.php?tour_id=<?php echo $tour['tour_id']; ?>" style="text-decoration: none; color: orange;">More Info</a>
                    </div>
                    <div class="tour-price-book">
                        <div class="tour-price">₹<?php echo number_format($tour['price'], 2); ?></div>
                        <div class="tour-rating">⭐<?php echo number_format($tour['rating'], 2); ?></div>
                        <button class="book-btn" data-tour-id="<?php echo $tour['tour_id']; ?>">Book</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No tours found.</p>
        <?php endif; ?>
    </div>

    <?php if ($hasMoreTours): ?>
    <div class="show-more-container">
        <button id="show-more-btn" class="show-more-btn" data-offset="3" data-limit="5" data-search="<?php echo htmlspecialchars($searchTerm); ?>">Show More</button>
        <div class="spinner" id="loading-spinner"></div>
    </div>
    <?php endif; ?>

    <div class="dotted-line"></div>

    <div class="custom-tour">
        <img src="assets/Images/Girl_Character.png" class="custom-tour-image" alt="Create custom tour">
        <div class="custom-tour-content">
            <h2 class="custom-tour-title">Want more in your tour?</h2>
            <p>Create your Own Itinerary</p>
            <a href="create_itinerary.php"><button class="custom-tour-btn">Go</button></a>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.book-btn').forEach(button => {
        button.addEventListener('click', function () {
            const tourId = this.getAttribute('data-tour-id');
            window.location.href = `booking.php?tour_id=${tourId}`;
        });
    });

    const showMoreBtn = document.getElementById('show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function () {
            const offset = parseInt(this.getAttribute('data-offset'));
            const limit = parseInt(this.getAttribute('data-limit'));
            const search = this.getAttribute('data-search');
            const spinner = document.getElementById('loading-spinner');

            spinner.style.display = 'block';
            this.style.display = 'none';

            fetch(`load_more_tours.php?offset=${offset}&limit=${limit}&search=${encodeURIComponent(search)}`)
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';

                    if (data.tours.length > 0) {
                        const container = document.getElementById('tours-container');
                        data.tours.forEach(tour => {
                            const tourCard = document.createElement('div');
                            tourCard.className = 'tour-card';
                            tourCard.innerHTML = `
                                <div class="tour-overlay"></div>
                                <div class="tour-image-container">
                                    <img src="${tour.image_url}" class="tour-image" alt="${tour.title}">
                                </div>
                                <div class="tour-details">
                                    <h2 class="tour-title">${tour.title}</h2>
                                    <p class="tour-description">${tour.description}</p>
                                    <br>
                                    <a href="tour_details.php?tour_id=${tour.tour_id}" style="text-decoration: none; color: orange;">More Info</a>
                                </div>
                                <div class="tour-price-book">
                                    <div class="tour-price">₹${parseFloat(tour.price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                                    <div class="tour-rating">⭐${parseFloat(tour.rating).toFixed(2)}</div>
                                    <button class="book-btn" data-tour-id="${tour.tour_id}">Book</button>
                                </div>`;
                            container.appendChild(tourCard);
                        });

                        document.querySelectorAll('.book-btn').forEach(button => {
                            button.addEventListener('click', function () {
                                const tourId = this.getAttribute('data-tour-id');
                                window.location.href = `booking.php?tour_id=${tourId}`;
                            });
                        });

                        const newOffset = offset + data.tours.length;
                        showMoreBtn.setAttribute('data-offset', newOffset);

                        if (!data.hasMore) {
                            showMoreBtn.parentNode.remove();
                        } else {
                            showMoreBtn.style.display = 'block';
                        }
                    } else {
                        showMoreBtn.parentNode.remove();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    spinner.style.display = 'none';
                    showMoreBtn.style.display = 'block';
                });
        });
    }
</script>
</body>
</html>

<?php $conn->close(); ?>
