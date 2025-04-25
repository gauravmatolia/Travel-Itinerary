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

// Check if itinerary ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$itineraryId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Get itinerary details
$query = "SELECT i.*, u.username 
          FROM itineraries i
          JOIN users u ON i.user_id = u.user_id
          WHERE i.itinerary_id = $itineraryId AND i.user_id = $userId";
$result = $conn->query($query);

if (!$result || $result->num_rows == 0) {
    // Itinerary not found or doesn't belong to user
    header("Location: dashboard.php");
    exit();
}

$itinerary = $result->fetch_assoc();

// Get locations in the itinerary in their optimized order
$query = "SELECT tl.location_id, tl.location_name, tl.description, tl.image_url, 
                 tl.latitude, tl.longitude,
                 c.city_name, s.state_name,
                 il.location_order
          FROM itinerary_locations il
          JOIN travel_locations tl ON il.location_id = tl.location_id
          JOIN cities c ON tl.city_id = c.city_id
          JOIN states s ON c.state_id = s.state_id
          WHERE il.itinerary_id = $itineraryId
          ORDER BY il.location_order";
$result = $conn->query($query);

$locations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

// Get distance matrix if available
$distanceMatrix = null;
$query = "SELECT distance_matrix FROM itinerary_distance_matrix WHERE itinerary_id = $itineraryId";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $distanceMatrix = json_decode($row['distance_matrix'], true);
}

// If distance matrix is not available, calculate it using Geoapify API
// Note: In a production environment, this would ideally be done during itinerary creation
// and stored in the database for efficiency
if (!$distanceMatrix && count($locations) > 1) {
    $distanceMatrix = [];
    $totalDistance = 0;
    
    // Initialize the distance matrix
    foreach ($locations as $location) {
        $distanceMatrix[$location['location_id']] = [];
    }
    
    // Geoapify API key
    $apiKey = "YOUR_GEOAPIFY_API_KEY";
    
    // Calculate distance between consecutive locations
    for ($i = 0; $i < count($locations) - 1; $i++) {
        $origin = $locations[$i];
        $destination = $locations[$i + 1];
        
        $originId = $origin['location_id'];
        $destinationId = $destination['location_id'];
        
        // Construct API URL for routing
        $url = "https://api.geoapify.com/v1/routing?waypoints=" . 
               $origin['latitude'] . "," . $origin['longitude'] . "|" . 
               $destination['latitude'] . "," . $destination['longitude'] . 
               "&mode=drive&apiKey=" . $apiKey;
        
        // Make API request
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            
            // Extract distance in kilometers
            if (isset($data['features'][0]['properties']['distance'])) {
                $distance = round($data['features'][0]['properties']['distance'] / 1000, 2); // Convert meters to km
                $distanceMatrix[$originId][$destinationId] = $distance;
                $totalDistance += $distance;
            } else {
                // Fallback to straight-line distance if routing fails
                $distance = calculateHaversineDistance(
                    $origin['latitude'], $origin['longitude'],
                    $destination['latitude'], $destination['longitude']
                );
                $distanceMatrix[$originId][$destinationId] = $distance;
                $totalDistance += $distance;
            }
        } else {
            // Fallback to straight-line distance if API request fails
            $distance = calculateHaversineDistance(
                $origin['latitude'], $origin['longitude'],
                $destination['latitude'], $destination['longitude']
            );
            $distanceMatrix[$originId][$destinationId] = $distance;
            $totalDistance += $distance;
        }
    }
    
    // Update the itinerary with the total distance
    $query = "UPDATE itineraries SET total_distance = $totalDistance, 
              estimated_price = 5000 + ($totalDistance * 5) 
              WHERE itinerary_id = $itineraryId";
    $conn->query($query);
    
    // Store the distance matrix in the database
    $matrixJson = json_encode($distanceMatrix);
    
    // Check if a record already exists
    $query = "SELECT * FROM itinerary_distance_matrix WHERE itinerary_id = $itineraryId";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $query = "UPDATE itinerary_distance_matrix 
                  SET distance_matrix = '$matrixJson' 
                  WHERE itinerary_id = $itineraryId";
    } else {
        $query = "INSERT INTO itinerary_distance_matrix (itinerary_id, distance_matrix) 
                  VALUES ($itineraryId, '$matrixJson')";
    }
    
    $conn->query($query);
    
    // Refresh itinerary data
    $query = "SELECT * FROM itineraries WHERE itinerary_id = $itineraryId";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $itinerary = $result->fetch_assoc();
    }
}

