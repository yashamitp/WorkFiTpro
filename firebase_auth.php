<?php
require_once 'config.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json = file_get_contents('php://input');
    
    // Decode the JSON data
    $data = json_decode($json, true);
    
    // Check if required fields are present
    if (isset($data['email']) && isset($data['name']) && isset($data['firebase_uid'])) {
        $email = $data['email'];
        $name = $data['name'];
        $firebase_uid = $data['firebase_uid'];
        
        // Check if user already exists
        if (userExists($email)) {
            // User exists, update Firebase UID if needed
            $stmt = $conn->prepare("UPDATE users SET firebase_uid = ? WHERE email = ?");
            $stmt->bind_param("ss", $firebase_uid, $email);
            $stmt->execute();
            
            // Get user data
            $user = getUserByEmail($email);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Return success response
            echo json_encode(['success' => true]);
            exit;
        } else {
            // User doesn't exist, create new user
            // Generate a random password (user will login via Google, so this is just for database)
            $random_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, firebase_uid) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $firebase_uid);
            
            if ($stmt->execute()) {
                // Get the new user's ID
                $user_id = $conn->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Return success response
                echo json_encode(['success' => true]);
                exit;
            } else {
                // Return error response
                echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                exit;
            }
        }
    } else {
        // Return error response for missing fields
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
} else {
    // Return error response for invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
?>