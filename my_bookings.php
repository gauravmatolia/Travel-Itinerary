<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch all bookings for the current user
$query = "SELECT * FROM final_bookings WHERE user_id = ? ORDER BY booking_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
$stmt->close();

// Separate bookings into active and completed
$today = date('Y-m-d');
$activeBookings = [];
$completedBookings = [];

// Get additional details for each booking type
foreach ($bookings as $key => $booking) {
    if ($booking['booking_type'] == 'tour') {
        // Get tour details
        $query = "SELECT t.*, c.city_name, s.state_name 
                  FROM tours t 
                  JOIN cities c ON t.city_id = c.city_id 
                  JOIN states s ON t.state_id = s.state_id 
                  WHERE t.tour_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $booking['item_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $tour_details = $result->fetch_assoc();
            $booking['details'] = $tour_details;
            
            // Check if tour is completed
            if ($tour_details['end_date'] < $today) {
                $completedBookings[] = $booking;
            } else {
                $activeBookings[] = $booking;
            }
        }
        $stmt->close();
    } else if ($booking['booking_type'] == 'itinerary') {
        // Get itinerary details
        $query = "SELECT i.*, c.city_name, s.state_name 
                  FROM itineraries i
                  LEFT JOIN cities c ON i.city_id = c.city_id
                  LEFT JOIN states s ON i.state_id = s.state_id
                  WHERE i.itinerary_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $booking['item_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $booking['details'] = $result->fetch_assoc();
            
            // Get locations for this itinerary
            $locationQuery = "SELECT tl.location_name, tl.image_url, c.city_name 
                              FROM itinerary_locations il
                              JOIN travel_locations tl ON il.location_id = tl.location_id
                              JOIN cities c ON tl.city_id = c.city_id
                              WHERE il.itinerary_id = ?
                              ORDER BY il.day_number, il.time_slot";
            $locStmt = $conn->prepare($locationQuery);
            $locStmt->bind_param("i", $booking['item_id']);
            $locStmt->execute();
            $locResult = $locStmt->get_result();
            
            $locations = [];
            if ($locResult && $locResult->num_rows > 0) {
                while ($locRow = $locResult->fetch_assoc()) {
                    $locations[] = $locRow;
                }
            }
            $booking['locations'] = $locations;
            $locStmt->close();
            
            // Check if itinerary is completed
            if ($booking['end_date'] < $today) {
                $completedBookings[] = $booking;
            } else {
                $activeBookings[] = $booking;
            }
        }
        $stmt->close();

        if (empty($booking['details']['image_url'])) {
            $booking['details']['image_url'] = 
                !empty($booking['locations'][0]['image_url'])
                ? $booking['locations'][0]['image_url']
                : 'assets/images/placeholder.jpg';
        }
    
    }
}

