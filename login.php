<?php
// Check if the database exists first
include_once 'check_database.php';

require_once 'config.php';

$error = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "No account found with that email";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WorkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            color: #4cc9f0;
        }
        
        .logo h1 span {
            color: #f72585;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(45deg, #4cc9f0, #4361ee);
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(45deg, #4361ee, #4cc9f0);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(76, 201, 240, 0.3);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .divider span {
            padding: 0 15px;
            color: #ccc;
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .social-btn i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .google-btn {
            background: rgba(234, 67, 53, 0.2);
        }
        
        .google-btn:hover {
            background: rgba(234, 67, 53, 0.3);
        }
        
        .register-link {
            text-align: center;
            margin-top: 30px;
            color: #ccc;
        }
        
        .register-link a {
            color: #4cc9f0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #f72585;
        }
        
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: #f72585;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-home i {
            margin-right: 5px;
        }
        
        .back-home:hover {
            color: #4cc9f0;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="container">
        <div class="logo">
            <h1>Work<span>Fit</span></h1>
        </div>
        
        <h2>Welcome Back</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="social-login">
            <button type="button" id="googleLogin" class="social-btn google-btn">
                <i class="fab fa-google"></i> Login with Google
            </button>
        </div>
        
        <div class="register-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-auth-compat.js"></script>
    
    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyB0N2jbhpFpjDaxZKkNz46uIz93VTbjPOA",
            authDomain: "workfit-yg09.firebaseapp.com",
            projectId: "workfit-yg09",
            storageBucket: "workfit-yg09.firebasestorage.app",
            messagingSenderId: "502000859649",
            appId: "1:502000859649:web:83266a5c608d23af131e00",
            measurementId: "G-EKFF1SK32B"
        };
        
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        
        // Google Sign-in
        document.getElementById('googleLogin').addEventListener('click', function() {
            const provider = new firebase.auth.GoogleAuthProvider();
            
            firebase.auth().signInWithPopup(provider)
                .then((result) => {
                    // Get user info
                    const user = result.user;
                    
                    // Send user data to PHP backend
                    const userData = {
                        name: user.displayName,
                        email: user.email,
                        firebase_uid: user.uid
                    };
                    
                    // Send to backend
                    fetch('firebase_auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(userData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'dashboard.php';
                        } else {
                            alert('Authentication failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Authentication failed: ' + error.message);
                });
        });
    </script>
</body>
</html>