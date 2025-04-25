<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
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

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['locations']) || empty($_POST['locations'])) {
    header("Location: create_itinerary.php");
    exit();
}

// Get form data
$userId = $_POST['user_id'] ?? $_SESSION['user_id'];
$itineraryName = $_POST['itinerary_name'] ?? 'My Itinerary';
$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;
$locations = $_POST['locations'] ?? [];

// Function to calculate distance between two points using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    // Convert from degrees to radians
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    // Haversine formula
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $radius = 6371; // Radius of the Earth in kilometers
    
    // Distance in kilometers
    $distance = $radius * $c;
    return $distance;
}

// Get state information for each location and build locations array
$locationDetails = [];
$stateGroupedLocations = [];

foreach ($locations as $location) {
    $locationId = $location['id'];
    
    // Query to get location details including state
    $query = "SELECT tl.location_id, tl.location_name, tl.latitude, tl.longitude, 
                     c.city_name, s.state_id, s.state_name
              FROM travel_locations tl
              JOIN cities c ON tl.city_id = c.city_id
              JOIN states s ON c.state_id = s.state_id
              WHERE tl.location_id = $locationId";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        $locationDetails[$locationId] = [
            'id' => $locationId,
            'name' => $row['location_name'],
            'city' => $row['city_name'],
            'state_id' => $row['state_id'],
            'state_name' => $row['state_name'],
            'lat' => $row['latitude'],
            'lon' => $row['longitude']
        ];
        
        // Group locations by state
        if (!isset($stateGroupedLocations[$row['state_id']])) {
            $stateGroupedLocations[$row['state_id']] = [];
        }
        $stateGroupedLocations[$row['state_id']][] = $locationId;
    }
}

// Create distance matrix
$distanceMatrix = [];
foreach ($locationDetails as $id1 => $loc1) {
    $distanceMatrix[$id1] = [];
    
    foreach ($locationDetails as $id2 => $loc2) {
        if ($id1 == $id2) {
            // Distance to self is 0
            $distanceMatrix[$id1][$id2] = 0;
        } elseif ($loc1['state_id'] == $loc2['state_id']) {
            // Calculate distance if locations are in the same state
            $distance = calculateDistance(
                $loc1['lat'], $loc1['lon'],
                $loc2['lat'], $loc2['lon']
            );
            $distanceMatrix[$id1][$id2] = round($distance, 2);
        } else {
            // Locations in different states have NULL distance
            $distanceMatrix[$id1][$id2] = null;
        }
    }
}

// Determine optimal route per state using Nearest Neighbor algorithm
$optimizedRoutes = [];
$totalDistance = 0;

foreach ($stateGroupedLocations as $stateId => $stateLocations) {
    if (count($stateLocations) == 1) {
        // Only one location in this state, no optimization needed
        $optimizedRoutes[$stateId] = $stateLocations;
        continue;
    }
    
    // Start with the first location in the state
    $current = $stateLocations[0];
    $remaining = array_slice($stateLocations, 1);
    $route = [$current];
    $stateDistance = 0;
    
    // Find nearest neighbor until all locations are visited
    while (!empty($remaining)) {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($remaining as $index => $locId) {
            $distance = $distanceMatrix[$current][$locId];
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $index;
            }
        }
        
        // Add the nearest location to the route
        $current = $remaining[$nearest];
        $route[] = $current;
        $stateDistance += $minDistance;
        
        // Remove the location from remaining
        array_splice($remaining, $nearest, 1);
    }
    
    $optimizedRoutes[$stateId] = $route;
    $totalDistance += $stateDistance;
}

// Flatten the optimized routes for storage
$finalRoute = [];
foreach ($optimizedRoutes as $stateId => $stateRoute) {
    foreach ($stateRoute as $locId) {
        $finalRoute[] = $locId;
    }
}

// Calculate trip price based on distance
// Base price + per km charge
$basePrice = 5000; // Base price in your currency
$pricePerKm = 5;  // Price per kilometer
$tripPrice = $basePrice + ($totalDistance * $pricePerKm);

// Store the itinerary in the database
$userId = $conn->real_escape_string($userId);
$itineraryName = $conn->real_escape_string($itineraryName);
$startDate = $startDate ? "'" . $conn->real_escape_string($startDate) . "'" : "NULL";
$endDate = $endDate ? "'" . $conn->real_escape_string($endDate) . "'" : "NULL";
$totalDistance = $conn->real_escape_string($totalDistance);
$tripPrice = $conn->real_escape_string($tripPrice);

$sql = "INSERT INTO itineraries (user_id, itinerary_name, start_date, end_date, total_distance, estimated_price, created_at) 
        VALUES ($userId, '$itineraryName', $startDate, $endDate, $totalDistance, $tripPrice, NOW())";

if ($conn->query($sql) === TRUE) {
    $itineraryId = $conn->insert_id;
    
    // Store the locations in the optimized order
    $order = 1;
    $insertSuccess = true;
    
    foreach ($finalRoute as $locationId) {
        $locationId = $conn->real_escape_string($locationId);
        
        $sql = "INSERT INTO itinerary_locations (itinerary_id, location_id, location_order) 
                VALUES ($itineraryId, $locationId, $order)";
        
        if ($conn->query($sql) !== TRUE) {
            $insertSuccess = false;
            error_log("Error inserting location: " . $conn->error);
        }
        
        $order++;
    }
    
    // Store the distance matrix for reference
    $matrixJson = json_encode($distanceMatrix);
    $sql = "INSERT INTO itinerary_distance_matrix (itinerary_id, distance_matrix) 
            VALUES ($itineraryId, '$matrixJson')";
    $conn->query($sql);
    
    if ($insertSuccess) {
        // Redirect to view the created itinerary
        header("Location: self_itinerary.php?id=$itineraryId");
        exit();
    } else {
        echo "Error: Failed to save some locations";
    }
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>