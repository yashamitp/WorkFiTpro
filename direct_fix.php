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

// Try to directly execute the SQL statements
try {
    // First, try to add the quantity column
    $conn->query("ALTER TABLE diet_entries ADD COLUMN quantity FLOAT DEFAULT 1 AFTER food_name");
    echo "Added quantity column (or it already exists).<br>";
} catch (Exception $e) {
    echo "Note: " . $e->getMessage() . "<br>";
}

try {
    // Then, try to add the quantity_unit column
    $conn->query("ALTER TABLE diet_entries ADD COLUMN quantity_unit VARCHAR(20) DEFAULT 'serving' AFTER quantity");
    echo "Added quantity_unit column (or it already exists).<br>";
} catch (Exception $e) {
    echo "Note: " . $e->getMessage() . "<br>";
}

// Show the current table structure
$result = $conn->query("DESCRIBE diet_entries");
echo "<h3>Current Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<p>Database fix completed. <a href='nutrition_finder.php'>Return to Nutrition Finder</a></p>";

// Close connection
$conn->close();
?>