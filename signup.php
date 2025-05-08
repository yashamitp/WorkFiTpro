<?php
// Check if the database exists first
include_once 'check_database.php';

require_once 'config.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Process signup form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        if (userExists($email)) {
            $error = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - WorkFit</title>
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
            padding: 40px 0;
        }
        
        .container {
            width: 100%;
            max-width: 450px;
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
        
        .login-link {
            text-align: center;
            margin-top: 30px;
            color: #ccc;
        }
        
        .login-link a {
            color: #4cc9f0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
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
        
        .success-message {
            background: rgba(76, 201, 240, 0.2);
            color: #4cc9f0;
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
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 5px;
            background: #ccc;
            position: relative;
            overflow: hidden;
        }
        
        .password-strength span {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .weak {
            background: #f72585;
            width: 33.33% !important;
        }
        
        .medium {
            background: #f9c74f;
            width: 66.66% !important;
        }
        
        .strong {
            background: #4cc9f0;
            width: 100% !important;
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
        
        <h2>Create Your Account</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="password-strength">
                    <span id="password-strength-meter"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Sign Up</button>
        </form>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="social-login">
            <button type="button" id="googleSignup" class="social-btn google-btn">
                <i class="fab fa-google"></i> Sign Up with Google
            </button>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
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
        
        // Google Sign-up
        document.getElementById('googleSignup').addEventListener('click', function() {
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
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('password-strength-meter');
            
            // Remove all classes
            strengthMeter.classList.remove('weak', 'medium', 'strong');
            
            if (password.length === 0) {
                strengthMeter.style.width = '0';
                return;
            }
            
            // Calculate password strength
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            
            // Complexity check
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Set the appropriate class
            if (strength <= 2) {
                strengthMeter.classList.add('weak');
            } else if (strength <= 3) {
                strengthMeter.classList.add('medium');
            } else {
                strengthMeter.classList.add('strong');
            }
        });
    </script>
</body>
</html>