<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Self Itinerary</title>
  <link rel="stylesheet" href="assets/styles/self_itinerary.css" />
</head>
<body>

    <div class="hero">
      <?php include('navbar.php'); ?>

    <h1 class="main-title">Your Itinerary</h1>

    <div class="input-box">
      <div class="input-group">
        <label>Current Location</label>
        <input type="text" placeholder="Enter Location" />
      </div>
      <br>
      <div class="input-group">
        <label>Start Date</label>
        <input type="date" placeholder="Select Date" id="start-date" />
      </div>
      <br>
      <div class="input-group">
        <label>End Date</label>
        <input type="date" placeholder="Select Date" id="end-date" />
      </div>
    </div>

    <p class="subtitle">Preparing the best plan for your travel</p>

    <?php
    // Start session to retrieve travel data
    session_start();
    
    // Check if travel data exists in session
    if(isset($_SESSION['travel_data'])) {
        $travelData = $_SESSION['travel_data'];
        $optimizedRoute = $travelData['optimized_route'];
        $totalDistance = $travelData['total_distance_km'];
        
        // Calculate estimated cost (example: $10 per km)
        $estimatedCost = number_format($totalDistance * 10, 2);
        
        // Determine how many days we need (2 locations per day)
        $locationsPerDay = 2;
        $totalDays = ceil(count($optimizedRoute) / $locationsPerDay);
        
        // Create an array of days with their locations
        $dayLocations = [];
        for($day = 0; $day < $totalDays; $day++) {
            $startIndex = $day * $locationsPerDay;
            $dayLocations[$day] = array_slice($optimizedRoute, $startIndex, $locationsPerDay);
        }
    } else {
        // Default data if no session data exists
        $dayLocations = [[]];
        $totalDistance = 0;
        $estimatedCost = "0.00";
        $totalDays = 1;
    }
    
    // Function to get date string based on start date
    function getDateString($startDateObj, $dayOffset) {
        $date = clone $startDateObj;
        $date->modify("+$dayOffset days");
        return $date->format('M j');
    }
    ?>

    <div class="itinerary-box">
      <?php
      // Get today's date for default
      $startDate = new DateTime();
      
      // Display each day's itinerary
      for($dayNumber = 0; $dayNumber < $totalDays; $dayNumber++):
      ?>
      <div class="day">
        <h2>Day <?php echo $dayNumber + 1; ?> (<?php echo getDateString($startDate, $dayNumber); ?>)</h2>
        <ul class="events">
          <?php if($dayNumber == 0): ?>
          <li><span>Flight to Destination</span><span>5:00AM–3:00PM (10 hrs)</span></li>
          <li><span>Hotel Checkin</span><span>4:00PM</span></li>
          <?php endif; ?>
          
          <?php foreach($dayLocations[$dayNumber] as $index => $location): ?>
          <li><span><?php echo htmlspecialchars($location['name']); ?></span>
              <span><?php echo (6 + $index * 2) ?>:00PM–<?php echo (8 + $index * 2) ?>:00PM (2 hrs)</span></li>
          <?php endforeach; ?>
          
          <?php if($dayNumber == $totalDays - 1): ?>
          <li><span>Hotel Checkout</span><span>5:00PM</span></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endfor; ?>
    </div>

    <div class="cost-box">
      <span>Total Cost :</span>
      <button class="price">$<?php echo $estimatedCost; ?></button>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Set default dates (today and future date based on total days needed)
    const today = new Date();
    const endDate = new Date(today);
    endDate.setDate(endDate.getDate() + <?php echo max(1, $totalDays - 1); ?>);
    
    // Format dates for input fields (YYYY-MM-DD)
    const formatDate = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    
    document.getElementById('start-date').value = formatDate(today);
    document.getElementById('end-date').value = formatDate(endDate);
  });
  </script>
</body>
</html>