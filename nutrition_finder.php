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

/**
 * Generate a descriptive text about a food item based on its nutritional data
 * 
 * @param array $food The food data from the API
 * @param bool $is_indian_food Whether the food is identified as Indian
 * @return string The generated description
 */
function generateFoodDescription($food, $is_indian_food) {
    // Extract nutritional information
    $calories = 0;
    $protein = 0;
    $fat = 0;
    $carbs = 0;
    $fiber = 0;
    $sugar = 0;
    $sodium = 0;
    $calcium = 0;
    $iron = 0;
    $vitaminC = 0;
    
    foreach ($food['foodNutrients'] as $nutrient) {
        if ($nutrient['nutrientName'] === 'Energy' && $nutrient['unitName'] === 'KCAL') {
            $calories = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Protein') {
            $protein = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Total lipid (fat)') {
            $fat = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Carbohydrate, by difference') {
            $carbs = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Fiber, total dietary') {
            $fiber = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Sugars, total including NLEA') {
            $sugar = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Sodium, Na') {
            $sodium = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Calcium, Ca') {
            $calcium = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Iron, Fe') {
            $iron = $nutrient['value'];
        } elseif ($nutrient['nutrientName'] === 'Vitamin C, total ascorbic acid') {
            $vitaminC = $nutrient['value'];
        }
    }
    
    // Get food name and category
    $foodName = $food['description'];
    $foodCategory = isset($food['foodCategory']) ? $food['foodCategory'] : '';
    $dataType = isset($food['dataType']) ? $food['dataType'] : '';
    
    // Start building the description
    $description = "";
    
    // Add information about the food type
    if ($is_indian_food) {
        $description .= "<strong>{$foodName}</strong> is a popular food item in Indian cuisine. ";
    } else {
        $description .= "<strong>{$foodName}</strong> is a nutritious food option. ";
    }
    
    // Add calorie information
    if ($calories > 0) {
        if ($calories < 100) {
            $description .= "It is a low-calorie food with approximately {$calories} calories per serving. ";
        } elseif ($calories >= 100 && $calories < 300) {
            $description .= "It contains a moderate {$calories} calories per serving. ";
        } else {
            $description .= "It is a calorie-dense food with {$calories} calories per serving. ";
        }
    }
    
    // Add macronutrient information
    $macroInfo = [];
    if ($protein > 0) {
        if ($protein > 15) {
            $macroInfo[] = "high in protein ({$protein}g)";
        } elseif ($protein > 5) {
            $macroInfo[] = "a good source of protein ({$protein}g)";
        } else {
            $macroInfo[] = "contains some protein ({$protein}g)";
        }
    }
    
    if ($carbs > 0) {
        if ($carbs > 30) {
            $macroInfo[] = "rich in carbohydrates ({$carbs}g)";
        } elseif ($carbs > 10) {
            $macroInfo[] = "a moderate source of carbohydrates ({$carbs}g)";
        } else {
            $macroInfo[] = "low in carbohydrates ({$carbs}g)";
        }
    }
    
    if ($fat > 0) {
        if ($fat > 15) {
            $macroInfo[] = "high in fat ({$fat}g)";
        } elseif ($fat > 5) {
            $macroInfo[] = "contains moderate fat ({$fat}g)";
        } else {
            $macroInfo[] = "low in fat ({$fat}g)";
        }
    }
    
    if (!empty($macroInfo)) {
        $description .= "This food is " . implode(", ", $macroInfo) . ". ";
    }
    
    // Add information about fiber and sugar if available
    if ($fiber > 0 || $sugar > 0) {
        $description .= "It ";
        
        if ($fiber > 0) {
            if ($fiber > 5) {
                $description .= "is high in dietary fiber ({$fiber}g)";
            } elseif ($fiber > 2) {
                $description .= "contains a good amount of dietary fiber ({$fiber}g)";
            } else {
                $description .= "has some dietary fiber ({$fiber}g)";
            }
            
            if ($sugar > 0) {
                $description .= " and ";
            }
        }
        
        if ($sugar > 0) {
            if ($sugar > 15) {
                $description .= "contains high sugar content ({$sugar}g)";
            } elseif ($sugar > 5) {
                $description .= "has moderate sugar content ({$sugar}g)";
            } else {
                $description .= "is low in sugar ({$sugar}g)";
            }
        }
        
        $description .= ". ";
    }
    
    // Add information about micronutrients if available
    $microInfo = [];
    if ($sodium > 0) {
        if ($sodium > 500) {
            $microInfo[] = "high in sodium";
        } elseif ($sodium > 200) {
            $microInfo[] = "moderate in sodium";
        } else {
            $microInfo[] = "low in sodium";
        }
    }
    
    if ($calcium > 0 && $calcium > 100) {
        $microInfo[] = "a good source of calcium";
    }
    
    if ($iron > 0 && $iron > 1.5) {
        $microInfo[] = "a good source of iron";
    }
    
    if ($vitaminC > 0 && $vitaminC > 15) {
        $microInfo[] = "rich in vitamin C";
    }
    
    if (!empty($microInfo)) {
        $description .= "This food is also " . implode(", ", $microInfo) . ". ";
    }
    
    // Add dietary considerations
    if ($protein > 15 && $carbs < 10 && $fat < 10) {
        $description .= "It's an excellent choice for high-protein, low-carb diets. ";
    } elseif ($carbs > 30 && $fat < 5) {
        $description .= "It's suitable for carb-loading or high-energy diets. ";
    } elseif ($fat > 15 && $carbs < 10) {
        $description .= "It fits well in ketogenic or low-carb diets. ";
    }
    
    if ($calories < 100 && $protein > 5) {
        $description .= "This is a great option for weight management while maintaining protein intake. ";
    }
    
    // Add data source information
    if (!empty($dataType)) {
        $description .= "<small>Data source: {$dataType}</small>";
    }
    
    return $description;
}

