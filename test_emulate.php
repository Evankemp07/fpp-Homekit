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

// Mock POST data
$_POST['value'] = 1;

// Call the emulate function
echo "Testing emulate function with value=1 (should start playlist)...\n";
$result = fppHomekitEmulate();

// Since json() exits, this won't be reached, but let's see what happens
echo "Function returned (this shouldn't print if json() works): " . print_r($result, true) . "\n";
?>
