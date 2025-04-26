<?php
// Database connection details
$servername = "localhost";  // Change this if your database server is different
$username = "root"; // Replace with your MySQL username
$password = ""; // Replace with your MySQL password
$dbname = "flight_booking";    // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to handle booking a flight
function bookFlight($departureLocation, $arrivalLocation, $departureDate, $passengers) {
    global $conn; // Access the global database connection

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO bookings (departure_location, arrival_location, departure_date, passengers) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $departureLocation, $arrivalLocation, $departureDate, $passengers); // "sssi" indicates the data types of the parameters: string, string, string, integer

    if ($stmt->execute()) {
        return true; // Booking successful
    } else {
        return false; // Booking failed
    }
    $stmt->close();
}

// Function to get all bookings
function getBookings() {
    global $conn;
    $sql = "SELECT * FROM bookings";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $bookings = array(); // Initialize an empty array to store bookings
        // Fetch each row as an associative array
        while($row = $result->fetch_assoc()) {
            $bookings[] = $row; // Add each row to the $bookings array
        }
        return $bookings;
    } else {
        return array(); // Return an empty array if there are no bookings
    }
}

// Function to get most visited destinations.
function getMostVisitedDestinations() {
    global $conn;

    $sql = "SELECT arrival_location, COUNT(*) AS visit_count FROM bookings GROUP BY arrival_location ORDER BY visit_count DESC LIMIT 4";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $destinations = array();
        while ($row = $result->fetch_assoc()) {
            $destinations[] = $row;
        }
        return $destinations;
    } else {
        return array();
    }
}


// Handle form submission (for booking a flight)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_flight'])) {
    $departureLocation = $_POST['departure_location'];
    $arrivalLocation = $_POST['arrival_location'];
    $departureDate = $_POST['departure_date'];
    $passengers = $_POST['passengers'];

    if (bookFlight($departureLocation, $arrivalLocation, $departureDate, $passengers)) {
        echo "Booking successful!"; //  Send this back to the client-side JavaScript using echo
    } else {
        echo "Booking failed."; // Send this back to the client-side JavaScript using echo
    }
}

// Handle fetching bookings
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['get_bookings'])) {
    $bookings = getBookings();
    echo json_encode($bookings); // Send the bookings data as JSON
}

// Handle fetching most visited destinations
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['get_destinations'])) {
    $destinations = getMostVisitedDestinations();
    echo json_encode($destinations);
}

$conn->close(); // Close the database connection
?>