// Ensure user exists
ensureUserExists($user);

// Initialize variables
$food_data = null;
$error = '';

// List of common Indian food items to prioritize
$indian_foods = [
    'roti', 'chapati', 'naan', 'paratha', 'dosa', 'idli', 'sambar', 'dal', 'rajma', 'chana',
    'paneer', 'curry', 'biryani', 'pulao', 'rice', 'basmati', 'samosa', 'pakora', 'chaat',
    'chole', 'bhature', 'puri', 'upma', 'poha', 'khichdi', 'sabzi', 'aloo', 'gobi', 'palak',
    'bhindi', 'baingan', 'matar', 'methi', 'saag', 'raita', 'lassi', 'chai', 'masala',
    'tandoori', 'tikka', 'korma', 'vindaloo', 'butter chicken', 'malai kofta', 'gulab jamun',
    'jalebi', 'ladoo', 'barfi', 'halwa', 'kheer', 'kulfi', 'rasmalai', 'chutney', 'pickle',
    'papad', 'thali', 'dahi', 'ghee', 'rogan josh', 'pav bhaji', 'vada pav', 'uttapam',
    'rasam', 'payasam', 'thoran', 'avial', 'appam', 'puttu', 'parotta', 'bisi bele bath',
    'pongal', 'pesarattu', 'gongura', 'dhokla', 'khandvi', 'thepla', 'undhiyu', 'fafda',
    'jalebi', 'kachori', 'khakhra', 'modak', 'puran poli', 'shrikhand', 'misal pav'
];

