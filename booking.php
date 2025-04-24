<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if tour_id is set
if (!isset($_GET['tour_id'])) {
    header("Location: tours.php");
    exit();
}

$tour_id = $_GET['tour_id'];
$user_id = $_SESSION['user_id'];

// Get tour details
$stmt = $conn->prepare("SELECT title, description, price, image_url FROM tours WHERE tour_id = ?");
$stmt->bind_param("i", $tour_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: tours.php");
    exit();
}

$tour = $result->fetch_assoc();
$stmt->close();

// Handle payment submission
$payment_success = false;
$payment_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST['payment_method'];
    $payment_amount = $tour['price'];
    
    // Validate and sanitize input
    if ($payment_method !== 'card' && $payment_method !== 'upi') {
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
                if (strlen($card_number) < 13 || strlen($card_number) > 19 || !is_numeric($card_number)) {
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
                    $masked_card = 'XXXX-XXXX-XXXX-' . substr($card_number, -4);
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
            $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_amount, payment_method, card_details, upi_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $user_id, $payment_amount, $payment_method, $card_details, $upi_id);
            
            if ($stmt->execute()) {
                $payment_id = $stmt->insert_id;
                
                // Now create a booking entry (assuming you have a bookings table)
                $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, tour_id, payment_id, booking_date) VALUES (?, ?, ?, NOW())");
                $booking_stmt->bind_param("iii", $user_id, $tour_id, $payment_id);
                
                if ($booking_stmt->execute()) {
                    $payment_success = true;
                } else {
                    $payment_error = "Failed to create booking. Please try again.";
                }
                $booking_stmt->close();
            } else {
                $payment_error = "Payment processing failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Tour - TravelGo</title>
    <link rel="stylesheet" href="assets/styles/tours_styles.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tour-summary {
            display: flex;
            margin-bottom: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tour-summary-image {
            width: 250px;
            height: 180px;
            object-fit: cover;
        }
        
        .tour-summary-details {
            padding: 15px;
            flex-grow: 1;
        }
        
        .payment-form {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            border-color: #FF8C00;
            background-color: rgba(255, 140, 0, 0.1);
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
            background-color: #FF8C00;
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
            background-color: #e67e00;
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
    </style>
</head>
<body>
<?php include('navbar2.php'); ?>
<br><br><br>

<div class="booking-container">
    <h1>Book Your Tour</h1>
    
    <?php if ($payment_success): ?>
        <div class="success-message">
            <h2>Payment Successful!</h2>
            <p>Your booking for <?php echo htmlspecialchars($tour['title']); ?> has been confirmed.</p>
            <p>Thank you for choosing TravelGo!</p>
            <a href="my_bookings.php" class="submit-btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">View My Bookings</a>
        </div>
    <?php else: ?>
        <div class="tour-summary">
            <img src="<?php echo htmlspecialchars($tour['image_url']); ?>" alt="<?php echo htmlspecialchars($tour['title']); ?>" class="tour-summary-image">
            <div class="tour-summary-details">
                <h2><?php echo htmlspecialchars($tour['title']); ?></h2>
                <p><?php echo htmlspecialchars($tour['description']); ?></p>
                <div class="tour-price" style="font-size: 1.5em; margin-top: 15px;">₹<?php echo number_format($tour['price'], 2); ?></div>
            </div>
        </div>
        
        <?php if (!empty($payment_error)): ?>
            <div class="error-message"><?php echo $payment_error; ?></div>
        <?php endif; ?>
        
        <div class="payment-form">
            <h2>Payment Details</h2>
            <form method="post" action="">
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
                
                <button type="submit" class="submit-btn">Pay ₹<?php echo number_format($tour['price'], 2); ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
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