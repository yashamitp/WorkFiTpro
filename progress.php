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

// Get progress records
$stmt = $conn->prepare("
    SELECT * FROM progress_records 
    WHERE user_id = ? 
    ORDER BY record_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process form submission for adding new progress record
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_progress'])) {
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $record_date = $_POST['record_date'];
    
    // Calculate BMI
    $height_in_meters = $height / 100; // Convert cm to meters
    $bmi = $weight / ($height_in_meters * $height_in_meters);
    $bmi = round($bmi, 2);
    
    // Insert new record
    $stmt = $conn->prepare("
        INSERT INTO progress_records (user_id, weight, height, bmi, record_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iddds", $user_id, $weight, $height, $bmi, $record_date);
    
    if ($stmt->execute()) {
        $success = "Progress record added successfully!";
        
        // Refresh progress records
        $stmt = $conn->prepare("
            SELECT * FROM progress_records 
            WHERE user_id = ? 
            ORDER BY record_date DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $progress_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Failed to add progress record. Please try again.";
    }
}

// Prepare data for charts
$dates = [];
$weights = [];
$bmis = [];

foreach (array_reverse($progress_records) as $record) {
    $dates[] = date('M d', strtotime($record['record_date']));
    $weights[] = $record['weight'];
    $bmis[] = $record['bmi'];
}

// Convert to JSON for JavaScript
$dates_json = json_encode($dates);
$weights_json = json_encode($weights);
$bmis_json = json_encode($bmis);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Tracking - WorkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Progress Grid */
        .progress-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
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
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        /* Progress Table */
        .table-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .progress-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .progress-table th,
        .progress-table td {
            padding: 12px 15px;
            text-align: left;
        }
        
        .progress-table th {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 500;
        }
        
        .progress-table tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .progress-table tr:last-child {
            border-bottom: none;
        }
        
        .progress-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .bmi-category {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .bmi-underweight {
            background: rgba(251, 191, 36, 0.2);
            color: var(--warning);
        }
        
        .bmi-normal {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
        }
        
        .bmi-overweight {
            background: rgba(248, 113, 113, 0.2);
            color: var(--danger);
        }
        
        .bmi-obese {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        /* Messages */
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: var(--secondary);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Empty State */
        .empty-state {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .empty-icon {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-description {
            color: var(--text-muted);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">Work<span>Fit</span></div>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo substr($user['name'], 0, 1); ?>
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
                <a href="progress.php" class="nav-link active">
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
                <h1 class="page-title">Progress Tracking</h1>
                <p class="page-description">Track your weight, BMI, and fitness progress over time.</p>
            </div>
            
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
            
            <!-- Add Progress Form -->
            <div class="form-card">
                <h2 class="form-title">Add New Progress Record</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" step="0.1" min="30" max="300" id="weight" name="weight" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" step="0.1" min="100" max="250" id="height" name="height" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="record_date">Date</label>
                            <input type="date" id="record_date" name="record_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_progress" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Record
                    </button>
                </form>
            </div>
            
            <?php if (count($progress_records) > 0): ?>
                <!-- Progress Charts -->
                <div class="progress-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h2 class="chart-title">Weight Progress</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="weightChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h2 class="chart-title">BMI Progress</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="bmiChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Progress History Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h2 class="table-title">Progress History</h2>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="progress-table">
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
                                <?php foreach ($progress_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                        <td><?php echo $record['weight']; ?></td>
                                        <td><?php echo $record['height']; ?></td>
                                        <td><?php echo $record['bmi']; ?></td>
                                        <td>
                                            <?php
                                            $bmi = $record['bmi'];
                                            if ($bmi < 18.5) {
                                                echo '<span class="bmi-category bmi-underweight">Underweight</span>';
                                            } elseif ($bmi >= 18.5 && $bmi < 25) {
                                                echo '<span class="bmi-category bmi-normal">Normal</span>';
                                            } elseif ($bmi >= 25 && $bmi < 30) {
                                                echo '<span class="bmi-category bmi-overweight">Overweight</span>';
                                            } else {
                                                echo '<span class="bmi-category bmi-obese">Obese</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2 class="empty-title">No Progress Records Yet</h2>
                    <p class="empty-description">Add your first progress record to start tracking your fitness journey.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (count($progress_records) > 0): ?>
    <script>
        // Weight Chart
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        const weightChart = new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo $dates_json; ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo $weights_json; ?>,
                    backgroundColor: 'rgba(76, 201, 240, 0.2)',
                    borderColor: 'rgba(76, 201, 240, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(76, 201, 240, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                }
            }
        });
        
        // BMI Chart
        const bmiCtx = document.getElementById('bmiChart').getContext('2d');
        const bmiChart = new Chart(bmiCtx, {
            type: 'line',
            data: {
                labels: <?php echo $dates_json; ?>,
                datasets: [{
                    label: 'BMI',
                    data: <?php echo $bmis_json; ?>,
                    backgroundColor: 'rgba(247, 37, 133, 0.2)',
                    borderColor: 'rgba(247, 37, 133, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(247, 37, 133, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)'
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>