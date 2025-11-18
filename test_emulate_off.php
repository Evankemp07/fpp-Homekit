<?php
// Test script for OFF button (stop playlist)

// Define the json function that FPP uses
function json($data) {
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    exit();
}

// Include the API file
include_once('api.php');

// Mock POST data for OFF button
$_POST['value'] = 0;

// Call the emulate function
echo "Testing emulate function with value=0 (OFF button - should stop playlist)...\n";
$result = fppHomekitEmulate();

// Since json() exits, this won't be reached
echo "Function returned (this shouldn't print if json() works): " . print_r($result, true) . "\n";
?>
