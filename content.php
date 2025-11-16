<?php
// Get plugin directory
$pluginDir = dirname(__FILE__);
$plugin = basename($pluginDir);

// Fetch current configuration
$configUrl = "http://localhost/api/plugin/{$plugin}/config";
$currentConfig = array('playlist_name' => '');

try {
    $ch = curl_init($configUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $currentConfig = json_decode($response, true);
    }
} catch (Exception $e) {
    // Ignore errors
}

// Fetch available playlists
$playlistsUrl = "http://localhost/api/plugin/{$plugin}/playlists";
$playlists = array();

try {
    $ch = curl_init($playlistsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['playlists'])) {
            $playlists = $data['playlists'];
        }
    }
} catch (Exception $e) {
    // Ignore errors
}

$currentPlaylist = isset($currentConfig['playlist_name']) ? $currentConfig['playlist_name'] : '';
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

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 15px;
    margin-bottom: 8px;
}

.form-select {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: 15px;
    font-family: inherit;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 40px;
    cursor: pointer;
    transition: border-color 0.2s;
}

.form-select:hover {
    border-color: #007aff;
}

.form-select:focus {
    outline: none;
    border-color: #007aff;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

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

.homekit-button.secondary {
    background: var(--text-secondary);
}

.info-text {
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.6;
    margin: 8px 0;
}

.info-text strong {
    color: var(--text-primary);
}

.info-list {
    color: var(--text-secondary);
    padding-left: 20px;
    line-height: 1.8;
    margin: 12px 0;
}

.info-list li {
    margin-bottom: 8px;
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

.message {
    padding: 12px 16px;
    border-radius: 10px;
    margin-top: 16px;
    font-size: 15px;
}

.message.success {
    background: rgba(52, 199, 89, 0.1);
    color: var(--success-color);
}

.message.error {
    background: rgba(255, 59, 48, 0.1);
    color: var(--error-color);
}

.message.info {
    background: rgba(0, 122, 255, 0.1);
    color: #007aff;
}

.link {
    color: #007aff;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}
</style>

<div class="homekit-container">
    <div class="homekit-card">
        <h2>Configuration</h2>
        
        <div class="form-group">
            <label class="form-label" for="playlist_name">Playlist</label>
            <select class="form-select" name="playlist_name" id="playlist_name">
                <option value="">-- Select Playlist --</option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?php echo htmlspecialchars($playlist); ?>" 
                        <?php echo ($playlist == $currentPlaylist) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($playlist); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="info-text" style="margin-top: 8px;">Select which playlist should start when HomeKit turns the light ON.</p>
        </div>
        
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <button class="homekit-button" type="button" onclick="saveConfig()">Save Configuration</button>
            <button class="homekit-button secondary" type="button" onclick="loadPlaylists()">Refresh Playlists</button>
        </div>
        
        <div id="message"></div>
    </div>
    
    <div class="homekit-card">
        <h3>Current Configuration</h3>
        <div class="status-row">
            <span class="status-label">Selected Playlist</span>
            <span class="status-value">
                <?php if ($currentPlaylist): ?>
                    <span style="color: var(--success-color);"><?php echo htmlspecialchars($currentPlaylist); ?></span>
                <?php else: ?>
                    <span style="color: var(--warning-color);">No playlist selected</span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    
    <div class="homekit-card">
        <h3>How It Works</h3>
        <ul class="info-list">
            <li>When you turn the HomeKit light <strong>ON</strong>, the selected playlist will start playing</li>
            <li>When you turn the HomeKit light <strong>OFF</strong>, FPP playback will stop</li>
            <li>Make sure to select a playlist before pairing with HomeKit for best results</li>
            <li>You can change the playlist at any time - the change will take effect on the next ON command</li>
        </ul>
    </div>
    
    <div class="homekit-card">
        <h3>Pairing Instructions</h3>
        <ol class="info-list">
            <li>Configure the playlist above</li>
            <li>Go to the <a href="plugin.php?plugin=<?php echo $plugin; ?>&page=status.php" class="link">Status page</a> to view the QR code</li>
            <li>Open the Home app on your iPhone or iPad</li>
            <li>Tap the "+" button to add an accessory</li>
            <li>Scan the QR code or enter the setup code manually</li>
            <li>Once paired, you can control FPP from the Home app</li>
        </ol>
    </div>
</div>

<script>
function saveConfig() {
    var playlistName = document.getElementById('playlist_name').value;
    var messageDiv = document.getElementById('message');
    
    messageDiv.className = 'message info';
    messageDiv.innerHTML = 'Saving...';
    
    var formData = new FormData();
    formData.append('playlist_name', playlistName);
    
    fetch('<?php echo "http://localhost/api/plugin/{$plugin}/config"; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'saved') {
            messageDiv.className = 'message success';
            messageDiv.innerHTML = '✓ Configuration saved successfully!';
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            messageDiv.className = 'message error';
            messageDiv.innerHTML = '✗ Error: ' + (data.message || 'Failed to save');
        }
    })
    .catch(error => {
        messageDiv.className = 'message error';
        messageDiv.innerHTML = '✗ Error: ' + error;
    });
}

function loadPlaylists() {
    location.reload();
}
</script>
