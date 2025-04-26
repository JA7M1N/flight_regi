-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS flight_booking;

-- Select the database to use
USE flight_booking;

-- Create the bookings table if it doesn't exist
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departure_location VARCHAR(255) NOT NULL,
    arrival_location VARCHAR(255) NOT NULL,
    departure_date DATE NOT NULL,
    passengers INT NOT NULL
);

-- Optionally, you can pre-populate the table with some data for testing
-- INSERT INTO bookings (departure_location, arrival_location, departure_date, passengers) VALUES
-- ('New York', 'Los Angeles', '2024-03-10', 2),
-- ('Chicago', 'Houston', '2024-03-15', 1),
-- ('Los Angeles', 'New York', '2024-03-20', 3);
