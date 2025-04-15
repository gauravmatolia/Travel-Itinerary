<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT tour_id, tour_name, tour_description, price, location, image_url FROM tours WHERE is_featured = TRUE ORDER BY price ASC LIMIT 3";
$result = $conn->query($sql);

$tours = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tours[] = $row;
    }
}

$countSql = "SELECT COUNT(*) as total FROM tours WHERE is_featured = TRUE";
$countResult = $conn->query($countSql);
$totalTours = $countResult->fetch_assoc()['total'];
$hasMoreTours = $totalTours > 3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Tours</title>
    <link rel="stylesheet" href="assets/styles/tours_styles.css">
    <style>
        
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="tours.php">Tours</a>
            <a href="aboutus.html">About Us</a>
            <a href="contactus.php">Contact Us</a>
            <a href="profile.php">Profile</a>
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Search...">
            <button><img src="assets/home_images/search0.svg" alt="search_icon"></button>
        </div>
    </nav>
    
    <div class="container">
        <!-- Tours Section -->
        <h1>
            Recommended Tours
        </h1>
        
        <div class="tours" id="tours-container">
            <?php if(count($tours) > 0): ?>
                <?php foreach($tours as $index => $tour): ?>
                    <!-- Tour <?php echo $index + 1; ?> -->
                    <div class="tour-card">
                        <div class="tour-overlay"></div>
                        <div class="tour-image-container">
                            <img src="<?php echo htmlspecialchars($tour['image_url']); ?>" alt="<?php echo htmlspecialchars($tour['tour_name']); ?>" class="tour-image">
                        </div>
                        <div class="tour-details">
                            <h2 class="tour-title"><?php echo htmlspecialchars($tour['tour_name']); ?></h2>
                            <p class="tour-description">
                                <?php echo htmlspecialchars($tour['tour_description']); ?>
                            </p>
                        </div>
                        <div class="tour-price-book">
                            <div class="tour-price">₹<?php echo number_format($tour['price'], 2); ?></div>
                            <button class="book-btn" data-tour-id="<?php echo $tour['tour_id']; ?>">Book</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No tours available at the moment. Please check back later.</p>
            <?php endif; ?>
        </div>
        
        <?php if($hasMoreTours): ?>
        <div class="show-more-container">
            <button id="show-more-btn" class="show-more-btn" data-offset="3" data-limit="5">Show More</button>
            <div class="spinner" id="loading-spinner"></div>
        </div>
        <?php endif; ?>
        
        <div class="dotted-line"></div>
        
        
        <div class="custom-tour">
            <img src="assets/Images/Girl_Character.png" alt="Create custom tour" class="custom-tour-image">
            <div class="custom-tour-content">
                <h2 class="custom-tour-title">Want more in your tour?</h2>
                <p>Create your Own Itinerary</p>
                <a href="#"><button class="custom-tour-btn">Go</button></a>
            </div>
        </div>
    </div>
    
    <script>
        
        document.querySelectorAll('.book-btn').forEach(button => {
            button.addEventListener('click', function() {
                const tourId = this.getAttribute('data-tour-id');
                window.location.href = `booking.php?tour_id=${tourId}`;
            });
        });
        
        
        document.getElementById('show-more-btn').addEventListener('click', function() {
            const offset = parseInt(this.getAttribute('data-offset'));
            const limit = parseInt(this.getAttribute('data-limit'));
            const spinner = document.getElementById('loading-spinner');
            
            
            spinner.style.display = 'block';
            this.style.display = 'none';
            
            
            fetch(`load_more_tours.php?offset=${offset}&limit=${limit}`)
                .then(response => response.json())
                .then(data => {
                    
                    spinner.style.display = 'none';
                    
                    if (data.tours && data.tours.length > 0) {
                    
                        const toursContainer = document.getElementById('tours-container');
                        
                        data.tours.forEach(tour => {
                            const tourCard = document.createElement('div');
                            tourCard.className = 'tour-card';
                            tourCard.innerHTML = `
                                <div class="tour-overlay"></div>
                                <div class="tour-image-container">
                                    <img src="${tour.image_url}" alt="${tour.tour_name}" class="tour-image">
                                </div>
                                <div class="tour-details">
                                    <h2 class="tour-title">${tour.tour_name}</h2>
                                    <p class="tour-description">${tour.tour_description}</p>
                                </div>
                                <div class="tour-price-book">
                                    <div class="tour-price">₹${parseFloat(tour.price).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                                    <button class="book-btn" data-tour-id="${tour.tour_id}">Book</button>
                                </div>
                            `;
                            toursContainer.appendChild(tourCard);
                        });
                        
                        
                        document.querySelectorAll('.book-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const tourId = this.getAttribute('data-tour-id');
                                window.location.href = `booking.php?tour_id=${tourId}`;
                            });
                        });
                        
                        
                        const newOffset = offset + data.tours.length;
                        this.setAttribute('data-offset', newOffset);
                        
                        
                        if (data.hasMore) {
                            this.style.display = 'block';
                        } else {
                            
                            this.parentNode.remove();
                        }
                    } else {
                        
                        this.parentNode.remove();
                    }
                })
                .catch(error => {
                    console.error('Error loading more tours:', error);
                    spinner.style.display = 'none';
                    this.style.display = 'block';
                });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>