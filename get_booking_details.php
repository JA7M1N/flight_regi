<?php
// get_booking_details.php

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

// Get booking ID from GET request
$booking_id = $_GET['booking_id'] ?? null;

// Basic validation
if (empty($booking_id)) {
    echo json_encode(['status' => 'error', 'message' => 'No booking ID provided.']);
    $conn->close();
    exit();
}

// Prepare SQL query to fetch booking, flight, AND passenger details
// Join bookings, flights, and passengers tables
$sql = "SELECT
            b.booking_id,
            b.booking_time,
            b.number_of_passengers,
            b.total_price,
            b.status,
            f.flight_number,
            f.origin,
            f.destination,
            f.departure_time,
            f.arrival_time,
            p.first_name,
            p.last_name,
            p.email
        FROM
            bookings b
        JOIN
            flights f ON b.flight_id = f.flight_id
        JOIN
            passengers p ON b.passenger_id = p.passenger_id
        WHERE
            b.booking_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
    $conn->close();
    exit();
}

// Bind parameter
$stmt->bind_param("i", $booking_id);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

$booking_details = null;
if ($result->num_rows > 0) {
    // Fetch the booking details
    $booking_details = $result->fetch_assoc();

    // Calculate duration for the confirmation page
    $departure_timestamp = strtotime($booking_details['departure_time']);
    $arrival_timestamp = strtotime($booking_details['arrival_time']);
    $duration_seconds = $arrival_timestamp - $departure_timestamp;
    $hours = floor($duration_seconds / 3600);
    $minutes = floor(($duration_seconds % 3600) / 60);
    $booking_details['duration'] = sprintf('%d hours %d minutes', $hours, $minutes);


    // Return all details including passenger info and duration
    echo json_encode(['status' => 'success', 'booking' => $booking_details]);
} else {
    // Booking not found
    echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
