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

// Ensure user exists
ensureUserExists($user);

// Initialize variables
$weight = '';
$height = '';
$bmi = '';
$bmi_category = '';
$message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    
    // Validate input
    if ($weight <= 0 || $height <= 0) {
        $message = "Please enter valid weight and height values.";
    } else {
        // Calculate BMI (weight in kg / height in meters squared)
        $height_in_meters = $height / 100; // Convert cm to meters
        $bmi = $weight / ($height_in_meters * $height_in_meters);
        $bmi = round($bmi, 1);
        
        // Determine BMI category
        if ($bmi < 18.5) {
            $bmi_category = "Underweight";
        } elseif ($bmi >= 18.5 && $bmi < 25) {
            $bmi_category = "Normal weight";
        } elseif ($bmi >= 25 && $bmi < 30) {
            $bmi_category = "Overweight";
        } else {
            $bmi_category = "Obesity";
        }
        
        // Save to database
        $stmt = $conn->prepare("
            INSERT INTO progress_records (user_id, weight, height, bmi, record_date) 
            VALUES (?, ?, ?, ?, CURDATE())
        ");
        $stmt->bind_param("iddd", $user_id, $weight, $height, $bmi);
        $stmt->execute();
    }
}

// Get previous BMI records
$stmt = $conn->prepare("
    SELECT * FROM progress_records 
    WHERE user_id = ? 
    ORDER BY record_date DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bmi_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMI Calculator - WorkFit</title>
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
            /* background: var(--primary); */
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
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .page-description {
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        /* BMI Calculator */
        .bmi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .bmi-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .bmi-card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .bmi-card-title i {
            margin-right: 10px;
            color: var(--primary);
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
        
        .btn {
            padding: 12px 20px;
            border-radius: 10px;
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
            width: 100%;
            justify-content: center;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        /* BMI Result */
        .bmi-result {
            text-align: center;
            padding: 20px 0;
        }
        
        .bmi-value {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .bmi-category {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .bmi-category.underweight {
            color: var(--warning);
        }
        
        .bmi-category.normal {
            color: var(--success);
        }
        
        .bmi-category.overweight {
            color: var(--warning);
        }
        
        .bmi-category.obese {
            color: var(--danger);
        }
        
        .bmi-message {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        /* BMI Chart */
        .bmi-chart {
            width: 100%;
            height: 20px;
            background: linear-gradient(to right, #3b82f6, #4ade80, #fbbf24, #f87171);
            border-radius: 10px;
            margin: 30px 0;
            position: relative;
        }
        
        .bmi-marker {
            position: absolute;
            top: -25px;
            width: 2px;
            height: 30px;
            background: var(--text);
            transform: translateX(-50%);
        }
        
        .bmi-marker::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: -4px;
            width: 10px;
            height: 10px;
            background: var(--text);
            border-radius: 50%;
        }
        
        .bmi-scale {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-muted);
        }
        
        /* BMI History */
        .bmi-history {
            margin-top: 40px;
        }
        
        .history-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .history-table th {
            font-weight: 600;
            color: var(--primary);
        }
        
        .history-table tr:last-child td {
            border-bottom: none;
        }
        
        /* BMI Recommendations */
        .recommendations {
            margin-top: 40px;
        }
        
        .recommendations-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .recommendations-list {
            list-style: none;
        }
        
        .recommendation-item {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .recommendation-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(76, 201, 240, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .recommendation-content {
            flex: 1;
        }
        
        .recommendation-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .recommendation-text {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .bmi-grid {
                grid-template-columns: 1fr;
            }
        }
        
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
                color: var(--text);
                font-size: 20px;
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
                <?php echo getUserInitial($user); ?>
            </div>
            <div>
                <div class="user-name"><?php echo getUserName($user); ?></div>
                <div class="user-email"><?php echo getUserEmail($user); ?></div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="workout_plans.php" class="nav-link">
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
                <a href="bmi_calculator.php" class="nav-link active">
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
                <h1 class="page-title">BMI Calculator</h1>
                <p class="page-description">Calculate your Body Mass Index (BMI) to assess your weight relative to your height.</p>
            </div>
            
            <div class="bmi-grid">
                <div class="bmi-card">
                    <h2 class="bmi-card-title">
                        <i class="fas fa-calculator"></i> Calculate Your BMI
                    </h2>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" class="form-control" step="0.1" min="1" value="<?php echo $weight; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" id="height" name="height" class="form-control" step="0.1" min="1" value="<?php echo $height; ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calculator"></i> Calculate BMI
                        </button>
                    </form>
                    
                    <?php if (!empty($bmi)): ?>
                        <div class="bmi-result">
                            <div class="bmi-value"><?php echo $bmi; ?></div>
                            <div class="bmi-category <?php echo strtolower(str_replace(' ', '', $bmi_category)); ?>">
                                <?php echo $bmi_category; ?>
                            </div>
                            
                            <div class="bmi-chart">
                                <?php
                                    // Position the marker based on BMI value (18.5 = 25%, 25 = 50%, 30 = 75%)
                                    $position = 0;
                                    if ($bmi < 18.5) {
                                        $position = ($bmi / 18.5) * 25;
                                    } elseif ($bmi >= 18.5 && $bmi < 25) {
                                        $position = 25 + (($bmi - 18.5) / 6.5) * 25;
                                    } elseif ($bmi >= 25 && $bmi < 30) {
                                        $position = 50 + (($bmi - 25) / 5) * 25;
                                    } else {
                                        $position = 75 + min(25, (($bmi - 30) / 10) * 25);
                                    }
                                ?>
                                <div class="bmi-marker" style="left: <?php echo $position; ?>%;"></div>
                                <div class="bmi-scale">
                                    <span>Underweight</span>
                                    <span>Normal</span>
                                    <span>Overweight</span>
                                    <span>Obese</span>
                                </div>
                            </div>
                            
                            <p class="bmi-message">
                                <?php
                                    if ($bmi < 18.5) {
                                        echo "You are underweight. Consider consulting with a healthcare professional about healthy ways to gain weight.";
                                    } elseif ($bmi >= 18.5 && $bmi < 25) {
                                        echo "You have a healthy weight. Maintain your current lifestyle with regular exercise and balanced nutrition.";
                                    } elseif ($bmi >= 25 && $bmi < 30) {
                                        echo "You are overweight. Consider making lifestyle changes such as increased physical activity and improved diet.";
                                    } else {
                                        echo "You are in the obesity range. It's recommended to consult with a healthcare professional for personalized advice.";
                                    }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="bmi-card">
                    <h2 class="bmi-card-title">
                        <i class="fas fa-info-circle"></i> What is BMI?
                    </h2>
                    
                    <p style="margin-bottom: 20px; line-height: 1.6; color: var(--text-muted);">
                        Body Mass Index (BMI) is a value derived from the mass (weight) and height of a person. The BMI is defined as the body mass divided by the square of the body height, and is expressed in units of kg/m².
                    </p>
                    
                    <h3 style="font-size: 18px; margin-bottom: 15px;">BMI Categories:</h3>
                    
                    <ul style="list-style: none; margin-bottom: 20px;">
                        <li style="display: flex; align-items: center; margin-bottom: 10px;">
                            <span style="width: 12px; height: 12px; background: #3b82f6; border-radius: 50%; margin-right: 10px;"></span>
                            <span>Underweight: BMI less than 18.5</span>
                        </li>
                        <li style="display: flex; align-items: center; margin-bottom: 10px;">
                            <span style="width: 12px; height: 12px; background: #4ade80; border-radius: 50%; margin-right: 10px;"></span>
                            <span>Normal weight: BMI 18.5 to 24.9</span>
                        </li>
                        <li style="display: flex; align-items: center; margin-bottom: 10px;">
                            <span style="width: 12px; height: 12px; background: #fbbf24; border-radius: 50%; margin-right: 10px;"></span>
                            <span>Overweight: BMI 25 to 29.9</span>
                        </li>
                        <li style="display: flex; align-items: center;">
                            <span style="width: 12px; height: 12px; background: #f87171; border-radius: 50%; margin-right: 10px;"></span>
                            <span>Obesity: BMI 30 or higher</span>
                        </li>
                    </ul>
                    
                    <p style="line-height: 1.6; color: var(--text-muted);">
                        While BMI is a useful tool for assessing weight status, it's important to remember that it doesn't directly measure body fat or account for factors like muscle mass, bone density, and overall body composition.
                    </p>
                </div>
            </div>
            
            <!-- BMI History -->
            <?php if (!empty($bmi_records)): ?>
                <div class="bmi-history">
                    <h2 class="history-title">Your BMI History</h2>
                    
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>Height (cm)</th>
                                <th>BMI</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bmi_records as $record): ?>
                                <?php
                                    // Determine BMI category
                                    $record_bmi = $record['bmi'];
                                    if ($record_bmi < 18.5) {
                                        $record_category = "Underweight";
                                        $category_class = "underweight";
                                    } elseif ($record_bmi >= 18.5 && $record_bmi < 25) {
                                        $record_category = "Normal weight";
                                        $category_class = "normal";
                                    } elseif ($record_bmi >= 25 && $record_bmi < 30) {
                                        $record_category = "Overweight";
                                        $category_class = "overweight";
                                    } else {
                                        $record_category = "Obesity";
                                        $category_class = "obese";
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                    <td><?php echo $record['weight']; ?></td>
                                    <td><?php echo $record['height']; ?></td>
                                    <td><?php echo $record['bmi']; ?></td>
                                    <td class="bmi-category <?php echo $category_class; ?>" style="font-size: 16px;">
                                        <?php echo $record_category; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Recommendations -->
            <?php if (!empty($bmi_category)): ?>
                <div class="recommendations">
                    <h2 class="recommendations-title">Recommendations for <?php echo $bmi_category; ?></h2>
                    
                    <ul class="recommendations-list">
                        <?php if ($bmi_category == "Underweight"): ?>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Increase Caloric Intake</h3>
                                    <p class="recommendation-text">
                                        Aim to consume more calories than you burn. Focus on nutrient-dense foods like nuts, seeds, avocados, and whole grains.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-drumstick-bite"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Prioritize Protein</h3>
                                    <p class="recommendation-text">
                                        Include protein-rich foods in every meal to support muscle growth. Good sources include lean meats, eggs, dairy, legumes, and protein supplements.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Strength Training</h3>
                                    <p class="recommendation-text">
                                        Focus on resistance exercises to build muscle mass. Compound movements like squats, deadlifts, and bench presses are particularly effective.
                                    </p>
                                </div>
                            </li>
                        <?php elseif ($bmi_category == "Normal weight"): ?>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Maintain Balance</h3>
                                    <p class="recommendation-text">
                                        Continue with your current balanced diet and regular exercise routine to maintain your healthy weight.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Focus on Fitness</h3>
                                    <p class="recommendation-text">
                                        Shift your focus from weight management to improving overall fitness, flexibility, and strength.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-carrot"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Nutrient Quality</h3>
                                    <p class="recommendation-text">
                                        Emphasize the quality of nutrients rather than quantity. Include a variety of fruits, vegetables, whole grains, and lean proteins.
                                    </p>
                                </div>
                            </li>
                        <?php elseif ($bmi_category == "Overweight"): ?>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-walking"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Increase Physical Activity</h3>
                                    <p class="recommendation-text">
                                        Aim for at least 150 minutes of moderate-intensity aerobic activity or 75 minutes of vigorous activity per week, plus muscle-strengthening activities.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-apple-alt"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Dietary Adjustments</h3>
                                    <p class="recommendation-text">
                                        Create a moderate calorie deficit by reducing portion sizes and limiting processed foods, sugars, and unhealthy fats.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-water"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Stay Hydrated</h3>
                                    <p class="recommendation-text">
                                        Drink plenty of water throughout the day. Sometimes thirst can be mistaken for hunger.
                                    </p>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Consult a Healthcare Professional</h3>
                                    <p class="recommendation-text">
                                        It's recommended to work with a healthcare provider to develop a personalized weight management plan.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-running"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Start Slowly</h3>
                                    <p class="recommendation-text">
                                        Begin with low-impact exercises like walking, swimming, or cycling. Gradually increase intensity and duration as your fitness improves.
                                    </p>
                                </div>
                            </li>
                            <li class="recommendation-item">
                                <div class="recommendation-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title">Track Your Progress</h3>
                                    <p class="recommendation-text">
                                        Keep a food and exercise journal to monitor your habits and identify areas for improvement.
                                    </p>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>