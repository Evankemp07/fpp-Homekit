<?php
// Get plugin directory
$pluginDir = dirname(__FILE__);
$plugin = basename($pluginDir);

// Load CSS file
$cssPath = $pluginDir . '/styles.css';
?>

<style>
<?php
if (file_exists($cssPath)) {
    readfile($cssPath);
}
?>
</style>

<div class="homekit-container">
    <div class="homekit-card">
        <h2>Configuration</h2>
        
        <div id="message-container"></div>
        
        <div class="form-group">
            <label class="form-label" for="playlist_name">Playlist</label>
            <select class="form-select" name="playlist_name" id="playlist_name">
                <option value="">-- Loading playlists... --</option>
            </select>
            <p class="info-text" style="margin-top: 8px;">Select which playlist should start when HomeKit turns the light ON.</p>
        </div>
        
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
            <button class="homekit-button" type="button" onclick="saveConfig()" id="save-btn">Save Configuration</button>
            <button class="homekit-button secondary" type="button" onclick="loadPlaylists()" id="refresh-btn">Refresh Playlists</button>
        </div>
    </div>
    
    <div class="homekit-card">
        <h3>Current Configuration</h3>
        <div class="status-row">
            <span class="status-label">Selected Playlist</span>
            <span class="status-value" id="current-playlist">
                Loading...
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
(function() {
    const API_BASE = '/api/plugin/<?php echo $plugin; ?>';
    
    // Show message
    function showMessage(message, type) {
        const container = document.getElementById('message-container');
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + type;
        msgDiv.innerHTML = escapeHtml(message);
        msgDiv.style.opacity = '0';
        msgDiv.style.transform = 'translateY(-20px)';
        container.innerHTML = '';
        container.appendChild(msgDiv);
        
        setTimeout(() => {
            msgDiv.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            msgDiv.style.opacity = '1';
            msgDiv.style.transform = 'translateY(0)';
        }, 10);
        
        if (type === 'success') {
            setTimeout(() => {
                msgDiv.style.opacity = '0';
                msgDiv.style.transform = 'translateY(-20px)';
                setTimeout(() => msgDiv.remove(), 500);
            }, 5000);
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Load playlists
    function loadPlaylists() {
        const select = document.getElementById('playlist_name');
        const refreshBtn = document.getElementById('refresh-btn');
        
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="spinner"></span> Loading...';
        select.disabled = true;
        
        fetch(API_BASE + '/playlists')
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '<option value="">-- Select Playlist --</option>';
                if (data.playlists && data.playlists.length > 0) {
                    data.playlists.forEach(playlist => {
                        const option = document.createElement('option');
                        option.value = playlist;
                        option.textContent = playlist;
                        select.appendChild(option);
                    });
                    loadCurrentConfig();
                } else {
                    select.innerHTML = '<option value="">No playlists available</option>';
                }
                select.disabled = false;
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Refresh Playlists';
            })
            .catch(error => {
                showMessage('Error loading playlists: ' + error.message, 'error');
                select.disabled = false;
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Refresh Playlists';
            });
    }
    
    // Load current configuration
    function loadCurrentConfig() {
        fetch(API_BASE + '/config')
            .then(response => response.json())
            .then(data => {
                const playlist = data.playlist_name || '';
                const select = document.getElementById('playlist_name');
                const currentEl = document.getElementById('current-playlist');
                
                if (playlist) {
                    select.value = playlist;
                    currentEl.innerHTML = '<span style="color: var(--success-color);">' + escapeHtml(playlist) + '</span>';
                } else {
                    currentEl.innerHTML = '<span style="color: var(--warning-color);">No playlist selected</span>';
                }
            })
            .catch(error => {
                console.error('Error loading config:', error);
            });
    }
    
    // Save configuration
    window.saveConfig = function() {
        const playlistName = document.getElementById('playlist_name').value;
        const saveBtn = document.getElementById('save-btn');
        const messageDiv = document.getElementById('message-container');
        
        if (!playlistName) {
            showMessage('Please select a playlist', 'warning');
            return;
        }
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner"></span> Saving...';
        
        const formData = new FormData();
        formData.append('playlist_name', playlistName);
        
        fetch(API_BASE + '/config', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'saved') {
                showMessage('✓ Configuration saved successfully!', 'success');
                loadCurrentConfig();
            } else {
                showMessage('✗ Error: ' + (data.message || 'Failed to save'), 'error');
            }
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Configuration';
        })
        .catch(error => {
            showMessage('✗ Error: ' + error.message, 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Configuration';
        });
    };
    
    // Refresh playlists
    window.loadPlaylists = function() {
        loadPlaylists();
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadPlaylists();
        loadCurrentConfig();
    });
})();
</script>
