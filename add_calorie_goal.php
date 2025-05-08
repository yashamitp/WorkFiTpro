<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration file to get the database connection
require_once 'config.php';

// We already have a connection from config.php in the $conn variable

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to database.<br>";

// Check if the calorie_goal column already exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'calorie_goal'");
$column_exists = $result->num_rows > 0;

if (!$column_exists) {
    // Add the calorie_goal column
    $sql = "ALTER TABLE users ADD COLUMN calorie_goal INT DEFAULT 2000";
    if ($conn->query($sql) === TRUE) {
        echo "Added 'calorie_goal' column to users table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "The 'calorie_goal' column already exists in the users table.<br>";
}

// Show the current table structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Current Users Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<p>Database update completed. <a href='diet_tracker.php'>Return to Diet Tracker</a></p>";

// Close connection
$conn->close();
?>