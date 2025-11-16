<?php
// Get plugin directory
$pluginDir = dirname(__FILE__);
$plugin = basename($pluginDir);

// Fetch status from API
$statusUrl = "http://localhost/api/plugin/{$plugin}/status";
$statusData = array();

try {
    $ch = curl_init($statusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $statusData = json_decode($response, true);
    }
} catch (Exception $e) {
    // Ignore errors
}

$serviceRunning = isset($statusData['service_running']) ? $statusData['service_running'] : false;
$paired = isset($statusData['paired']) ? $statusData['paired'] : false;
$fppStatus = isset($statusData['fpp_status']) ? $statusData['fpp_status'] : array('playing' => false, 'status_name' => 'unknown');
$playlist = isset($statusData['playlist']) ? $statusData['playlist'] : '';

// Get pairing info
$pairingUrl = "http://localhost/api/plugin/{$plugin}/pairing-info";
$pairingData = array('setup_code' => '123-45-678', 'setup_id' => 'HOME');

try {
    $ch = curl_init($pairingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $pairingData = json_decode($response, true);
    }
} catch (Exception $e) {
    // Ignore errors
}

$qrCodeUrl = "http://localhost/api/plugin/{$plugin}/qr-code";
?>

<style>
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f5f5f7;
    --text-primary: #1d1d1f;
    --text-secondary: #86868b;
    --border-color: #d2d2d7;
    --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    --success-color: #34c759;
    --error-color: #ff3b30;
    --warning-color: #ff9500;
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg-primary: #1d1d1f;
        --bg-secondary: #2c2c2e;
        --text-primary: #f5f5f7;
        --text-secondary: #86868b;
        --border-color: #38383a;
        --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
}

.homekit-container {
    max-width: 680px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    color: var(--text-primary);
    background: var(--bg-secondary);
    min-height: 100vh;
}

.homekit-card {
    background: var(--bg-primary);
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.homekit-card h2 {
    margin: 0 0 20px 0;
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -0.5px;
    color: var(--text-primary);
}

.homekit-card h3 {
    margin: 0 0 16px 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
}

.status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.status-row:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 15px;
}

.status-value {
    font-weight: 500;
    font-size: 15px;
    color: var(--text-primary);
}

.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.running { background: var(--success-color); }
.status-indicator.stopped { background: var(--error-color); }
.status-indicator.paired { background: var(--success-color); }
.status-indicator.not-paired { background: var(--warning-color); }
.status-indicator.playing { background: var(--success-color); }

.homekit-button {
    background: #007aff;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    margin-right: 10px;
    font-family: inherit;
}

.homekit-button:hover {
    background: #0051d5;
    transform: translateY(-1px);
}

.homekit-button:active {
    transform: translateY(0);
}

.qr-container {
    text-align: center;
    padding: 20px 0;
}

.qr-code {
    border-radius: 16px;
    padding: 20px;
    background: white;
    display: inline-block;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.setup-code {
    font-size: 24px;
    font-weight: 600;
    margin-top: 16px;
    letter-spacing: 2px;
    font-family: 'SF Mono', Monaco, monospace;
    color: var(--text-primary);
}

.info-text {
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.5;
    margin: 8px 0;
}

.info-text strong {
    color: var(--text-primary);
}

.success-message {
    color: var(--success-color);
    font-size: 17px;
    font-weight: 500;
    margin: 8px 0;
}

.link {
    color: #007aff;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

@media (prefers-color-scheme: dark) {
    .qr-code {
        background: white;
    }
}
</style>

<div class="homekit-container">
    <div class="homekit-card">
        <h2>HomeKit Status</h2>
        
        <div class="status-row">
            <span class="status-label">HomeKit Service</span>
            <span class="status-value">
                <span class="status-indicator <?php echo $serviceRunning ? 'running' : 'stopped'; ?>"></span>
                <?php echo $serviceRunning ? 'Running' : 'Stopped'; ?>
            </span>
        </div>
        
        <div class="status-row">
            <span class="status-label">Pairing Status</span>
            <span class="status-value">
                <span class="status-indicator <?php echo $paired ? 'paired' : 'not-paired'; ?>"></span>
                <?php echo $paired ? 'Paired' : 'Not Paired'; ?>
            </span>
        </div>
        
        <div class="status-row">
            <span class="status-label">FPP Status</span>
            <span class="status-value">
                <?php 
                $playing = isset($fppStatus['playing']) ? $fppStatus['playing'] : false;
                $statusName = isset($fppStatus['status_name']) ? $fppStatus['status_name'] : 'unknown';
                if ($playing || $statusName == 'playing'): 
                ?>
                    <span class="status-indicator playing"></span>Playing
                <?php else: ?>
                    <span class="status-indicator stopped"></span><?php echo htmlspecialchars(ucfirst($statusName)); ?>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="status-row">
            <span class="status-label">Configured Playlist</span>
            <span class="status-value">
                <?php if ($playlist): ?>
                    <?php echo htmlspecialchars($playlist); ?>
                <?php else: ?>
                    <span style="color: var(--warning-color);">Not configured - <a href="plugin.php?plugin=<?php echo $plugin; ?>&page=content.php" class="link">Configure now</a></span>
                <?php endif; ?>
            </span>
        </div>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <button class="homekit-button" onclick="restartService()">Restart Service</button>
            <button class="homekit-button" onclick="location.reload()" style="background: var(--text-secondary);">Refresh</button>
        </div>
    </div>
    
    <?php if (!$paired): ?>
    <div class="homekit-card">
        <h3>Pair with HomeKit</h3>
        <p class="info-text">To pair this accessory with HomeKit:</p>
        <ol style="color: var(--text-secondary); padding-left: 20px; line-height: 1.8;">
            <li>Open the Home app on your iPhone or iPad</li>
            <li>Tap the "+" button to add an accessory</li>
            <li>Scan the QR code below, or enter the setup code manually</li>
        </ol>
        
        <div class="qr-container">
            <?php if ($serviceRunning): ?>
                <div class="qr-code">
                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="HomeKit QR Code" style="max-width: 280px; display: block;" />
                </div>
                <div class="setup-code"><?php echo htmlspecialchars($pairingData['setup_code']); ?></div>
            <?php else: ?>
                <p style="color: var(--error-color);">Service is not running. Please start the service to generate QR code.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="homekit-card">
        <h3>Pairing Status</h3>
        <p class="success-message">✓ Successfully paired with HomeKit</p>
        <p class="info-text">You can now control FPP from the Home app on your iOS devices.</p>
    </div>
    <?php endif; ?>
    
    <div class="homekit-card">
        <h3>Information</h3>
        <p class="info-text"><strong>Accessory Name:</strong> FPP Light</p>
        <p class="info-text"><strong>Accessory Type:</strong> Light</p>
        <p class="info-text"><strong>Control:</strong> Turning the light ON will start the configured playlist. Turning it OFF will stop playback.</p>
    </div>
</div>

<script>
function restartService() {
    if (confirm('Are you sure you want to restart the HomeKit service?')) {
        fetch('<?php echo "http://localhost/api/plugin/{$plugin}/restart"; ?>', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            alert('Service restart initiated. Please wait a few seconds and refresh the page.');
            setTimeout(function() {
                location.reload();
            }, 3000);
        })
        .catch(error => {
            alert('Error restarting service: ' + error);
        });
    }
}
</script>
