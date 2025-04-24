<?php
// Start session to track user login status
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

// Initialize variables
$searchResults = [];
$searchTerm = '';

// Process search query
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchTerm = $conn->real_escape_string($searchTerm);
    
    // First check if the search term matches a state
    $stateQuery = "SELECT s.state_id, s.state_name 
                   FROM states s 
                   WHERE s.state_name LIKE '%$searchTerm%'";
    $stateResult = $conn->query($stateQuery);
    
    if ($stateResult->num_rows > 0) {
        // It's a state search, group locations by city
        while ($stateRow = $stateResult->fetch_assoc()) {
            $stateId = $stateRow['state_id'];
            $stateName = $stateRow['state_name'];
            
            // Get cities in this state
            $cityQuery = "SELECT c.city_id, c.city_name 
                         FROM cities c 
                         WHERE c.state_id = $stateId";
            $cityResult = $conn->query($cityQuery);
            
            if ($cityResult->num_rows > 0) {
                while ($cityRow = $cityResult->fetch_assoc()) {
                    $cityId = $cityRow['city_id'];
                    $cityName = $cityRow['city_name'];
                    
                    // Get locations for this city
                    $locationQuery = "SELECT tl.location_id, tl.location_name, tl.image_url, tl.latitude, tl.longitude 
                                     FROM travel_locations tl 
                                     WHERE tl.city_id = $cityId";
                    $locationResult = $conn->query($locationQuery);
                    
                    $locations = [];
                    while ($locRow = $locationResult->fetch_assoc()) {
                        $locations[] = [
                            'id' => $locRow['location_id'],
                            'name' => $locRow['location_name'],
                            'image_url' => $locRow['image_url'],
                            'latitude' => $locRow['latitude'],
                            'longitude' => $locRow['longitude']
                        ];
                    }
                    
                    // Only add cities that have locations
                    if (!empty($locations)) {
                        $searchResults[] = [
                            'state_name' => $stateName,
                            'city_name' => $cityName,
                            'locations' => $locations
                        ];
                    }
                }
            }
        }
    } else {
        // Check if it's a city search
        $cityQuery = "SELECT c.city_id, c.city_name, s.state_name 
                     FROM cities c
                     JOIN states s ON c.state_id = s.state_id 
                     WHERE c.city_name LIKE '%$searchTerm%'";
        $cityResult = $conn->query($cityQuery);
        
        if ($cityResult->num_rows > 0) {
            while ($cityRow = $cityResult->fetch_assoc()) {
                $cityId = $cityRow['city_id'];
                $cityName = $cityRow['city_name'];
                $stateName = $cityRow['state_name'];
                
                // Get locations for this city
                $locationQuery = "SELECT tl.location_id, tl.location_name, tl.image_url, tl.latitude, tl.longitude 
                                 FROM travel_locations tl 
                                 WHERE tl.city_id = $cityId";
                $locationResult = $conn->query($locationQuery);
                
                $locations = [];
                while ($locRow = $locationResult->fetch_assoc()) {
                    $locations[] = [
                        'id' => $locRow['location_id'],
                        'name' => $locRow['location_name'],
                        'image_url' => $locRow['image_url'],
                        'latitude' => $locRow['latitude'],
                        'longitude' => $locRow['longitude']
                    ];
                }
                
                // Only add cities that have locations
                if (!empty($locations)) {
                    $searchResults[] = [
                        'state_name' => $stateName,
                        'city_name' => $cityName,
                        'locations' => $locations
                    ];
                }
            }
        }
    }
}