// Process search form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_food'])) {
    $food_query = trim($_POST['food_query']);
    
    if (empty($food_query)) {
        $error = "Please enter a food name to search";
    } else {
        // FoodData Central API key
        $api_key = "ZVMc9YCge99gXhcm7snWD5wRgoYjCMDbiJfBlf8e";
        
        // API endpoint - increase pageSize to get more results for better sorting
        $url = "https://api.nal.usda.gov/fdc/v1/foods/search?query=" . urlencode($food_query) . "&dataType=Foundation,SR%20Legacy,Survey%20(FNDDS)&pageSize=25&api_key=" . $api_key;
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = "API request failed: " . curl_error($ch);
        } else {
            // Close cURL session
            curl_close($ch);
            
            // Decode JSON response
            $food_data = json_decode($response, true);
            
            // Check if foods were found
            if (!isset($food_data['foods']) || count($food_data['foods']) === 0) {
                $error = "No food items found for your query. Please try a different search term.";
                $food_data = null;
            } else {
                // Sort foods to prioritize Indian food items
                usort($food_data['foods'], function($a, $b) use ($indian_foods, $food_query) {
                    $a_desc = strtolower($a['description']);
                    $b_desc = strtolower($b['description']);
                    
                    // Check if food names contain any Indian food keywords
                    $a_indian_score = 0;
                    $b_indian_score = 0;
                    
                    foreach ($indian_foods as $indian_food) {
                        // Exact match gets highest priority
                        if ($a_desc === $indian_food) {
                            $a_indian_score += 10;
                        }
                        if ($b_desc === $indian_food) {
                            $b_indian_score += 10;
                        }
                        
                        // Contains the Indian food term
                        if (strpos($a_desc, $indian_food) !== false) {
                            $a_indian_score += 5;
                        }
                        if (strpos($b_desc, $indian_food) !== false) {
                            $b_indian_score += 5;
                        }
                    }
                    
                    // If both have Indian food scores, compare them
                    if ($a_indian_score > 0 && $b_indian_score > 0) {
                        return $b_indian_score - $a_indian_score;
                    }
                    
                    // If only one has an Indian food score, prioritize it
                    if ($a_indian_score > 0) {
                        return -1;
                    }
                    if ($b_indian_score > 0) {
                        return 1;
                    }
                    
                    // If neither has an Indian food score, prioritize exact matches to the query
                    $a_exact = strtolower($a_desc) === strtolower($food_query);
                    $b_exact = strtolower($b_desc) === strtolower($food_query);
                    
                    if ($a_exact && !$b_exact) {
                        return -1;
                    }
                    if (!$a_exact && $b_exact) {
                        return 1;
                    }
                    
                    // Default to the original order
                    return 0;
                });
                
                // Limit to top 10 results after sorting
                $food_data['foods'] = array_slice($food_data['foods'], 0, 10);
            }
        }
    }
}

