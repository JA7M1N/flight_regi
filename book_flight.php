<?php
// book_flight.php (This is a simplified example)

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

// Get booking parameters from POST request (example parameters)
$flight_id = $_POST['flight_id'] ?? null;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$number_of_passengers = $_POST['number_of_passengers'] ?? 1; // Default to 1 passenger

// Basic validation
if (empty($flight_id) || empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide all required booking details.']);
    $conn->close();
    exit();
}

// Start a transaction
$conn->begin_transaction();

try {
    // 1. Check flight availability and get price
    $sql_flight = "SELECT price, available_seats FROM flights WHERE flight_id = ? FOR UPDATE"; // Lock row for update
    $stmt_flight = $conn->prepare($sql_flight);
    $stmt_flight->bind_param("i", $flight_id);
    $stmt_flight->execute();
    $result_flight = $stmt_flight->get_result();

    if ($result_flight->num_rows === 0) {
        throw new Exception("Flight not found.");
    }

    $flight = $result_flight->fetch_assoc();
    $price_per_passenger = $flight['price'];
    $available_seats = $flight['available_seats'];

    if ($available_seats < $number_of_passengers) {
        throw new Exception("Not enough seats available.");
    }

    $total_price = $price_per_passenger * $number_of_passengers;

    // 2. Check if passenger already exists or insert new passenger
    $sql_passenger = "SELECT passenger_id FROM passengers WHERE email = ?";
    $stmt_passenger = $conn->prepare($sql_passenger);
    $stmt_passenger->bind_param("s", $email);
    $stmt_passenger->execute();
    $result_passenger = $stmt_passenger->get_result();

    $passenger_id = null;
    if ($result_passenger->num_rows > 0) {
        $passenger = $result_passenger->fetch_assoc();
        $passenger_id = $passenger['passenger_id'];
    } else {
        // Insert new passenger
        $sql_insert_passenger = "INSERT INTO passengers (first_name, last_name, email) VALUES (?, ?, ?)";
        $stmt_insert_passenger = $conn->prepare($sql_insert_passenger);
        $stmt_insert_passenger->bind_param("sss", $first_name, $last_name, $email);
        if (!$stmt_insert_passenger->execute()) {
             throw new Exception("Failed to add passenger: " . $stmt_insert_passenger->error);
        }
        $passenger_id = $conn->insert_id; // Get the ID of the newly inserted passenger
    }

    // 3. Create the booking
    $sql_booking = "INSERT INTO bookings (flight_id, passenger_id, number_of_passengers, total_price, status) VALUES (?, ?, ?, ?, 'Confirmed')";
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("iiid", $flight_id, $passenger_id, $number_of_passengers, $total_price);
     if (!$stmt_booking->execute()) {
         throw new Exception("Failed to create booking: " . $stmt_booking->error);
     }
    $booking_id = $conn->insert_id;

    // 4. Update available seats in flights table
    $sql_update_seats = "UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?";
    $stmt_update_seats = $conn->prepare($sql_update_seats);
    $stmt_update_seats->bind_param("ii", $number_of_passengers, $flight_id);
     if (!$stmt_update_seats->execute()) {
         throw new Exception("Failed to update seats: " . $stmt_update_seats->error);
     }


    // Commit the transaction if all steps were successful
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Booking confirmed!', 'booking_id' => $booking_id]);

} catch (Exception $e) {
    // Rollback the transaction in case of any error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Booking failed: ' . $e->getMessage()]);
} finally {
    // Close statements and connection
    if (isset($stmt_flight)) $stmt_flight->close();
    if (isset($stmt_passenger)) $stmt_passenger->close();
    if (isset($stmt_insert_passenger)) $stmt_insert_passenger->close();
    if (isset($stmt_booking)) $stmt_booking->close();
    if (isset($stmt_update_seats)) $stmt_update_seats->close();
    $conn->close();
}
?>
