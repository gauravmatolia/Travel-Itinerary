<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if id is set (from self_itinerary.php)
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$itinerary_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get itinerary details from the user's custom itinerary
$query = "SELECT i.*, u.username 
          FROM itineraries i
          JOIN users u ON i.user_id = u.user_id
          WHERE i.itinerary_id = ? AND i.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $itinerary_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$itinerary = $result->fetch_assoc();
$stmt->close();

// Get locations in the itinerary
$query = "SELECT tl.location_id, tl.location_name, tl.description, tl.image_url, 
                 tl.latitude, tl.longitude,
                 c.city_name, s.state_name,
                 il.location_order
          FROM itinerary_locations il
          JOIN travel_locations tl ON il.location_id = tl.location_id
          JOIN cities c ON tl.city_id = c.city_id
          JOIN states s ON c.state_id = s.state_id
          WHERE il.itinerary_id = ?
          ORDER BY il.location_order";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $itinerary_id);
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}
$stmt->close();

// Calculate travel time (assuming average speed of 50 km/h)
$averageSpeed = 50; // km/h
$travelTimeHours = $itinerary['total_distance'] / $averageSpeed;
$travelTimeDays = ceil($travelTimeHours / 8); // Assuming 8 hours of travel per day

// Handle payment submission
$payment_success = false;
$payment_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST['payment_method'];
    $payment_amount = $itinerary['estimated_price']; // Using the estimated price from the itinerary
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Validate dates
    $start_date_obj = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $min_days = $travelTimeDays; // Minimum days based on travel time
    $today = new DateTime();
    
    if ($start_date_obj < $today) {
        $payment_error = "Start date cannot be in the past";
    } elseif ($end_date_obj <= $start_date_obj) {
        $payment_error = "End date must be after start date";
    } elseif ($end_date_obj->diff($start_date_obj)->days < $min_days) {
        $payment_error = "Your trip needs at least " . $min_days . " days based on the distances";
    } elseif ($payment_method !== 'card' && $payment_method !== 'upi') {
        $payment_error = "Invalid payment method";
    } else {
        // Initialize variables
        $card_details = null;
        $upi_id = null;
        
        // Process based on payment method
        if ($payment_method === 'card') {
            // Validate card details
            if (!isset($_POST['card_number']) || !isset($_POST['card_holder']) || 
                !isset($_POST['expiry_date']) || !isset($_POST['cvv'])) {
                $payment_error = "All card details are required";
            } else {
                $card_number = trim($_POST['card_number']);
                $card_holder = trim($_POST['card_holder']);
                $expiry_date = trim($_POST['expiry_date']);
                $cvv = trim($_POST['cvv']);
                
                // Basic validation
                if (strlen($card_number) < 13 || strlen($card_number) > 19 || !is_numeric(str_replace(' ', '', $card_number))) {
                    $payment_error = "Invalid card number";
                } elseif (empty($card_holder)) {
                    $payment_error = "Card holder name is required";
                } elseif (!preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $expiry_date)) {
                    $payment_error = "Invalid expiry date format (MM/YY)";
                } elseif (strlen($cvv) < 3 || strlen($cvv) > 4 || !is_numeric($cvv)) {
                    $payment_error = "Invalid CVV";
                } else {
                    // In a real application, you'd want to encrypt this data
                    // For demonstration purposes, we're storing it with basic masking
                    $masked_card = 'XXXX-XXXX-XXXX-' . substr(str_replace(' ', '', $card_number), -4);
                    $card_details = json_encode([
                        'card_number' => $masked_card,
                        'card_holder' => $card_holder,
                        'expiry_date' => $expiry_date
                    ]);
                }
            }
        } elseif ($payment_method === 'upi') {
            if (!isset($_POST['upi_id']) || empty($_POST['upi_id'])) {
                $payment_error = "UPI ID is required";
            } else {
                $upi_id = trim($_POST['upi_id']);
                // Basic UPI validation
                if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/', $upi_id)) {
                    $payment_error = "Invalid UPI ID format";
                }
            }
        }
        
        // If validation passes, store payment in database
        if (empty($payment_error)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_amount, payment_method, card_details, upi_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idsss", $user_id, $payment_amount, $payment_method, $card_details, $upi_id);
                
                if ($stmt->execute()) {
                    $payment_id = $stmt->insert_id;
                    
                    // Now create an itinerary booking entry
                    $booking_stmt = $conn->prepare("INSERT INTO itineraries_bookings (user_id, itinerary_id, payment_id, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                    $booking_stmt->bind_param("iiiss", $user_id, $itinerary_id, $payment_id, $start_date, $end_date);
                    
                    if ($booking_stmt->execute()) {
                        // Update the itinerary to mark it as booked
                        $update_stmt = $conn->prepare("UPDATE itineraries_bookings SET status = 'confirmed' WHERE itinerary_id = ? AND user_id = ?");
                        $update_stmt->bind_param("ii", $itinerary_id, $user_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        $conn->commit();
                        $payment_success = true;
                    } else {
                        throw new Exception("Failed to create booking. Please try again.");
                    }
                    $booking_stmt->close();
                } else {
                    throw new Exception("Payment processing failed. Please try again.");
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $payment_error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Your Custom Itinerary - TravelGo</title>
    <link rel="stylesheet" href="assets/styles/self_itinerary.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .itinerary-summary {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            background: rgba( 255, 255, 255, 0.15 );
            box-shadow: 0 8px 32px 0 rgba( 31, 38, 135, 0.37 );
            backdrop-filter: blur( 2px );
            -webkit-backdrop-filter: blur( 2px );
            border-radius: 10px;
            border: 1px solid rgba( 255, 255, 255, 0.18 );
        }
        
        .itinerary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: black;
        }
        
        .location-list {
            margin-bottom: 30px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .location-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: white;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .location-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .location-item .location-info {
            flex: 1;
        }
        
        .location-item .location-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .location-item .location-city {
            font-size: 0.8rem;
            color: #666;
        }
        
        .payment-form {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            /* color: white; */
            background: rgba( 255, 255, 255, 0.15 );
            box-shadow: 0 8px 32px 0 rgba( 31, 38, 135, 0.37 );
            backdrop-filter: blur( 2px );
            -webkit-backdrop-filter: blur( 2px );
            border-radius: 10px;
            border: 1px solid rgba( 255, 255, 255, 0.18 );
        }
        
        .date-selection {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .payment-methods {
            display: flex;
            margin-bottom: 20px;
        }
        
        .payment-method {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .payment-method:last-child {
            margin-right: 0;
        }
        
        .payment-method.active {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .payment-details {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .card-inputs {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 4px;
            margin-top: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        .error-message {
            color: #ff3333;
            margin-top: 20px;
            padding: 10px;
            background-color: rgba(255, 51, 51, 0.1);
            border-radius: 4px;
        }
        
        .success-message {
            color: #33aa33;
            margin-top: 20px;
            padding: 15px;
            background-color: rgba(51, 170, 51, 0.1);
            border-radius: 4px;
            text-align: center;
        }
        
        .payment-details > div {
            display: none;
        }
        
        .payment-details > div.active {
            display: block;
        }
        
        .price-breakdown {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        .price-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
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
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="hero">
    <div class="booking-container"><br>
        <h1>Book Your Custom Itinerary</h1>
        
        <?php if ($payment_success): ?>
            <div class="success-message">
                <h2>Payment Successful!</h2>
                <p>Your booking for <?php echo htmlspecialchars($itinerary['itinerary_name']); ?> has been confirmed.</p>
                <p>Thank you for choosing TravelGo!</p>
                <a href="my_bookings.php" class="submit-btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">View My Bookings</a>
            </div>
        <?php else: ?>
            <br><br>
            <div class="itinerary-summary">
                <h2 style="color: orange;"><?php echo htmlspecialchars($itinerary['itinerary_name']); ?></h2>
                
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
                
                <h3 style="color: orange;">Your Destinations</h3>
                <div class="location-list">
                    <?php foreach ($locations as $index => $location): ?>
                        <div class="location-item">
                            <img src="<?php echo htmlspecialchars($location['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($location['location_name']); ?>"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                            <div class="location-info">
                                <div class="location-name"><?php echo ($index + 1) . '. ' . htmlspecialchars($location['location_name']); ?></div>
                                <div class="location-city">
                                    <?php echo htmlspecialchars($location['city_name']); ?>, 
                                    <?php echo htmlspecialchars($location['state_name']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
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
                </div>
            </div>
            
            <?php if (!empty($payment_error)): ?>
                <div class="error-message"><?php echo $payment_error; ?></div>
            <?php endif; ?>
            
            <div class="payment-form">
                <form method="post" action="">
                    <div class="date-selection">
                        <h2 style="color: orange;">Select Travel Dates</h2><br>
                        <p>Your itinerary requires at least <?php echo $travelTimeDays; ?> days based on the route distances.</p>
                        <br>
                        <div class="date-inputs">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="text" id="start_date" name="start_date" placeholder="Select start date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="text" id="end_date" name="end_date" placeholder="Select end date" required>
                            </div>
                        </div>
                    </div>
                    
                    <h2 style="color: orange;">Payment Details</h2>
                    <div class="payment-methods">
                        <div class="payment-method active" data-method="card">
                            <h3>Credit/Debit Card</h3>
                        </div>
                        <div class="payment-method" data-method="upi">
                            <h3>UPI Payment</h3>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                    
                    <div class="payment-details">
                        <div id="card-payment" class="active">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                            </div>
                            
                            <div class="form-group">
                                <label for="card_holder">Card Holder Name</label>
                                <input type="text" id="card_holder" name="card_holder" placeholder="Name on card">
                            </div>
                            
                            <div class="card-inputs">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                                </div>
                                
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="password" id="cvv" name="cvv" placeholder="XXX" maxlength="4">
                                </div>
                            </div>
                        </div>
                        
                        <div id="upi-payment">
                            <div class="form-group">
                                <label for="upi_id">UPI ID</label>
                                <input type="text" id="upi_id" name="upi_id" placeholder="username@upi">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Pay ₹<?php echo number_format($itinerary['estimated_price']); ?></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
    // Initialize date pickers
    const today = new Date();
    const minDays = <?php echo $travelTimeDays; ?>; // Minimum number of days required
    
    // Start date picker - can only select today or future dates
    const startDatePicker = flatpickr("#start_date", {
        minDate: "today",
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr, instance) {
            // When start date is changed, update end date min date
            if (selectedDates[0]) {
                // Set end date minimum to start date + minimum days required
                const minEndDate = new Date(selectedDates[0]);
                minEndDate.setDate(minEndDate.getDate() + minDays);
                
                endDatePicker.set("minDate", minEndDate);
                
                // If current end date is before new minimum end date, reset it
                const currentEndDate = endDatePicker.selectedDates[0];
                if (currentEndDate && currentEndDate < minEndDate) {
                    endDatePicker.setDate(minEndDate);
                }
            }
        }
    });

    // End date picker - can only select dates after start date + minimum days
    const endDatePicker = flatpickr("#end_date", {
        minDate: new Date().fp_incr(minDays), // Default to today + minimum days
        dateFormat: "Y-m-d"
    });
    
    // Switch between payment methods
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            // Update active class on payment methods
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            this.classList.add('active');
            
            // Update hidden payment method input
            const paymentMethod = this.getAttribute('data-method');
            document.getElementById('payment_method').value = paymentMethod;
            
            // Show corresponding payment details
            document.querySelectorAll('.payment-details > div').forEach(div => div.classList.remove('active'));
            document.getElementById(paymentMethod + '-payment').classList.add('active');
        });
    });
    
    // Format card number with spaces
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        this.value = formattedValue;
    });
    
    // Format expiry date
    document.getElementById('expiry_date').addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        
        if (value.length > 0) {
            if (value.length <= 2) {
                this.value = value;
            } else {
                this.value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
        }
    });
</script>
</body>
</html>

<?php $conn->close(); ?>