<?php
/**
 * Test script to simulate the "Emulate ON" button click
 * This shows what commands would be executed
 */

// Define the json function that FPP uses
function json($data) {
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    exit();
}

// Mock the FPP HomeKit API functions
function fppHomekitSettingsCandidates() {
    return array('/home/fpp/media/settings');
}

function fppHomekitReadHttpPort($settingsPath) {
    return 32320; // Default FPP port
}

// Include the actual API file (it has the emulate function)
include_once('api.php');

// Test the emulate function with value=1 (ON button)
echo "=== Testing Emulate ON Button (value=1) ===\n";
echo "This simulates clicking the 'Emulate ON' button\n\n";

// Mock POST data
$_POST['value'] = 1;

// Call the function (this will try to execute mosquitto_pub commands)
echo "Calling fppHomekitEmulate()...\n";
$result = fppHomekitEmulate();

// This won't print if json() exits, but let's add some debug output
echo "\nFunction completed.\n";
?>
