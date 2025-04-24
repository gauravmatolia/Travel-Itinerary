<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_itinerary";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : "";

$tours = [];

if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT tour_id, title, description, price, image_url, rating FROM tours WHERE is_public = TRUE AND title LIKE ? ORDER BY price ASC LIMIT ? OFFSET ?");
    $searchPattern = "%" . $searchTerm . "%";
    $stmt->bind_param("sii", $searchPattern, $limit, $offset);
} else {
    $stmt = $conn->prepare("SELECT tour_id, title, description, price, image_url, rating FROM tours WHERE is_public = TRUE ORDER BY price ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tours[] = $row;
}
$stmt->close();

if (!empty($searchTerm)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tours WHERE is_public = TRUE AND title LIKE ?");
    $stmt->bind_param("s", $searchPattern);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tours WHERE is_public = TRUE");
}

$stmt->execute();
$countResult = $stmt->get_result();
$totalTours = $countResult->fetch_assoc()['total'];
$stmt->close();

$hasMore = $offset + count($tours) < $totalTours;

echo json_encode([
    'tours' => $tours,
    'hasMore' => $hasMore
]);

$conn->close();
?>
