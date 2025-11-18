<?php
// Simple test script for the emulate functionality

// Define the json function that FPP uses
function json($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

// Include the API file
include_once('api.php');

// Test both ON and OFF
$test_cases = [
    ['value' => 1, 'description' => 'ON button (start playlist)'],
    ['value' => 0, 'description' => 'OFF button (stop playlist)']
];

foreach ($test_cases as $test) {
    // Mock POST data
    $_POST['value'] = $test['value'];

    // Call the emulate function
    echo "\n=== Testing emulate function with value={$test['value']} ({$test['description']}) ===\n";
    $result = fppHomekitEmulate();

    // Since json() exits, add a small delay between tests
    sleep(1);
}

// Since json() exits, this won't be reached, but let's see what happens
echo "Function returned (this shouldn't print if json() works): " . print_r($result, true) . "\n";
?>