// Get featured locations if no search is performed
$featuredLocations = [];
if (empty($searchTerm)) {
    $query = "SELECT tl.location_id, tl.location_name, tl.image_url, tl.latitude, tl.longitude, c.city_name
              FROM travel_locations tl
              JOIN cities c ON tl.city_id = c.city_id
              ORDER BY RAND() LIMIT 8";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $featuredLocations[] = [
                'id' => $row['location_id'],
                'name' => $row['location_name'],
                'image_url' => $row['image_url'],
                'city_name' => $row['city_name'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude']
            ];
        }
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['itinerary_name']) && isset($_POST['locations']) && !empty($_POST['locations'])) {
        $itineraryName = $conn->real_escape_string($_POST['itinerary_name']);
        $userId = $_SESSION['user_id'];
        $locations = json_decode($_POST['locations'], true);
        
        if (!empty($locations)) {
            // Insert into itineraries table
            $sql = "INSERT INTO itineraries (user_id, itinerary_name, created_at) 
                    VALUES ($userId, '$itineraryName', NOW())";
            
            if ($conn->query($sql) === TRUE) {
                $itineraryId = $conn->insert_id;
                
                // Insert each location into itinerary_locations table
                $insertSuccess = true;
                foreach ($locations as $index => $location) {
                    $locationId = $conn->real_escape_string($location['id']);
                    $orderNum = $index + 1;
                    
                    $sql = "INSERT INTO itinerary_locations (itinerary_id, location_id, location_order) 
                            VALUES ($itineraryId, $locationId, $orderNum)";
                    
                    if ($conn->query($sql) !== TRUE) {
                        $insertSuccess = false;
                        error_log("Error inserting location: " . $conn->error);
                    }
                }
                
                if ($insertSuccess) {
                    // Redirect to self_itinerary.php with the new itinerary ID
                    header("Location: self_itinerary.php?id=$itineraryId");
                    exit();
                } else {
                    echo "Error: Failed to save some locations";
                }
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Travel Itinerary</title>
  <link rel="stylesheet" href="assets/styles/create_itinerary_styles.css" />
  <style>
    .search-results {
      margin-top: 20px;
    }
    .state-city-section {
      margin-bottom: 40px;
    }
    .city-section {
      margin-bottom: 30px;
    }
    .state-title {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: #2a5885;
      border-bottom: 1px solid #e0e0e0;
      padding-bottom: 5px;
    }
    .city-title {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: #333;
      padding-left: 15px;
      position: relative;
    }
    .city-title:before {
      content: "•";
      color: #4CAF50;
      font-size: 1.5rem;
      position: absolute;
      left: 0;
    }
    .location-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .location-box {
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s;
      background-color: white;
    }
    .location-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .location-box.selected {
      border: 2px solid #4CAF50;
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
    }
    .location-box img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }
    .location-box p {
      padding: 10px;
      text-align: center;
      margin: 0;
      font-weight: 500;
    }
    .location-city {
      font-size: 0.8rem;
      color: #666;
      text-align: center;
      padding-bottom: 10px;
      margin-top: -5px;
    }
    .travel-queue {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-top: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .queue-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin: 15px 0;
      max-height: 300px;
      overflow-y: auto;
    }
    .queue-item {
      background-color: #fff;
      padding: 10px 15px;
      border-radius: 5px;
      border-left: 4px solid #4CAF50;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: black;
    }
    .remove-btn {
      background-color: #ff5252;
      color: white;
      border: none;
      border-radius: 50%;
      width: 25px;
      height: 25px;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .proceed-btn {
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      width: 100%;
      transition: background-color 0.3s;
    }
    .proceed-btn:hover {
      background-color: #45a049;
    }
    .search-form {
      display: flex;
      align-items: center;
      width: 100%;
    }
    .search-box {
      flex-grow: 1;
    }
    .search-box input {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      width: 100%;
      font-size: 16px;
    }
    .search-button {
      background: none;
      border: none;
      cursor: pointer;
    }
    .search-icon {
      width: 24px;
      height: 24px;
      margin-left: 10px;
    }
    .itinerary-name-input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    .no-results {
      text-align: center;
      padding: 30px;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="hero">
  <?php include('navbar2.php'); ?>

  <br><br><br>
    
    <section class="itinerary-section">
      <h2>Creating Your Travel Itinerary</h2>
      
      <!-- <form class="search-form" action="" method="GET">
        <div class="search-box">
          <input type="text" name="search" placeholder="Search destinations by city or state" value="<?php echo htmlspecialchars($searchTerm); ?>">
        </div>
        <button type="submit" class="search-button">
          <img src="assets/images/search-icon.png" alt="Search" class="search-icon">
        </button>
      </form> -->
      
      <?php if (!empty($searchTerm)): ?>
        <div class="search-results">
          <?php if (empty($searchResults)): ?>
            <div class="no-results">
              <h3>No results found for "<?php echo htmlspecialchars($searchTerm); ?>"</h3>
              <p>Try searching for a different city or state name.</p>
            </div>
          <?php else: ?>
            <?php foreach ($searchResults as $result): ?>
              <div class="state-city-section">
                <h3 class="city-title">
                  <?php echo htmlspecialchars($result['city_name']); ?>, 
                  <?php echo htmlspecialchars($result['state_name']); ?>
                </h3>
                <div class="location-grid">
                  <?php foreach ($result['locations'] as $location): ?>
                    <div class="location-box" 
                         data-id="<?php echo $location['id']; ?>" 
                         data-name="<?php echo htmlspecialchars($location['name']); ?>"
                         data-city="<?php echo htmlspecialchars($result['city_name']); ?>"
                         data-state="<?php echo htmlspecialchars($result['state_name']); ?>"
                         data-lat="<?php echo $location['latitude']; ?>"
                         data-lng="<?php echo $location['longitude']; ?>">
                      <img src="<?php echo htmlspecialchars($location['image_url']); ?>" 
                           alt="<?php echo htmlspecialchars($location['name']); ?>"
                           onerror="this.src='assets/images/placeholder.jpg'">
                      <p><?php echo htmlspecialchars($location['name']); ?></p>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <h3>Featured Locations</h3>
        <div class="location-grid">
          <?php foreach ($featuredLocations as $location): ?>
            <div class="location-box" 
                 data-id="<?php echo $location['id']; ?>" 
                 data-name="<?php echo htmlspecialchars($location['name']); ?>"
                 data-city="<?php echo htmlspecialchars($location['city_name']); ?>"
                 data-lat="<?php echo $location['latitude']; ?>"
                 data-lng="<?php echo $location['longitude']; ?>">
              <img src="<?php echo htmlspecialchars($location['image_url']); ?>" 
                   alt="<?php echo htmlspecialchars($location['name']); ?>"
                   onerror="this.src='assets/images/placeholder.jpg'">
              <p><?php echo htmlspecialchars($location['name']); ?></p>
              <div class="location-city"><?php echo htmlspecialchars($location['city_name']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    
      <div class="travel-queue">
        <h3>Your Travel Queue</h3>
        <div class="queue-list" id="queue-list">
          <!-- Queue items will be added here dynamically -->
        </div>
        <form id="itinerary-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <input type="text" name="itinerary_name" class="itinerary-name-input" placeholder="Name your itinerary" required>
          <input type="hidden" name="locations" id="selected-locations">
          <button type="submit" class="proceed-btn">Create Itinerary</button>
        </form>
      </div>
    </section>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const locationBoxes = document.querySelectorAll('.location-box');
      const queueList = document.getElementById('queue-list');
      const selectedLocationsInput = document.getElementById('selected-locations');
      const itineraryForm = document.getElementById('itinerary-form');
      
      // Store selected locations
      let selectedLocations = [];
      
      // Add click event to all location boxes
      locationBoxes.forEach(box => {
        box.addEventListener('click', function() {
          const locationId = this.dataset.id;
          const locationName = this.dataset.name;
          const cityName = this.dataset.city;
          const stateName = this.dataset.state || '';
          const latitude = this.dataset.lat;
          const longitude = this.dataset.lng;
          
          // Check if location is already in the queue
          const existingIndex = selectedLocations.findIndex(loc => loc.id === locationId);
          
          if (existingIndex === -1) {
            // Add to selected locations array
            selectedLocations.push({
              id: locationId,
              name: locationName,
              city: cityName,
              state: stateName,
              latitude: latitude,
              longitude: longitude
            });
            
            // Add visual selected class
            this.classList.add('selected');
            
            // Add to visual queue
            addToQueue(locationId, locationName, cityName, stateName);
          } else {
            // Remove from selected locations array
            selectedLocations.splice(existingIndex, 1);
            
            // Remove visual selected class
            this.classList.remove('selected');
            
            // Remove from visual queue
            const itemToRemove = queueList.querySelector(`.remove-btn[data-id="${locationId}"]`).parentNode;
            queueList.removeChild(itemToRemove);
          }
          
          // Update hidden input
          updateSelectedLocationsInput();
        });
      });
      
      // Function to add location to queue
      function addToQueue(id, name, city, state) {
        const queueItem = document.createElement('div');
        queueItem.className = 'queue-item';
        
        const locationText = state 
          ? `${name} (${city}, ${state})` 
          : `${name} (${city})`;
        
        queueItem.innerHTML = `
          ${locationText}
          <button type="button" class="remove-btn" data-id="${id}">×</button>
        `;
        queueList.appendChild(queueItem);
        
        // Add remove event listener
        queueItem.querySelector('.remove-btn').addEventListener('click', function(e) {
          e.stopPropagation(); // Prevent event bubbling
          const locationId = this.dataset.id;
          removeFromQueue(locationId);
          
          // Remove selected class from location box
          const locationBox = document.querySelector(`.location-box[data-id="${locationId}"]`);
          if (locationBox) {
            locationBox.classList.remove('selected');
          }
        });
      }
      
      // Function to remove location from queue
      function removeFromQueue(id) {
        // Remove from array
        selectedLocations = selectedLocations.filter(loc => loc.id !== id);
        
        // Remove from DOM
        const itemToRemove = queueList.querySelector(`.remove-btn[data-id="${id}"]`).parentNode;
        queueList.removeChild(itemToRemove);
        
        // Update hidden input
        updateSelectedLocationsInput();
      }
      
      // Update the hidden input with selected locations
      function updateSelectedLocationsInput() {
        selectedLocationsInput.value = JSON.stringify(selectedLocations);
      }
      
      // Form submission handler
      itineraryForm.addEventListener('submit', function(e) {
        if (selectedLocations.length === 0) {
          e.preventDefault();
          alert('Please select at least one location for your itinerary.');
        }
      });
    });
  </script>
</body>
</html>