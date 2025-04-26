<?php
// search_flights.php

// Database connection details (replace with your actual credentials)
$servername = "localhost";
$username = "root";
$password = ""; // Your MySQL password
$dbname = "airline_reservation";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set header to return JSON
header('Content-Type: application/json');

// Get search parameters from POST request
$origin = $_POST['origin'] ?? '';
$destination = $_POST['destination'] ?? '';
$departure_date = $_POST['departure_date'] ?? '';

// Basic validation
if (empty($origin) || empty($destination) || empty($departure_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide origin, destination, and departure date.']);
    $conn->close();
    exit();
}

// Prepare SQL query to search for flights
// Using prepared statements to prevent SQL injection
$sql = "SELECT flight_id, flight_number, origin, destination, departure_time, arrival_time, available_seats, price
        FROM flights
        WHERE origin = ? AND destination = ? AND DATE(departure_time) = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
    $conn->close();
    exit();
}

// Bind parameters
$stmt->bind_param("sss", $origin, $destination, $departure_date);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

$flights = [];
if ($result->num_rows > 0) {
    // Fetch all results
    while($row = $result->fetch_assoc()) {
        // Calculate duration
        $departure_timestamp = strtotime($row['departure_time']);
        $arrival_timestamp = strtotime($row['arrival_time']);
        $duration_seconds = $arrival_timestamp - $departure_timestamp;

        // Format duration (e.g., H hours M minutes)
        $hours = floor($duration_seconds / 3600);
        $minutes = floor(($duration_seconds % 3600) / 60);
        $row['duration'] = sprintf('%d hours %d minutes', $hours, $minutes);

        $flights[] = $row;
    }
    echo json_encode(['status' => 'success', 'flights' => $flights]);
} else {
    // No flights found
    echo json_encode(['status' => 'success', 'flights' => []]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
