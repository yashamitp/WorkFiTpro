<?php
// Test file for ExerciseDB API

// RapidAPI key
$api_key = "40e819cafcmshbb90df3af6c5dabp168983jsn8cbaba0e7c36";

// API endpoint
$url = "https://exercisedb.p.rapidapi.com/exercises/bodyPart/back?limit=10&offset=0";

// Initialize cURL session
$curl = curl_init();

// Set cURL options
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: exercisedb.p.rapidapi.com",
        "x-rapidapi-key: " . $api_key
    ],
]);

// Execute cURL session and get the response
$response = curl_exec($curl);
$err = curl_error($curl);

// Close cURL session
curl_close($curl);

// Output results
echo "<h1>ExerciseDB API Test</h1>";

if ($err) {
    echo "<p style='color: red;'>cURL Error: " . $err . "</p>";
} else {
    // Decode JSON response
    $exercises = json_decode($response, true);
    
    if (is_array($exercises)) {
        echo "<p style='color: green;'>API call successful! Found " . count($exercises) . " exercises.</p>";
        
        echo "<h2>First Exercise Details:</h2>";
        if (count($exercises) > 0) {
            $first = $exercises[0];
            echo "<pre>";
            print_r($first);
            echo "</pre>";
        } else {
            echo "<p>No exercises returned.</p>";
        }
    } else {
        echo "<p style='color: red;'>Error parsing API response. Raw response:</p>";
        echo "<pre>" . $response . "</pre>";
    }
}
?>