<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "workfit"; // Changed from workfit_db to workfit

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    firebase_uid VARCHAR(128) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating users table: " . $conn->error);
}

// Create workout_plans table if not exists
$sql = "CREATE TABLE IF NOT EXISTS workout_plans (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating workout_plans table: " . $conn->error);
}

// Create exercises table if not exists
$sql = "CREATE TABLE IF NOT EXISTS exercises (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    plan_id INT(11) NOT NULL,
    exercise_name VARCHAR(100) NOT NULL,
    duration INT(11) NULL,
    sets_reps VARCHAR(50) NULL,
    day_of_week VARCHAR(20) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating exercises table: " . $conn->error);
}

// Create diet_entries table if not exists
$sql = "CREATE TABLE IF NOT EXISTS diet_entries (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    quantity FLOAT DEFAULT 1,
    quantity_unit VARCHAR(20) DEFAULT 'serving',
    calories INT(11) NOT NULL,
    protein FLOAT NOT NULL,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating diet_entries table: " . $conn->error);
}

// Create goals table if not exists
$sql = "CREATE TABLE IF NOT EXISTS goals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    goal_description TEXT NOT NULL,
    target_date DATE NULL,
    is_achieved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating goals table: " . $conn->error);
}

// Create progress_records table if not exists
$sql = "CREATE TABLE IF NOT EXISTS progress_records (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    weight FLOAT NULL,
    height FLOAT NULL,
    bmi FLOAT NULL,
    record_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating progress_records table: " . $conn->error);
}

// Create chat_logs table if not exists
$sql = "CREATE TABLE IF NOT EXISTS chat_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    is_bot BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating chat_logs table: " . $conn->error);
}

// Function to check if user exists
function userExists($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to get user by email
function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get user by ID
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to safely get user avatar initial
function getUserInitial($user) {
    if (isset($user['name']) && !empty($user['name'])) {
        return strtoupper(substr($user['name'], 0, 1));
    }
    return 'U'; // Default initial if name is not available
}

// Function to safely get user name
function getUserName($user) {
    if (isset($user['name']) && !empty($user['name'])) {
        return $user['name'];
    }
    return 'User'; // Default name if not available
}

// Function to safely get user email
function getUserEmail($user) {
    if (isset($user['email']) && !empty($user['email'])) {
        return $user['email'];
    }
    return 'No email'; // Default email if not available
}

// Function to check if user exists and redirect if not
function ensureUserExists($user) {
    if (!$user) {
        // Clear the session as it might be invalid
        session_unset();
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>