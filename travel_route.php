<?php
/**
 * Optimized Travel Route Generator
 * 
 * This script takes an array of travel locations and determines the optimal
 * order to visit them based on distances calculated via the Geoapify Routing API.
 */

// Your Geoapify API key - you need to register at https://www.geoapify.com/ to get one
$apiKey = "46b5f4aa20fa403c897e9747de4bf9f7";

// Array of locations to visit
$locations = [
    
    [
        'name' => 'Colosseum, Rome',
        'lat' => 41.8902,
        'lon' => 12.4922
    ],
    [
        'name' => 'Eiffel Tower, Paris',
        'lat' => 48.8584,
        'lon' => 2.2945
    ],
    [
        'name' => 'Sagrada Familia, Barcelona',
        'lat' => 41.4036,
        'lon' => 2.1744
    ],
    [
        'name' => 'Big Ben, London',
        'lat' => 51.5007,
        'lon' => -0.1246
    ],
    [
        'name' => 'Brandenburg Gate, Berlin',
        'lat' => 52.5163,
        'lon' => 13.3777
    ]
];

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
        echo "cURL Error: " . curl_error($ch);
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

// Find the optimal route
$optimizedRoute = findOptimalRoute($locations, $apiKey);

// Calculate the total distance of the optimized route
$totalDistance = calculateTotalDistance($optimizedRoute, $apiKey);

// Output the results
header('Content-Type: application/json');
echo json_encode([
    'original_locations' => $locations,
    'optimized_route' => $optimizedRoute,
    'total_distance_km' => $totalDistance
], JSON_PRETTY_PRINT);
?>