// Process save to diet tracker form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_to_diet'])) {
    // Debug information
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>Debug Information</h3>";
    echo "<p>POST data:</p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Check if the diet_entries table has the required columns
    $result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity'");
    $quantity_exists = $result->num_rows > 0;
    $result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity_unit'");
    $quantity_unit_exists = $result->num_rows > 0;
    
    echo "<p>Database columns check:</p>";
    echo "Quantity column exists: " . ($quantity_exists ? "Yes" : "No") . "<br>";
    echo "Quantity_unit column exists: " . ($quantity_unit_exists ? "Yes" : "No") . "<br>";
    echo "</div>";
    
    $food_name = trim($_POST['food_name']);
    $quantity = floatval($_POST['quantity']);
    $quantity_unit = trim($_POST['quantity_unit']);
    $calories = floatval($_POST['calories']);
    $protein = floatval($_POST['protein']);
    $entry_date = date('Y-m-d'); // Current date
    
    if (empty($food_name)) {
        $error = "Food name is required";
    } elseif ($quantity <= 0) {
        $error = "Quantity must be greater than zero";
    } else {
        // Format the food name to include quantity if it's not 1 serving
        $display_food_name = $food_name;
        if ($quantity != 1 || $quantity_unit != 'serving') {
            $display_food_name = $food_name . " (" . $quantity . " " . $quantity_unit . ")";
        }
        
        // Use different SQL based on whether the columns exist
        if ($quantity_exists && $quantity_unit_exists) {
            // Insert with quantity columns
            $sql = "INSERT INTO diet_entries (user_id, food_name, quantity, quantity_unit, calories, protein, entry_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdsids", $user_id, $display_food_name, $quantity, $quantity_unit, $calories, $protein, $entry_date);
        } else {
            // Insert without quantity columns
            $sql = "INSERT INTO diet_entries (user_id, food_name, calories, protein, entry_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdds", $user_id, $display_food_name, $calories, $protein, $entry_date);
        }
        
        echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
        echo "<p>SQL Query: " . $sql . "</p>";
        echo "</div>";
        
        if ($stmt->execute()) {
            // Redirect to diet tracker page
            header("Location: diet_tracker.php?success=1");
            exit;
        } else {
            $error = "Failed to save food entry: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition Finder - WorkFit</title>
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
        
        /* Search Form */
        .search-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            color: var(--text);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
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
        
        .btn-success {
            background: var(--success);
            color: var(--text);
        }
        
        .btn-success:hover {
            background: #3bca70;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 222, 128, 0.3);
        }
        
        /* Results */
        .results-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .results-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .results-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .search-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 3px solid #ff8800;
        }
        
        .indian-food-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .food-list {
            list-style: none;
        }
        
        .food-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .food-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Indian Food Styles */
        .indian-food-item {
            background: rgba(255, 136, 0, 0.1);
            border-left: 3px solid #ff8800;
        }
        
        .indian-food-item:hover {
            background: rgba(255, 136, 0, 0.15);
            box-shadow: 0 10px 20px rgba(255, 136, 0, 0.2);
        }
        
        .indian-food-badge {
            display: inline-block;
            background: linear-gradient(45deg, #ff8800, #ff5500);
            color: white;
            font-size: 11px;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 136, 0, 0.4);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(255, 136, 0, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 136, 0, 0);
            }
        }
        
        .food-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .food-name {
            font-size: 18px;
            font-weight: 600;
        }
        
        .food-brand {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .btn-toggle-description {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 13px;
            padding: 5px 10px;
            margin-top: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .btn-toggle-description:hover {
            background: rgba(76, 201, 240, 0.1);
        }
        
        .btn-toggle-description i {
            font-size: 14px;
        }
        
        .food-description {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 10px;
            line-height: 1.5;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border-left: 2px solid var(--primary);
            animation: fadeIn 0.3s ease-out;
        }
        
        .food-actions {
            display: flex;
            gap: 10px;
        }
        
        .food-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .food-detail {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }
        
        .detail-value {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .food-nutrients {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 15px;
        }
        
        .nutrients-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .nutrients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .nutrient {
            display: flex;
            flex-direction: column;
        }
        
        .nutrient-name {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .nutrient-value {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        /* Error Message */
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: var(--secondary);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        .modal-content {
            background: var(--darker-bg);
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out forwards;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .nutrition-note {
            background: rgba(76, 201, 240, 0.1);
            border-left: 3px solid var(--primary);
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-muted);
            border-radius: 5px;
        }
        
        .nutrition-note i {
            color: var(--primary);
            margin-right: 5px;
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
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            
            .search-form {
                flex-direction: column;
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
                <a href="nutrition_finder.php" class="nav-link active">
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
                <h1 class="page-title">Nutrition Finder</h1>
                <p class="page-description">Search for foods to discover their nutritional information and add them to your diet tracker.</p>
            </div>
            
            <div class="search-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="search-form">
                    <div class="search-main">
                        <input type="text" name="food_query" class="search-input" placeholder="Enter a food name (e.g., apple, chicken breast, rice)" required>
                        <button type="submit" name="search_food" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="search-options">
                        <label class="filter-option">
                            <input type="checkbox" name="indian_food_only" <?php echo isset($_POST['indian_food_only']) ? 'checked' : ''; ?>>
                            <span>Show only Indian food items</span>
                        </label>
                    </div>
                </form>
            </div>
            
            <?php if ($food_data && isset($food_data['foods']) && count($food_data['foods']) > 0): ?>
                <div class="results-card">
                    <h2 class="results-title">
                        <i class="fas fa-list"></i> Search Results
                    </h2>
                    
                    <div class="search-info">
                        <div class="indian-food-info">
                            <span class="indian-food-badge">Popular in India</span>
                            Items popular in Indian cuisine are prioritized and highlighted for your convenience.
                        </div>
                    </div>
                    
                    <ul class="food-list">
                        <?php foreach ($food_data['foods'] as $food): 
                            // Check if this is an Indian food item
                            $is_indian_food = false;
                            $food_desc_lower = strtolower($food['description']);
                            foreach ($indian_foods as $indian_food) {
                                if (strpos($food_desc_lower, $indian_food) !== false) {
                                    $is_indian_food = true;
                                    break;
                                }
                            }
                        ?>
                            <li class="food-item <?php echo $is_indian_food ? 'indian-food-item' : ''; ?>">
                                <div class="food-header">
                                    <div>
                                        <h3 class="food-name">
                                            <?php echo $food['description']; ?>
                                            <?php if ($is_indian_food): ?>
                                                <span class="indian-food-badge">Popular in India</span>
                                            <?php endif; ?>
                                        </h3>
                                        <?php if (isset($food['brandName']) && !empty($food['brandName'])): ?>
                                            <div class="food-brand"><?php echo $food['brandName']; ?></div>
                                        <?php endif; ?>
                                        
                                        <!-- Food Description Toggle Button -->
                                        <button class="btn-toggle-description" type="button">
                                            <i class="fas fa-info-circle"></i> Show Nutritional Information
                                        </button>
                                        
                                        <!-- Food Description Section (Hidden by default) -->
                                        <div class="food-description" style="display: none;">
                                            <?php
                                            // Generate a description based on available data
                                            $foodDescription = generateFoodDescription($food, $is_indian_food);
                                            echo $foodDescription;
                                            ?>
                                        </div>
                                    </div>
                                    <div class="food-actions">
                                        <button type="button" class="btn btn-success btn-add-food" 
                                                data-food-name="<?php echo htmlspecialchars($food['description']); ?>"
                                                data-calories="<?php 
                                                    $calories = 0;
                                                    foreach ($food['foodNutrients'] as $nutrient) {
                                                        if ($nutrient['nutrientName'] === 'Energy' && $nutrient['unitName'] === 'KCAL') {
                                                            $calories = $nutrient['value'];
                                                            break;
                                                        }
                                                    }
                                                    echo $calories;
                                                ?>"
                                                data-protein="<?php 
                                                    $protein = 0;
                                                    foreach ($food['foodNutrients'] as $nutrient) {
                                                        if ($nutrient['nutrientName'] === 'Protein') {
                                                            $protein = $nutrient['value'];
                                                            break;
                                                        }
                                                    }
                                                    echo $protein;
                                                ?>">
                                            <i class="fas fa-plus"></i> Add to Diet
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="food-details">
                                    <?php
                                        $calories = 0;
                                        $protein = 0;
                                        $fat = 0;
                                        $carbs = 0;
                                        
                                        foreach ($food['foodNutrients'] as $nutrient) {
                                            if ($nutrient['nutrientName'] === 'Energy' && $nutrient['unitName'] === 'KCAL') {
                                                $calories = $nutrient['value'];
                                            } elseif ($nutrient['nutrientName'] === 'Protein') {
                                                $protein = $nutrient['value'];
                                            } elseif ($nutrient['nutrientName'] === 'Total lipid (fat)') {
                                                $fat = $nutrient['value'];
                                            } elseif ($nutrient['nutrientName'] === 'Carbohydrate, by difference') {
                                                $carbs = $nutrient['value'];
                                            }
                                        }
                                    ?>
                                    
                                    <div class="food-detail">
                                        <div class="detail-value"><?php echo $calories; ?></div>
                                        <div class="detail-label">Calories (kcal)</div>
                                    </div>
                                    
                                    <div class="food-detail">
                                        <div class="detail-value"><?php echo $protein; ?>g</div>
                                        <div class="detail-label">Protein</div>
                                    </div>
                                    
                                    <div class="food-detail">
                                        <div class="detail-value"><?php echo $fat; ?>g</div>
                                        <div class="detail-label">Fat</div>
                                    </div>
                                    
                                    <div class="food-detail">
                                        <div class="detail-value"><?php echo $carbs; ?>g</div>
                                        <div class="detail-label">Carbs</div>
                                    </div>
                                </div>
                                
                                <div class="food-nutrients">
                                    <h4 class="nutrients-title">Additional Nutrients</h4>
                                    <div class="nutrients-grid">
                                        <?php
                                            $displayed_nutrients = ['Energy', 'Protein', 'Total lipid (fat)', 'Carbohydrate, by difference'];
                                            $count = 0;
                                            
                                            foreach ($food['foodNutrients'] as $nutrient) {
                                                if (!in_array($nutrient['nutrientName'], $displayed_nutrients) && $count < 12) {
                                                    echo '<div class="nutrient">';
                                                    echo '<div class="nutrient-name">' . $nutrient['nutrientName'] . '</div>';
                                                    echo '<div class="nutrient-value">' . $nutrient['value'] . ' ' . strtolower($nutrient['unitName']) . '</div>';
                                                    echo '</div>';
                                                    $count++;
                                                }
                                            }
                                        ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add to Diet Modal -->
    <div class="modal" id="add-food-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add to Diet Tracker</h2>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" id="food_name" name="food_name">
                <input type="hidden" id="base_calories" name="base_calories">
                <input type="hidden" id="base_protein" name="base_protein">
                
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" step="0.1" min="0.1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_unit">Unit</label>
                        <select id="quantity_unit" name="quantity_unit" class="form-control">
                            <option value="serving">Serving</option>
                            <option value="g">Grams (g)</option>
                            <option value="ml">Milliliters (ml)</option>
                            <option value="cup">Cup</option>
                            <option value="tbsp">Tablespoon</option>
                            <option value="tsp">Teaspoon</option>
                            <option value="piece">Piece</option>
                            <option value="plate">Plate</option>
                            <option value="bowl">Bowl</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="calories">Calories (kcal)</label>
                    <input type="number" id="calories" name="calories" class="form-control" step="0.1" min="0" required readonly>
                </div>
                
                <div class="form-group">
                    <label for="protein">Protein (g)</label>
                    <input type="number" id="protein" name="protein" class="form-control" step="0.1" min="0" required readonly>
                </div>
                
                <div class="nutrition-note">
                    <i class="fas fa-info-circle"></i> Nutrition values will update based on the quantity you select.
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancel-btn">Cancel</button>
                    <button type="submit" name="save_to_diet" class="btn btn-success">Save to Diet Tracker</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Modal Functionality
        const modal = document.getElementById('add-food-modal');
        const modalClose = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('cancel-btn');
        const foodNameInput = document.getElementById('food_name');
        const baseCaloriesInput = document.getElementById('base_calories');
        const baseProteinInput = document.getElementById('base_protein');
        const quantityInput = document.getElementById('quantity');
        const quantityUnitInput = document.getElementById('quantity_unit');
        const caloriesInput = document.getElementById('calories');
        const proteinInput = document.getElementById('protein');
        
        // Function to update nutrition values based on quantity
        function updateNutritionValues() {
            const quantity = parseFloat(quantityInput.value) || 1;
            const baseCalories = parseFloat(baseCaloriesInput.value) || 0;
            const baseProtein = parseFloat(baseProteinInput.value) || 0;
            
            // Calculate new values based on quantity
            const newCalories = (baseCalories * quantity).toFixed(1);
            const newProtein = (baseProtein * quantity).toFixed(1);
            
            // Update the displayed values
            caloriesInput.value = newCalories;
            proteinInput.value = newProtein;
        }
        
        // Add event listeners to quantity and unit inputs
        quantityInput.addEventListener('input', updateNutritionValues);
        quantityUnitInput.addEventListener('change', updateNutritionValues);
        
        // Add event listeners to all "Add to Diet" buttons
        document.querySelectorAll('.btn-add-food').forEach(button => {
            button.addEventListener('click', function() {
                const foodName = this.getAttribute('data-food-name');
                const calories = this.getAttribute('data-calories');
                const protein = this.getAttribute('data-protein');
                
                foodNameInput.value = foodName;
                baseCaloriesInput.value = calories;
                baseProteinInput.value = protein;
                
                // Reset quantity to 1
                quantityInput.value = 1;
                quantityUnitInput.value = 'serving';
                
                // Set initial nutrition values
                caloriesInput.value = calories;
                proteinInput.value = protein;
                
                modal.classList.add('active');
            });
        });
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        modalClose.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        // Toggle food description visibility
        document.querySelectorAll('.btn-toggle-description').forEach(button => {
            button.addEventListener('click', function() {
                const description = this.nextElementSibling;
                const isHidden = description.style.display === 'none';
                
                // Toggle visibility
                description.style.display = isHidden ? 'block' : 'none';
                
                // Update button text
                this.innerHTML = isHidden 
                    ? '<i class="fas fa-info-circle"></i> Hide Nutritional Information' 
                    : '<i class="fas fa-info-circle"></i> Show Nutritional Information';
            });
        });
    </script>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>