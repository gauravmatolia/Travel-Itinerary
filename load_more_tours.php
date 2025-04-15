<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 3;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

$sql = "SELECT tour_id, tour_name, tour_description, price, location, image_url FROM tours WHERE is_featured = TRUE ORDER BY price ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$tours = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tours[] = $row;
    }
}

$countSql = "SELECT COUNT(*) as remaining FROM tours WHERE is_featured = TRUE";
$countResult = $conn->query($countSql);
$totalTours = $countResult->fetch_assoc()['remaining'];
$hasMore = ($offset + $limit) < $totalTours;


echo json_encode([
    'tours' => $tours,
    'hasMore' => $hasMore,
    'offset' => $offset,
    'limit' => $limit
]);

$conn->close();
?>