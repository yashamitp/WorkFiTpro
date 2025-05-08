<?php
// Check if the database exists
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "workfit";

// Create connection without selecting a database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the database exists
$result = $conn->query("SHOW DATABASES LIKE '$dbname'");
$database_exists = $result->num_rows > 0;

// Close the connection
$conn->close();

if (!$database_exists) {
    // Redirect to setup page if database doesn't exist
    header("Location: setup_database.php");
    exit;
}
?>