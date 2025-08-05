<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Database connection details
// For local development
$servername = "localhost";
$username = "ux7p426rgegze";
$password = "d14cdc)L61_$";
$dbname = "dbgk2bqd1ybqww";

// For hosting environment (uncomment and use these instead)
// $servername = "localhost";
// $username = "your_hosting_username";
// $password = "your_hosting_password";
// $dbname = "your_hosting_database";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8
    $conn->set_charset("utf8");

    // Create tables if they don't exist
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($users_table)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    $movies_table = "CREATE TABLE IF NOT EXISTS movies (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        genre VARCHAR(50) NOT NULL,
        video_url VARCHAR(255) NOT NULL,
        thumbnail VARCHAR(255) NOT NULL,
        user_id INT(11) NOT NULL,
        category VARCHAR(50) DEFAULT 'regular',
        year VARCHAR(4) DEFAULT '2025',
        duration VARCHAR(10) DEFAULT '1h 30m',
        rating VARCHAR(10) DEFAULT 'PG-13',
        views INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";

    if (!$conn->query($movies_table)) {
        throw new Exception("Error creating movies table: " . $conn->error);
    }
} catch (Exception $e) {
    // Log error to file
    error_log("Database Error: " . $e->getMessage() . " in " . __FILE__ . " on line " . __LINE__);
    
    // Display user-friendly error message
    die("Database connection error. Please try again later or contact support.");
}
?>
