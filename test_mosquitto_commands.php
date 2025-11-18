<?php
/**
 * Test script showing what mosquitto_pub commands the emulate button tries to run
 */

echo "=== Testing Emulate ON Button Commands ===\n\n";

// Load configuration
$configFile = __DIR__ . '/scripts/homekit_config.json';
$config = json_decode(file_get_contents($configFile), true);

if (!$config) {
    echo "ERROR: Could not load config from $configFile\n";
    exit(1);
}

$playlistName = $config['playlist_name'] ?? '';
$mqttConfig = $config['mqtt'] ?? ['broker' => 'localhost', 'port' => 1883, 'topic_prefix' => 'FPP'];

echo "Configuration loaded:\n";
echo "- Playlist: $playlistName\n";
echo "- MQTT Broker: {$mqttConfig['broker']}:{$mqttConfig['port']}\n";
echo "- Topic Prefix: {$mqttConfig['topic_prefix']}\n\n";

if (empty($playlistName)) {
    echo "ERROR: No playlist configured!\n";
    exit(1);
}

// Simulate what the emulate function does
$value = 1; // Emulate ON button
if ($value === 1) {
    $command = "StartPlaylist/{$playlistName}";
    $action = 'start';
} else {
    $command = "Stop";
    $action = 'stop';
}

echo "Emulate ON button pressed:\n";
echo "- Action: $action\n";
echo "- Command: $command\n\n";

// Show the mosquitto_pub commands that would be tried
$topics_to_try = array(
    "{$mqttConfig['topic_prefix']}/command/{$command}",
    "FPP/command/{$command}",
    "fpp/command/{$command}"
);

echo "Commands that will be attempted (in order):\n";
foreach ($topics_to_try as $i => $topic) {
    $cmd = "mosquitto_pub -h '{$mqttConfig['broker']}' -p '{$mqttConfig['port']}' -t '{$topic}' -m ''";
    echo ($i+1) . ". $cmd\n";
}

echo "\n=== Testing Commands ===\n";

// Test if mosquitto_pub is available
$mosquittoAvailable = shell_exec('which mosquitto_pub 2>/dev/null');
if (!$mosquittoAvailable) {
    echo "❌ mosquitto_pub not found - this is why the button doesn't work!\n";
    echo "Install with: sudo apt-get install mosquitto-clients\n";
} else {
    echo "✅ mosquitto_pub is available\n";

    // Try the first command to see what happens
    $firstTopic = $topics_to_try[0];
    $testCmd = "mosquitto_pub -h '{$mqttConfig['broker']}' -p '{$mqttConfig['port']}' -t '{$firstTopic}' -m '' 2>&1";
    echo "\nTesting first command: $testCmd\n";

    $output = shell_exec($testCmd);
    $exitCode = 0; // shell_exec doesn't give exit codes directly

    if ($output === null) {
        echo "✅ Command executed (no error output)\n";
    } else {
        echo "⚠️  Command output: " . trim($output) . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "The emulate button should work after installing mosquitto-clients on FPP.\n";
echo "Expected result: Clicking 'Emulate ON' starts playlist '$playlistName'\n";
?>
