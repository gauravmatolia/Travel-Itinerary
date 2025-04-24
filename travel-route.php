<?php
/**
 * Optimized Travel Route Generator
 * 
 * This script takes locations from create_itinerary.php,
 * determines the optimal order to visit them based on distances calculated via the Geoapify Routing API,
 * and forwards the data to self_itinerary.php for display.
 */

// Start session to access data
session_start();

// Your Geoapify API key - you need to register at https://www.geoapify.com/ to get one
$apiKey = "46b5f4aa20fa403c897e9747de4bf9f7";

// Check if we're receiving locations from create_itinerary.php
if (isset($_POST['locations']) && is_array($_POST['locations'])) {
    // Receive locations from create_itinerary.php via POST
    $locations = $_POST['locations'];
} elseif (isset($_SESSION['selected_locations']) && is_array($_SESSION['selected_locations'])) {
    // Alternatively, get locations from session if available
    $locations = $_SESSION['selected_locations'];
} else {
    // Fallback to default locations if none provided
    $locations = [
        [
            'name' => 'Eiffel Tower, Paris',
            'lat' => 48.8584,
            'lon' => 2.2945
        ],
        [
            'name' => 'Colosseum, Rome',
            'lat' => 41.8902,
            'lon' => 12.4922
        ],
        [
            'name' => 'Sagrada Familia, Barcelona',
            'lat' => 41.4036,
            'lon' => 2.1744
        ]
    ];
}

// Database connection - modify with your credentials
function connectToDatabase() {
    $servername = "localhost";
    $username = "root";  // Default XAMPP username
    $password = "";      // Default XAMPP password
    $dbname = "travel_itinerary";  // Your database name
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// If user ID and itinerary ID are available, store/update the itinerary in database
if (isset($_POST['user_id']) && isset($_POST['itinerary_name'])) {
    $conn = connectToDatabase();
    
    $userId = $_POST['user_id'];
    $itineraryName = $_POST['itinerary_name'];
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    
    // Convert locations array to JSON for storage
    $locationsJson = json_encode($locations);
    
    // Insert itinerary into database
    $sql = "INSERT INTO itinerary (user_id, itinerary_name, locations, start_date, end_date, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $itineraryName, $locationsJson, $startDate, $endDate);
    
    if ($stmt->execute()) {
        $itineraryId = $conn->insert_id;
        $_SESSION['itinerary_id'] = $itineraryId;
    }
    
    $stmt->close();
    $conn->close();
}

/**
 * Calculate distance between two points using Geoapify Routing API
 * 
 * @param array $origin Starting location [lat, lon]
 * @param array $destination Ending location [lat, lon]
 * @param string $apiKey Your Geoapify API key
 * @return float|null Distance in kilometers or null if API call fails
 */
function calculateDistance($origin, $destination, $apiKey) {
    $url = "https://api.geoapify.com/v1/routing?";
    $params = [
        'waypoints' => "{$origin['lon']},{$origin['lat']}|{$destination['lon']},{$destination['lat']}",
        'mode' => 'drive',
        'apiKey' => $apiKey
    ];
    
    $requestUrl = $url . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    // Check if the API returned valid data
    if(isset($data['features'][0]['properties']['distance'])) {
        // Return distance in kilometers
        return $data['features'][0]['properties']['distance'] / 1000;
    }
    
    return null;
}

/**
 * Find the optimal route using a simple nearest neighbor algorithm
 * 
 * @param array $locations Array of locations with lat/lon coordinates
 * @param string $apiKey Geoapify API key
 * @return array Ordered array of locations representing the optimized route
 */
function findOptimalRoute($locations, $apiKey) {
    // If less than 2 locations, no optimization needed
    if (count($locations) < 2) {
        return $locations;
    }
    
    // Start with an empty route
    $route = [];
    
    // Make a copy of the locations array to work with
    $remainingLocations = $locations;
    
    // Start with the first location
    $currentLocation = array_shift($remainingLocations);
    $route[] = $currentLocation;
    
    // While there are still locations to visit
    while(!empty($remainingLocations)) {
        $shortestDistance = PHP_FLOAT_MAX;
        $closestLocationIndex = -1;
        
        // Find the closest location to the current one
        foreach($remainingLocations as $index => $location) {
            $distance = calculateDistance($currentLocation, $location, $apiKey);
            
            if($distance !== null && $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestLocationIndex = $index;
            }
        }
        
        // If we found a valid next location
        if($closestLocationIndex >= 0) {
            $currentLocation = $remainingLocations[$closestLocationIndex];
            $route[] = $currentLocation;
            unset($remainingLocations[$closestLocationIndex]);
            // Reindex the array after removing an element
            $remainingLocations = array_values($remainingLocations);
        } else {
            // If API calls fail, just add remaining locations in their original order
            $route = array_merge($route, $remainingLocations);
            break;
        }
    }
    
    return $route;
}

/**
 * Calculate total distance of a route
 * 
 * @param array $route Ordered array of locations
 * @param string $apiKey Geoapify API key
 * @return float Total distance in kilometers
 */
function calculateTotalDistance($route, $apiKey) {
    $totalDistance = 0;
    
    for($i = 0; $i < count($route) - 1; $i++) {
        $distance = calculateDistance($route[$i], $route[$i + 1], $apiKey);
        if($distance !== null) {
            $totalDistance += $distance;
        }
    }
    
    return $totalDistance;
}

// Check if this is an AJAX request or direct access
if(isset($_GET['json']) && $_GET['json'] == 1) {
    // Find the optimal route
    $optimizedRoute = findOptimalRoute($locations, $apiKey);

    // Calculate the total distance of the optimized route
    $totalDistance = calculateTotalDistance($optimizedRoute, $apiKey);

    // Output JSON for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'original_locations' => $locations,
        'optimized_route' => $optimizedRoute,
        'total_distance_km' => $totalDistance
    ], JSON_PRETTY_PRINT);
    exit;
}

// For direct execution, process the route and forward to self_itinerary.php
$optimizedRoute = findOptimalRoute($locations, $apiKey);
$totalDistance = calculateTotalDistance($optimizedRoute, $apiKey);

// Store the results in session to pass to self_itinerary.php
$_SESSION['travel_data'] = [
    'original_locations' => $locations,
    'optimized_route' => $optimizedRoute,
    'total_distance_km' => $totalDistance
];

// If this was an update to an existing itinerary, update the optimized route in the database
if (isset($_SESSION['itinerary_id'])) {
    $conn = connectToDatabase();
    $itineraryId = $_SESSION['itinerary_id'];
    $optimizedRouteJson = json_encode($optimizedRoute);
    
    $sql = "UPDATE itinerary SET optimized_route = ?, total_distance = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $optimizedRouteJson, $totalDistance, $itineraryId);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
}

// Redirect to self_itinerary.php
header('Location: self_itinerary.php');
exit;
?>