// Handle cancellation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $booking_type = $_POST['booking_type'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update booking status based on booking type
        if ($booking_type === 'tour') {
            $updateQuery = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
        } else {
            $updateQuery = "UPDATE itineraries_bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
        }
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $booking_id, $user_id);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows > 0) {
            $conn->commit();
            // Redirect to refresh the page
            header("Location: my_bookings.php?cancelled=success");
            exit();
        } else {
            throw new Exception("Failed to cancel booking");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - TravelGo</title>
    <link rel="stylesheet" href="assets/styles/dashboard.css">
    <link rel="stylesheet" href="assets/styles/my_bookings_styles.css">
    <style>
        
    </style>
</head>
<body>
    <div class="navbar-div">
        <nav class='navbar'>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="tours.php">Tours</a></li>
            <li><a href="aboutus.php">About Us</a></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
        </nav>
        <div class="line"></div>
    </div>
    
    <div class="hero">
        <div class="bookings-container">
            <h1 style="color: white;">My Bookings</h1>
            
            <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'success'): ?>
                <div class="alert alert-success">
                    Your booking has been successfully cancelled.
                </div>
            <?php endif; ?>
            
            <!-- Upcoming Bookings Section -->
            <h2 class="section-title" style="color: white;">Upcoming Trips</h2>
            <?php if (empty($activeBookings)): ?>
                <div class="empty-message">
                    <p>You don't have any upcoming trips.</p>
                    <a href="tours.php" class="btn btn-primary">Explore Tours</a>
                </div>
            <?php else: ?>
                <?php foreach ($activeBookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3 class="booking-title"><?php echo htmlspecialchars($booking['item_title'] ?? 'Custom Itinerary'); ?></h3>
                            <span class="booking-type type-<?php echo $booking['booking_type']; ?>">
                                <?php echo $booking['booking_type'] === 'tour' ? 'Tour Package' : 'Custom Itinerary'; ?>
                            </span>
                        </div>
                        
                        <div class="booking-body">
                            <?php if ($booking['booking_type'] === 'tour'): ?>
                                <img src="<?php echo htmlspecialchars($booking['details']['image_url'] ?? 'assets/images/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['item_title'] ?? 'Tour'); ?>" 
                                     class="booking-image"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <?php if (!empty($booking['locations']) && isset($booking['locations'][0]['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($booking['locations'][0]['image_url']); ?>" 
                                         alt="Itinerary Location" 
                                         class="booking-image"
                                         onerror="this.src='assets/images/placeholder.jpg'">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.jpg" alt="Itinerary" class="booking-image">
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="booking-info">
                                <div class="booking-meta">
                                    <?php if ($booking['booking_type'] === 'tour'): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Location</span>
                                            <span class="meta-value">
                                                <?php echo htmlspecialchars($booking['details']['city_name'] ?? ''); ?>, 
                                                <?php echo htmlspecialchars($booking['details']['state_name'] ?? ''); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (isset($booking['details']['start_date']) && isset($booking['details']['end_date'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Travel Dates</span>
                                            <span class="meta-value">
                                                <?php echo date('M d, Y', strtotime($booking['details']['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($booking['details']['end_date'])); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (isset($booking['start_date']) && isset($booking['end_date'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Travel Dates</span>
                                            <span class="meta-value">
                                                <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($booking['details']['city_name']) || isset($booking['details']['state_name'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Main Location</span>
                                            <span class="meta-value">
                                                <?php echo htmlspecialchars($booking['details']['city_name'] ?? ''); ?>
                                                <?php echo !empty($booking['details']['city_name']) && !empty($booking['details']['state_name']) ? ', ' : ''; ?>
                                                <?php echo htmlspecialchars($booking['details']['state_name'] ?? ''); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Booking ID</span>
                                        <span class="meta-value">#<?php echo $booking['booking_id']; ?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Amount Paid</span>
                                        <span class="meta-value">₹<?php echo number_format($booking['payment_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Status</span>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($booking['booking_type'] === 'itinerary' && !empty($booking['locations'])): ?>
                                <div class="location-chips">
                                    <?php foreach (array_slice($booking['locations'], 0, 3) as $location): ?>
                                        <span class="location-chip"><?php echo htmlspecialchars($location['location_name']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($booking['locations']) > 3): ?>
                                        <span class="location-chip">+<?php echo (count($booking['locations']) - 3); ?> more</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'confirmed'): ?>
                                <div class="booking-actions">
                                    <?php if ($booking['booking_type'] === 'tour'): ?>
                                        <a href="view_tour.php?id=<?php echo $booking['item_id']; ?>" class="btn btn-outline">View Tour Details</a>
                                    <?php else: ?>
                                        <a href="view_itinerary.php?id=<?php echo $booking['item_id']; ?>" class="btn btn-outline">View Itinerary Details</a>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <input type="hidden" name="booking_type" value="<?php echo $booking['booking_type']; ?>">
                                        <button type="submit" name="cancel_booking" class="btn btn-danger">Cancel Booking</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Completed Trips Section -->
            <h2 class="section-title" style="color: white;">Completed Trips</h2>
            <?php if (empty($completedBookings)): ?>
                <div class="empty-message">
                    <p>You don't have any completed trips yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($completedBookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3 class="booking-title"><?php echo htmlspecialchars($booking['item_title'] ?? 'Custom Itinerary'); ?></h3>
                            <span class="completed-badge">Completed</span>
                        </div>
                        
                        <div class="booking-body">
                            <?php if ($booking['booking_type'] === 'tour'): ?>
                                <img src="<?php echo htmlspecialchars($booking['details']['image_url'] ?? 'assets/images/placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['item_title'] ?? 'Tour'); ?>" 
                                     class="booking-image"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <?php if (!empty($booking['locations']) && isset($booking['locations'][0]['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($booking['locations'][0]['image_url']); ?>" 
                                         alt="Itinerary Location" 
                                         class="booking-image"
                                         onerror="this.src='assets/images/placeholder.jpg'">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.jpg" alt="Itinerary" class="booking-image">
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="booking-info">
                                <div class="booking-meta">
                                    <?php if ($booking['booking_type'] === 'tour'): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Location</span>
                                            <span class="meta-value">
                                                <?php echo htmlspecialchars($booking['details']['city_name'] ?? ''); ?>, 
                                                <?php echo htmlspecialchars($booking['details']['state_name'] ?? ''); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (isset($booking['details']['start_date']) && isset($booking['details']['end_date'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Travel Dates</span>
                                            <span class="meta-value">
                                                <?php echo date('M d, Y', strtotime($booking['details']['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($booking['details']['end_date'])); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (isset($booking['start_date']) && isset($booking['end_date'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Travel Dates</span>
                                            <span class="meta-value">
                                                <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($booking['details']['city_name']) || isset($booking['details']['state_name'])): ?>
                                        <div class="meta-item">
                                            <span class="meta-label">Main Location</span>
                                            <span class="meta-value">
                                                <?php echo htmlspecialchars($booking['details']['city_name'] ?? ''); ?>
                                                <?php echo !empty($booking['details']['city_name']) && !empty($booking['details']['state_name']) ? ', ' : ''; ?>
                                                <?php echo htmlspecialchars($booking['details']['state_name'] ?? ''); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Booking ID</span>
                                        <span class="meta-value">#<?php echo $booking['booking_id']; ?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Amount Paid</span>
                                        <span class="meta-value">₹<?php echo number_format($booking['payment_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($booking['booking_type'] === 'itinerary' && !empty($booking['locations'])): ?>
                                <div class="location-chips">
                                    <?php foreach (array_slice($booking['locations'], 0, 3) as $location): ?>
                                        <span class="location-chip"><?php echo htmlspecialchars($location['location_name']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($booking['locations']) > 3): ?>
                                        <span class="location-chip">+<?php echo (count($booking['locations']) - 3); ?> more</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="booking-actions">
                                    <?php if ($booking['booking_type'] === 'tour'): ?>
                                        <a href="view_tour.php?id=<?php echo $booking['item_id']; ?>" class="btn btn-outline">View Tour Details</a>
                                    <?php else: ?>
                                        <a href="view_itinerary.php?id=<?php echo $booking['item_id']; ?>" class="btn btn-outline">View Itinerary Details</a>
                                    <?php endif; ?>
                                    
                                    <a href="add_memory.php?booking_id=<?php echo $booking['booking_id']; ?>&type=<?php echo $booking['booking_type']; ?>" class="btn add-memory-btn">Add Memory</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div style="background-color: black; color: white;">
        <?php include('footer.php'); ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>