// Function to calculate straight-line distance between two points using Haversine formula
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the Earth in kilometers
    
    $lat1 = deg2rad(floatval($lat1));
    $lon1 = deg2rad(floatval($lon1));
    $lat2 = deg2rad(floatval($lat2));
    $lon2 = deg2rad(floatval($lon2));
    
    $latDelta = $lat2 - $lat1;
    $lonDelta = $lon2 - $lon1;
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos($lat1) * cos($lat2) * 
         sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return round($earthRadius * $c, 2);
}

// Calculate total travel time (assuming average speed of 50 km/h)
$averageSpeed = 50; // km/h
$travelTimeHours = $itinerary['total_distance'] / $averageSpeed;
$travelTimeDays = ceil($travelTimeHours / 8); // Assuming 8 hours of travel per day
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Itinerary - <?php echo htmlspecialchars($itinerary['itinerary_name']); ?></title>
  <link rel="stylesheet" href="assets/styles/self_itinerary.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <style>
    /* Additional styles for distance and price info */
    .itinerary-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
    }
    
    .stat-box {
      text-align: center;
      padding: 15px;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .stat-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: #4CAF50;
    }
    
    .stat-label {
      font-size: 0.9rem;
      color: #666;
    }
    
    .route-map {
      height: 400px;
      margin-bottom: 30px;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .location-card {
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      display: grid;
      grid-template-columns: 150px 1fr;
    }
    
    .location-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .location-details {
      padding: 15px;
    }
    
    .location-name {
      font-size: 1.2rem;
      margin-bottom: 5px;
    }
    
    .location-city {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 10px;
    }
    
    .location-description {
      font-size: 0.9rem;
      line-height: 1.4;
      color: #333;
    }
    
    .route-connector {
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 15px 0;
    }
    
    .connector-line {
      height: 30px;
      width: 2px;
      background-color: #4CAF50;
    }
    
    .distance-badge {
      background-color: #f0f8ff;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.8rem;
      margin-left: 10px;
      border: 1px solid #b8daff;
      color: #0066cc;
    }
    
    .price-breakdown {
      margin-top: 40px;
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
    }
    
    .price-title {
      font-size: 1.2rem;
      margin-bottom: 15px;
      color: #333;
      color: black;
    }
    
    .price-item {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px dashed #ddd;
      color: black;
    }
    
    .price-total {
      display: flex;
      justify-content: space-between;
      padding: 15px 0;
      font-weight: bold;
      font-size: 1.1rem;
      margin-top: 10px;
      border-top: 2px solid #ddd;
      color: black;
    }
    
    .book-button {
      display: block;
      background-color: #4CAF50;
      color: white;
      text-align: center;
      padding: 15px;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: bold;
      text-decoration: none;
      margin-top: 20px;
      transition: background-color 0.3s;
    }
    
    .book-button:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
  <div class="hero">
    <?php include('navbar.php'); ?>
    
    <section class="itinerary-details">
      <h1><?php echo htmlspecialchars($itinerary['itinerary_name']); ?></h1>
      
      <div class="itinerary-dates">
        <?php if ($itinerary['start_date'] && $itinerary['end_date']): ?>
          <p>From: <?php echo date('F j, Y', strtotime($itinerary['start_date'])); ?></p>
          <p>To: <?php echo date('F j, Y', strtotime($itinerary['end_date'])); ?></p>
        <?php endif; ?>
      </div>
      
      <div class="itinerary-stats">
        <div class="stat-box">
          <div class="stat-value"><?php echo count($locations); ?></div>
          <div class="stat-label">Destinations</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-value"><?php echo round($itinerary['total_distance']); ?> km</div>
          <div class="stat-label">Total Distance</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-value"><?php echo $travelTimeDays; ?> days</div>
          <div class="stat-label">Estimated Travel Time</div>
        </div>
        
        <div class="stat-box">
          <div class="stat-value">₹<?php echo number_format($itinerary['estimated_price']); ?></div>
          <div class="stat-label">Estimated Price</div>
        </div>
      </div>
      
      <!-- Map display -->
      <div class="route-map" id="map"></div>
      
      <!-- Location list with optimized route -->
      <h2>Your Optimized Travel Route</h2>
      
      <?php foreach ($locations as $index => $location): ?>
        <div class="location-card">
          <img src="<?php echo htmlspecialchars($location['image_url']); ?>" 
               alt="<?php echo htmlspecialchars($location['location_name']); ?>"
               onerror="this.src='assets/images/placeholder.jpg'">
          <div class="location-details">
            <h3 class="location-name"><?php echo htmlspecialchars($location['location_name']); ?></h3>
            <div class="location-city">
              <?php echo htmlspecialchars($location['city_name']); ?>, 
              <?php echo htmlspecialchars($location['state_name']); ?>
            </div>
            <p class="location-description"><?php echo htmlspecialchars($location['description']); ?></p>
          </div>
        </div>
        
        <?php if ($index < count($locations) - 1): ?>
          <div class="route-connector">
            <div class="connector-line"></div>
            <?php 
              // Show distance between this location and the next one
              $currentId = $location['location_id'];
              $nextId = $locations[$index + 1]['location_id'];
              $distance = isset($distanceMatrix[$currentId][$nextId]) ? $distanceMatrix[$currentId][$nextId] : null;
              
              if ($distance !== null): 
            ?>
              <div class="distance-badge"><?php echo $distance; ?> km</div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
      
      <!-- Price breakdown -->
      <div class="price-breakdown">
        <h3 class="price-title">Price Breakdown</h3>
        
        <div class="price-item">
          <span>Base Package Fee</span>
          <span>₹5,000</span>
        </div>
        
        <div class="price-item">
          <span>Distance Charge (<?php echo round($itinerary['total_distance']); ?> km × ₹5)</span>
          <span>₹<?php echo number_format($itinerary['total_distance'] * 5); ?></span>
        </div>
        
        <div class="price-total">
          <span>Total Estimated Price</span>
          <span>₹<?php echo number_format($itinerary['estimated_price']); ?></span>
        </div>
        
        <a href="itineraries_booking.php?id=<?php echo $itineraryId; ?>" class="book-button">Book This Trip</a>
      </div>
    </section>
  </div>

  <!-- Include Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  
  <script>
    // Initialize map with Geoapify
    function initMap() {
      const locations = <?php echo json_encode($locations); ?>;
      const distanceMatrix = <?php echo json_encode($distanceMatrix); ?>;
      
      // Create map centered on India
      const map = L.map('map').setView([20.5937, 78.9629], 5);
      
      // Add Geoapify tile layer
      L.tileLayer('https://maps.geoapify.com/v1/tile/{style}/{z}/{x}/{y}.png?apiKey=46b5f4aa20fa403c897e9747de4bf9f7', {
        style: 'osm-carto', // Map style: 'osm-carto', 'osm-bright', 'klokantech-basic', etc.
        attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | © OpenStreetMap contributors'
      }).addTo(map);
      
      const markers = [];
      const path = [];
      
      // Add markers for each location
      locations.forEach((location, index) => {
        const lat = parseFloat(location.latitude);
        const lng = parseFloat(location.longitude);
        path.push([lat, lng]);
        
        // Create a custom icon with a number
        const customIcon = L.divIcon({
          className: 'custom-map-marker',
          html: `<div style="background-color: #4CAF50; color: white; border-radius: 50%; width: 25px; height: 25px; text-align: center; line-height: 25px; font-weight: bold;">${index + 1}</div>`,
          iconSize: [25, 25],
          iconAnchor: [12, 12],
          popupAnchor: [0, -10],
        });
        
        // Create marker with custom icon
        const marker = L.marker([lat, lng], {
          title: location.location_name,
          icon: customIcon
        }).addTo(map);
        
        // Create popup with location info
        marker.bindPopup(`<b>${index + 1}. ${location.location_name}</b><br>${location.city_name}, ${location.state_name}`);
        
        markers.push(marker);
      });
      
      // Create a polyline for the route
      const routeLine = L.polyline(path, {
        color: '#4CAF50',
        weight: 3,
        opacity: 1
      }).addTo(map);
      
      // Fit map bounds to show all markers
      if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1)); // Add 10% padding around the bounds
      }
      
      // Function to get route between two locations using Geoapify Routing API
      function getRoute(startLat, startLng, endLat, endLng) {
        const apiKey = 'YOUR_GEOAPIFY_API_KEY';
        const url = `https://api.geoapify.com/v1/routing?waypoints=${startLat},${startLng}|${endLat},${endLng}&mode=drive&apiKey=${apiKey}`;
        
        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data.features && data.features.length > 0) {
              // Extract the route coordinates
              const coordinates = data.features[0].geometry.coordinates;
              
              // Convert coordinates format from [lng, lat] to [lat, lng] for Leaflet
              const routeCoords = coordinates.map(coord => [coord[1], coord[0]]);
              
              // Create a polyline for the detailed route
              const detailedRoute = L.polyline(routeCoords, {
                color: '#4CAF50',
                weight: 4,
                opacity: 0.8
              }).addTo(map);
            }
          })
          .catch(error => console.error('Error fetching route:', error));
      }
      
      // Optionally, get detailed routes between consecutive locations
      // Note: This can result in many API calls if you have many locations
      /*
      for (let i = 0; i < locations.length - 1; i++) {
        const start = locations[i];
        const end = locations[i + 1];
        getRoute(
          start.latitude, start.longitude,
          end.latitude, end.longitude
        );
      }
      */
    }
    
    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', initMap);
  </script>
</body>
</html>