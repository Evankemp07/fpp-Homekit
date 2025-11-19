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
$normalizedPrefix = isset($mqttConfig['topic_prefix']) ? trim($mqttConfig['topic_prefix'], '/') : '';
$prefixCandidates = array();
$addCandidate = function ($candidate) use (&$prefixCandidates) {
    $normalized = trim((string)$candidate, '/');
    if (!in_array($normalized, $prefixCandidates, true)) {
        $prefixCandidates[] = $normalized;
    }
};

if ($normalizedPrefix !== '') {
    $addCandidate($normalizedPrefix);
    $addCandidate($normalizedPrefix . '/' . $normalizedPrefix);
}

foreach (array(
    'falcon/player/FPP2',
    'falcon/player/FPP2/falcon/player/FPP2',
    'falcon/player/FPP',
    'FPP',
    'fpp'
) as $fallbackPrefix) {
    $addCandidate($fallbackPrefix);
}

if (!in_array('', $prefixCandidates, true)) {
    $prefixCandidates[] = '';
}

$topics_to_try = array();
$addTopic = function ($basePrefix, $suffix) use (&$topics_to_try) {
    $suffix = trim($suffix, '/');
    if ($suffix === '') {
        return;
    }
    $topic = $basePrefix !== '' ? trim($basePrefix . '/' . $suffix, '/') : $suffix;
    $topic = preg_replace('#//+#', '/', $topic);
    if (!in_array($topic, $topics_to_try, true)) {
        $topics_to_try[] = $topic;
    }
};

if ($action === 'start') {
    foreach ($prefixCandidates as $base) {
        $addTopic($base, "set/playlist/{$playlistName}/start");
        $addTopic($base, "command/StartPlaylist/{$playlistName}");
        $addTopic($base, "set/command/StartPlaylist/{$playlistName}");
    }
} else {
    foreach ($prefixCandidates as $base) {
        $addTopic($base, "set/playlist/{$playlistName}/stop/now");
        $addTopic($base, "set/playlist/{$playlistName}/stop");
        $addTopic($base, "command/Stop");
        $addTopic($base, "set/command/Stop");
    }
}

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
    $testCmd = sprintf(
        "mosquitto_pub -h %s -p %s -t %s -m '' 2>&1",
        escapeshellarg($mqttConfig['broker']),
        escapeshellarg((string)$mqttConfig['port']),
        escapeshellarg($firstTopic)
    );
    echo "\nTesting first command: $testCmd\n";

    $output = array();
    $exitCode = 1;
    exec($testCmd, $output, $exitCode);
    $outputText = trim(implode("\n", $output));

    if ($exitCode === 0) {
        echo "✅ Command executed successfully (exit=0)\n";
        if ($outputText !== '') {
            echo "ℹ️ Output: $outputText\n";
        }
    } else {
        echo "⚠️ Command failed (exit=$exitCode)\n";
        if ($outputText !== '') {
            echo "   Output: $outputText\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "The emulate button should work after installing mosquitto-clients on FPP.\n";
echo "Expected result: Clicking 'Emulate ON' starts playlist '$playlistName'\n";
?>
