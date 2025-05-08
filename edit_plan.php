<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Check if plan ID is provided
if (!isset($_GET['id'])) {
    header("Location: workout_plans.php");
    exit;
}

$plan_id = $_GET['id'];

// Get plan details
$stmt = $conn->prepare("SELECT * FROM workout_plans WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $plan_id, $user_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();

// If plan doesn't exist or doesn't belong to user, redirect
if (!$plan) {
    header("Location: workout_plans.php");
    exit;
}

// Process form submission for updating plan name
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_plan'])) {
    $plan_name = trim($_POST['plan_name']);
    
    if (!empty($plan_name)) {
        $stmt = $conn->prepare("UPDATE workout_plans SET plan_name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $plan_name, $plan_id, $user_id);
        
        if ($stmt->execute()) {
            // Update the plan variable with the new name
            $plan['plan_name'] = $plan_name;
            $success_message = "Plan name updated successfully!";
        } else {
            $error_message = "Failed to update plan name. Please try again.";
        }
    } else {
        $error_message = "Plan name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plan - WorkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        :root {
            --primary: #4cc9f0;
            --secondary: #f72585;
            --dark-bg: #1a1a2e;
            --darker-bg: #16213e;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text: #fff;
            --text-muted: #ccc;
            --success: #4ade80;
            --warning: #fbbf24;
            --danger: #f87171;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-bg), var(--darker-bg));
            color: var(--text);
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            padding: 30px 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-radius: 10px;
            background: var(--card-bg);
            margin: 0 20px 30px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .user-name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--card-bg);
            color: var(--primary);
        }
        
        .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--danger);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-btn i {
            margin-right: 10px;
        }
        
        .logout-btn:hover {
            background: rgba(248, 113, 113, 0.2);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px 0;
            min-height: 100vh;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), #4361ee);
            color: var(--text);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(76, 201, 240, 0.1);
        }
        
        /* Edit Plan Form */
        .edit-form {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
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
            color: var(--text);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Messages */
        .success-message {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: rgba(248, 113, 113, 0.2);
            color: var(--danger);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: var(--card-bg);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .mobile-toggle:hover {
                background: rgba(255, 255, 255, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <div class="mobile-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">Work<span>Fit</span></div>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <div class="user-name"><?php echo $user['name']; ?></div>
                <div class="user-email"><?php echo $user['email']; ?></div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="workout_plans.php" class="nav-link active">
                    <i class="fas fa-dumbbell"></i> Workout Plans
                </a>
            </li>
            <li class="nav-item">
                <a href="diet_tracker.php" class="nav-link">
                    <i class="fas fa-utensils"></i> Diet Tracker
                </a>
            </li>
            <li class="nav-item">
                <a href="progress.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Progress
                </a>
            </li>
            <li class="nav-item">
                <a href="bmi_calculator.php" class="nav-link">
                    <i class="fas fa-calculator"></i> BMI Calculator
                </a>
            </li>
            <li class="nav-item">
                <a href="exercise_guide.php" class="nav-link">
                    <i class="fas fa-book"></i> Exercise Guide
                </a>
            </li>
            <li class="nav-item">
                <a href="nutrition_finder.php" class="nav-link">
                    <i class="fas fa-apple-alt"></i> Nutrition Finder
                </a>
            </li>
        </ul>
        
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Edit Workout Plan</h1>
                <div>
                    <a href="view_plan.php?id=<?php echo $plan_id; ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Plan
                    </a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="edit-form">
                <h2>Plan Details</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="plan_name">Plan Name</label>
                        <input type="text" id="plan_name" name="plan_name" class="form-control" value="<?php echo $plan['plan_name']; ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <a href="view_plan.php?id=<?php echo $plan_id; ?>" class="btn btn-outline">Cancel</a>
                        <button type="submit" name="update_plan" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
            
            <div class="edit-form">
                <h2>Manage Exercises</h2>
                <p>To add, edit, or remove exercises, go back to the plan view.</p>
                <div class="form-actions">
                    <a href="view_plan.php?id=<?php echo $plan_id; ?>" class="btn btn-primary">
                        <i class="fas fa-dumbbell"></i> Manage Exercises
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>