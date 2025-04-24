<?php
// Start session to get user info
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=create_itinerary.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root"; // replace with your database username
$password = ""; // replace with your database password
$dbname = "travel_itinerary"; // replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if (empty($_POST['itinerary_name']) || empty($_POST['locations'])) {
        header("Location: create_itinerary.php?error=missing_data");
        exit;
    }
    
    // Get user ID from session
    $userId = $_SESSION['user_id'];
    
    // Get itinerary name and sanitize
    $itineraryName = $conn->real_escape_string($_POST['itinerary_name']);
    
    // Decode locations JSON
    $locations = json_decode($_POST['locations'], true);
    
    if (empty($locations)) {
        header("Location: create_itinerary.php?error=no_locations");
        exit;
    }
    
    // Get the first location to determine state and city
    $firstLocation = $locations[0];
    $locationId = $firstLocation['id'];
    
    // Get city and state IDs for the first location
    $query = "SELECT c.city_id, c.state_id 
              FROM travel_locations tl
              JOIN cities c ON tl.city_id = c.city_id
              WHERE tl.location_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: create_itinerary.php?error=invalid_location");
        exit;
    }
    
    $locationData = $result->fetch_assoc();
    $cityId = $locationData['city_id'];
    $stateId = $locationData['state_id'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create new itinerary
        $query = "INSERT INTO itineraries (user_id, itinerary_name, state_id, city_id, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isii", $userId, $itineraryName, $stateId, $cityId);
        $stmt->execute();
        
        // Get the new itinerary ID
        $itineraryId = $conn->insert_id;
        
        // Add locations to itinerary
        $query = "INSERT INTO itinerary_locations (itinerary_id, location_id, day_number) 
                  VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        // Add each location to the itinerary
        foreach ($locations as $index => $location) {
            $dayNumber = $index + 1; // Assign sequential day numbers for simplicity
            $locationId = $location['id'];
            
            $stmt->bind_param("iii", $itineraryId, $locationId, $dayNumber);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to success page
        header("Location: tour_details.php?tour_id=" . $itineraryId);
        exit;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        header("Location: create_itinerary.php?error=database_error");
        exit;
    }
}
?>