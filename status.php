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
        <h2>FPP Homekit</h2>
        
        <div id="message-container"></div>
        
        <div id="status-content">
            <div class="status-cards-container">
                <div class="status-card">
                    <div class="status-card-label">HomeKit Service</div>
                    <div class="status-card-value" id="service-status">Loading...</div>
                </div>
                
                <div class="status-card">
                    <div class="status-card-label">Pairing Status</div>
                    <div class="status-card-value" id="pairing-status">Loading...</div>
                </div>
                
                <div class="status-card" id="fpp-status-card">
                    <div class="status-card-label">FPP Status</div>
                    <div class="status-card-value" id="fpp-status" style="display: flex; align-items: center; justify-content: space-between;">
                        <span id="fpp-status-text">Loading...</span>
                        <span class="status-dot-large" id="fpp-status-dot"></span>
                    </div>
                </div>
                
                <div class="status-card" id="playlist-status-card">
                    <div class="status-card-label">Configured Playlist</div>
                    <div class="status-card-value" id="playlist-status">Loading...</div>
                </div>
            </div>
            
            <div class="playlist-config">
                <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">Playlist Configuration</h3>
                <div class="playlist-config-controls">
                    <select class="form-select" id="playlist-select" aria-label="Select playlist to start">
                        <option value="">-- Loading playlists... --</option>
                    </select>
                    <button class="homekit-button" type="button" id="save-playlist-btn">Save Playlist</button>
                </div>
            </div>
            
            <div class="playlist-config" style="margin-top: 24px;">
                <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">MQTT Configuration</h3>
                <div class="playlist-config-controls" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <label for="mqtt-broker" style="font-weight: 500; color: var(--text-secondary);">Broker:</label>
                        <input type="text" class="form-select" id="mqtt-broker" placeholder="localhost" style="width: 120px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <label for="mqtt-port" style="font-weight: 500; color: var(--text-secondary);">Port:</label>
                        <input type="number" class="form-select" id="mqtt-port" placeholder="1883" min="1" max="65535" style="width: 100px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>
                    <button class="homekit-button" type="button" id="save-mqtt-btn">Save MQTT</button>
                    <button class="homekit-button secondary" type="button" id="test-mqtt-btn">Test MQTT</button>
                </div>
            </div>
            
            <div class="playlist-config" style="margin-top: 24px;">
                <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">HomeKit Network</h3>
                <div class="playlist-config-controls" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <label for="homekit-ip" style="font-weight: 500; color: var(--text-secondary);">Listen Address:</label>
                        <select class="form-select" id="homekit-ip" style="min-width: 220px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                            <option value="">Auto-detect (Primary Interface)</option>
                            <option value="0.0.0.0">All Interfaces (0.0.0.0)</option>
                        </select>
                    </div>
                    <button class="homekit-button" type="button" id="save-homekit-network-btn">Save & Restart</button>
                </div>
                <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                    Select which network interface HomeKit should listen on. Choose a specific interface if you get "not reachable" errors.
                </div>
            </div>
            
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <div style="display: flex; gap: 12px; justify-content: flex-start; flex-wrap: wrap; margin-bottom: 16px;">
                    <button class="homekit-button" onclick="restartService()" id="restart-btn">Restart Service</button>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h3>Pair with HomeKit</h3>
            <div id="pairing-section">
                <div class="qr-container">
                    <div id="qr-loading" style="display: none;">
                        <p class="info-text">Generating QR code...</p>
                    </div>
                    <div id="qr-content" style="display: none;">
                        <div class="qr-code">
                            <img id="qr-image" alt="HomeKit QR Code" style="max-width: 280px; display: block;" />
                        </div>
                        <div class="setup-code">
                            <span class="setup-code-label">Setup Code</span>
                            <div class="setup-code-value">
                                <span id="setup-code-text"></span>
                                <button class="copy-btn" onclick="copySetupCode()" title="Copy Setup Code" id="copy-btn">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.5 2.5H11.5C12.0523 2.5 12.5 2.94772 12.5 3.5V9.5C12.5 10.0523 12.0523 10.5 11.5 10.5H9.5V12.5C9.5 13.0523 9.05228 13.5 8.5 13.5H2.5C1.94772 13.5 1.5 13.0523 1.5 12.5V6.5C1.5 5.94772 1.94772 5.5 2.5 5.5H4.5V3.5C4.5 2.94772 4.94772 2.5 5.5 2.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4.5 5.5H8.5C9.05228 5.5 9.5 5.94772 9.5 6.5V10.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="qr-error" style="display: none;">
                        <p style="color: var(--error-color);">Service is not running. Please start the service to generate QR code.</p>
                    </div>
                </div>
            </div>
            
            <div id="paired-section" style="display: none;">
                <p class="success-message">✓ Successfully paired with HomeKit</p>
                <p class="info-text">You can now control FPP from the Home app on your iOS devices.</p>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h3>Information</h3>
            <p class="info-text"><strong>Accessory Name:</strong> FPP-Controller</p>
            <p class="info-text"><strong>Accessory Type:</strong> Light</p>
            <p class="info-text"><strong>Control:</strong> Turning the light ON will start the configured playlist. Turning it OFF will stop playback.</p>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h4 class="debug-header" onclick="toggleDebug()" style="margin: 0 0 16px 0; cursor: pointer; user-select: none; display: flex; align-items: center; gap: 8px; color: var(--text-primary); font-weight: 600;">
                <span class="debug-toggle-icon" id="debug-toggle-icon" style="display: inline-block; transition: transform 0.2s; transform: rotate(-90deg);">▼</span>
                Debug Messages
            </h4>
            <div class="debug-messages" id="debug-messages" style="display: none; color: var(--text-secondary); line-height: 1.6; max-height: 300px; overflow-y: auto; font-family: 'SF Mono', Monaco, monospace; font-size: 12px;">
                <div>No debug messages yet...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const API_BASE = '/api/plugin/<?php echo $plugin; ?>';
    let refreshInterval = null;
    let isUpdating = false;
    let playlistsLoaded = false;
    let currentPlaylist = '';
    let qrLoaded = false;
    let autoStartAttempted = false;
    let playlistFetchInProgress = false;
    
    const playlistSelect = document.getElementById('playlist-select');
    const savePlaylistBtn = document.getElementById('save-playlist-btn');
    
    // Basic debug logging (console + UI)
    function debugLog(message, data = null) {
        // Log to console
        if (data) {
            console.log('[FPP-HomeKit]', message, data);
        } else {
            console.log('[FPP-HomeKit]', message);
        }
        
        // Log to UI (keep last 15 messages)
        const debugContainer = document.getElementById('debug-messages');
        if (!debugContainer) return;
        
        const placeholder = debugContainer.querySelector('div:only-child');
        if (placeholder && placeholder.textContent === 'No debug messages yet...') {
            placeholder.remove();
        }
        
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.className = 'debug-entry';
        
        let content = '<strong style="color: var(--info-color);">[' + timestamp + ']</strong> ' + escapeHtml(message);
        if (data) {
            content += '<pre>' + escapeHtml(JSON.stringify(data, null, 2)) + '</pre>';
        }
        logEntry.innerHTML = content;
        
        debugContainer.insertBefore(logEntry, debugContainer.firstChild);
        
        // Keep only last 15 messages
        while (debugContainer.children.length > 15) {
            debugContainer.removeChild(debugContainer.lastChild);
        }
    }
    
    // Toggle debug section
    window.toggleDebug = function() {
        const debugMessages = document.getElementById('debug-messages');
        const toggleIcon = document.getElementById('debug-toggle-icon');
        if (!debugMessages || !toggleIcon) return;
        
        const isHidden = debugMessages.style.display === 'none' || 
                        (debugMessages.style.display === '' && !debugMessages.classList.contains('open'));
        
        if (isHidden) {
            debugMessages.style.display = 'block';
            debugMessages.classList.add('open');
            toggleIcon.style.transform = 'rotate(0deg)';
        } else {
            debugMessages.style.display = 'none';
            debugMessages.classList.remove('open');
            toggleIcon.style.transform = 'rotate(-90deg)';
        }
    };
    
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
    
    function updatePlaylistStatusText(name) {
        const playlistStatusEl = document.getElementById('playlist-status');
        if (!playlistStatusEl) {
            return;
        }
        if (name) {
            playlistStatusEl.textContent = name;
        } else {
            playlistStatusEl.textContent = 'Not configured';
        }
    }
    
    function setPlaylistLoadingState(isLoading) {
        playlistFetchInProgress = isLoading;
        if (playlistSelect) {
            playlistSelect.disabled = isLoading;
        }
        if (!isLoading) {
            updateSaveButtonState();
        }
    }
    
    function updateSaveButtonState() {
        if (!savePlaylistBtn || !playlistSelect) {
            return;
        }
        const selectedValue = playlistSelect.value;
        const shouldDisable = !selectedValue || selectedValue === currentPlaylist;
        savePlaylistBtn.disabled = shouldDisable;
    }
    
    function loadPlaylists(force = false) {
        if (!playlistSelect) {
            return;
        }
        if (playlistFetchInProgress) {
            return;
        }
        if (!force && document.activeElement === playlistSelect) {
            return;
        }
        
        if (force) {
            setPlaylistLoadingState(true);
        } else {
            playlistFetchInProgress = true;
        }
        fetch(API_BASE + '/playlists')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                playlistsLoaded = true;
                playlistSelect.innerHTML = '';
                
                if (data.playlists && data.playlists.length > 0) {
                    playlistSelect.appendChild(new Option('-- Select Playlist --', ''));
                    data.playlists.forEach((playlist) => {
                        playlistSelect.appendChild(new Option(playlist, playlist));
                    });
                } else {
                    playlistSelect.appendChild(new Option('No playlists available', ''));
                    if (force) {
                        showMessage('No playlists found. Create playlists in FPP first.', 'warning');
                    }
                }
                
                // Set the selected value - prioritize currentPlaylist if set
                const valueToSet = currentPlaylist || '';
                playlistSelect.value = valueToSet;
                // If value was set, also update currentPlaylist to match
                if (valueToSet && playlistSelect.value === valueToSet) {
                    currentPlaylist = valueToSet;
                }
                updateSaveButtonState();
            })
            .catch(error => {
                showMessage('Error loading playlists: ' + error.message, 'error');
            })
            .finally(() => {
                if (force) {
                    setPlaylistLoadingState(false);
                } else {
                    playlistFetchInProgress = false;
                    updateSaveButtonState();
                }
            });
    }
    
    function savePlaylist() {
        if (!playlistSelect || !savePlaylistBtn) {
            return;
        }
        
        const playlistName = playlistSelect.value;
        if (!playlistName) {
            showMessage('Please select a playlist before saving.', 'warning');
            return;
        }
        
        savePlaylistBtn.disabled = true;
        const originalLabel = savePlaylistBtn.textContent;
        savePlaylistBtn.innerHTML = '<span class="spinner"></span> Saving...';
        
        const formData = new FormData();
        formData.append('playlist_name', playlistName);
        
        debugLog('Saving playlist', playlistName);
        fetch(API_BASE + '/config', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'saved') {
                    debugLog('Playlist saved successfully');
                    currentPlaylist = playlistName;
                    updatePlaylistStatusText(currentPlaylist);
                    // Update dropdown immediately
                    if (playlistSelect) {
                        playlistSelect.value = currentPlaylist;
                    }
                    updateSaveButtonState();
                    showMessage('Configuration saved.', 'success');
                    // Reload status and playlists to ensure sync
                    setTimeout(() => {
                        loadStatus();
                        if (!playlistFetchInProgress) {
                            loadPlaylists();
                        }
                    }, 300);
                } else {
                    throw new Error(data.message || 'Failed to save configuration');
                }
            })
            .catch(error => {
                debugLog('Error saving playlist', error.message);
                showMessage('Error saving configuration: ' + error.message, 'error');
            })
            .finally(() => {
                savePlaylistBtn.disabled = false;
                savePlaylistBtn.textContent = originalLabel;
            });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Copy setup code to clipboard
    window.copySetupCode = function() {
        const codeText = document.getElementById('setup-code-text').textContent;
        if (!codeText) return;
        
        navigator.clipboard.writeText(codeText).then(() => {
            const btn = document.getElementById('copy-btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L6 11L13 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 2000);
        }).catch(err => {
            // Copy failed silently
        });
    };
    
    // Load status
    function loadStatus() {
        // Prevent concurrent updates
        if (isUpdating) {
            return;
        }
        
        isUpdating = true;
        debugLog('Loading status...');
        fetch(API_BASE + '/status')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Status updated', { playing: data.fpp_status?.playing, status: data.fpp_status?.status_name });
                updateStatusDisplay(data);
                // Clear any error messages on successful update
                const messageContainer = document.getElementById('message-container');
                if (messageContainer) {
                    const errorMessages = messageContainer.querySelectorAll('.message.error');
                    errorMessages.forEach(msg => {
                        if (msg.textContent.includes('Error loading status')) {
                            msg.remove();
                        }
                    });
                }
                isUpdating = false;
            })
            .catch(error => {
                debugLog('Error loading status', error.message);
                // Only show error message if it's not already showing
                const messageContainer = document.getElementById('message-container');
                const existingError = messageContainer && 
                    Array.from(messageContainer.querySelectorAll('.message.error'))
                        .some(msg => msg.textContent.includes('Error loading status'));
                if (!existingError) {
                    showMessage('Error loading status: ' + error.message, 'error');
                }
                isUpdating = false;
            });
    }
    
    // Update status display
    function updateStatusDisplay(data) {
        const serviceRunning = data.service_running || false;
        const paired = data.paired || false;
        const fppStatus = data.fpp_status || {};
        const playlist = data.playlist || '';
        
        // Update service status card
        const serviceStatusEl = document.getElementById('service-status');
        const serviceStatusText = serviceRunning ? 'Running' : 'Stopped';
        serviceStatusEl.textContent = serviceStatusText;
        if (serviceRunning) {
            autoStartAttempted = false;
        } else if (!autoStartAttempted) {
            attemptAutoStart();
        }
        
        // Update pairing status card
        const pairingStatusEl = document.getElementById('pairing-status');
        const pairingStatusText = paired ? 'Paired' : 'Not Paired';
        pairingStatusEl.textContent = pairingStatusText;
        
        // Update FPP status
        const playing = fppStatus.playing || false;
        let statusText = fppStatus.status_text || fppStatus.status_name || 'Unknown';
        const statusName = (fppStatus.status_name || 'unknown').toLowerCase();
        const errorDetail = fppStatus.error_detail || '';
        const fppCurrentPlaylist = fppStatus.current_playlist || '';
        const fppCurrentSequence = fppStatus.current_sequence || '';
        
        // Determine FPP status text and dot class
        let fppStatusText = 'Unknown';
        let fppDotClass = 'stopped';
        let fppStatusDetails = '';
        
        if (playing || statusName === 'playing') {
            fppStatusText = 'Playing';
            fppDotClass = 'playing';
            // Add playlist and sequence details
            if (fppCurrentPlaylist) {
                fppStatusDetails += '<div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Playlist: ' + escapeHtml(fppCurrentPlaylist) + '</div>';
            }
            if (fppCurrentSequence) {
                fppStatusDetails += '<div style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">Sequence: ' + escapeHtml(fppCurrentSequence) + '</div>';
            }
        } else if (statusName === 'paused') {
            fppStatusText = 'Paused';
            fppDotClass = 'paused';
        } else if (statusName === 'testing') {
            fppStatusText = 'Testing';
            fppDotClass = 'testing';
        } else if (statusText.includes('Running') && !statusText.includes('Not Running') && !statusText.includes('Unreachable')) {
            fppStatusText = 'Running';
            fppDotClass = 'running';
        } else if (statusText.includes('Available') || (!errorDetail && statusName !== 'unknown' && statusName !== '')) {
            fppStatusText = 'Available';
            fppDotClass = 'running';
            // Show error detail if available
            if (errorDetail) {
                const errorParts = errorDetail.split('. ').filter(part => part.trim());
                if (errorParts.length > 0) {
                    fppStatusDetails += '<div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px; line-height: 1.4;">';
                    errorParts.forEach((part, index) => {
                        if (part.trim()) {
                            const trimmedPart = part.trim();
                            fppStatusDetails += escapeHtml(trimmedPart);
                            if (!trimmedPart.endsWith('.') && !trimmedPart.endsWith(':')) {
                                fppStatusDetails += '.';
                            }
                            if (index < errorParts.length - 1) {
                                fppStatusDetails += '<br>';
                            }
                        }
                    });
                    fppStatusDetails += '</div>';
                }
            }
        } else if (statusText.includes('Not Running') || statusText.includes('Unavailable') || statusText.includes('Unreachable')) {
            fppStatusText = 'Unavailable';
            fppDotClass = 'stopped';
            if (errorDetail) {
                const errorParts = errorDetail.split('. ').filter(part => part.trim());
                if (errorParts.length > 0) {
                    fppStatusDetails += '<div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px; line-height: 1.4;">';
                    errorParts.forEach((part, index) => {
                        if (part.trim()) {
                            const trimmedPart = part.trim();
                            fppStatusDetails += escapeHtml(trimmedPart);
                            if (!trimmedPart.endsWith('.') && !trimmedPart.endsWith(':')) {
                                fppStatusDetails += '.';
                            }
                            if (index < errorParts.length - 1) {
                                fppStatusDetails += '<br>';
                            }
                        }
                    });
                    fppStatusDetails += '</div>';
                }
            }
        } else {
            fppStatusText = statusText || 'Unknown';
            fppDotClass = 'stopped';
        }
        
        // Update FPP status card
        const fppStatusTextEl = document.getElementById('fpp-status-text');
        const fppStatusDotEl = document.getElementById('fpp-status-dot');
        const fppStatusCard = document.getElementById('fpp-status-card');
        if (fppStatusTextEl) {
            fppStatusTextEl.textContent = fppStatusText;
        }
        if (fppStatusDotEl) {
            fppStatusDotEl.className = 'status-dot-large ' + fppDotClass;
        }
        // Add details below the status value if available
        if (fppStatusCard && fppStatusDetails) {
            let detailsEl = fppStatusCard.querySelector('.status-card-details');
            if (!detailsEl) {
                detailsEl = document.createElement('div');
                detailsEl.className = 'status-card-details';
                fppStatusCard.appendChild(detailsEl);
            }
            detailsEl.innerHTML = fppStatusDetails;
        } else if (fppStatusCard) {
            // Remove details if not needed
            const detailsEl = fppStatusCard.querySelector('.status-card-details');
            if (detailsEl) {
                detailsEl.remove();
            }
        }
        
        // Update playlist
        const newPlaylist = playlist || '';
        if (newPlaylist !== currentPlaylist) {
            currentPlaylist = newPlaylist;
            updatePlaylistStatusText(currentPlaylist);
            // Update dropdown if it's loaded and the value changed
            if (playlistSelect && playlistsLoaded) {
                playlistSelect.value = currentPlaylist;
            }
        } else if (playlistSelect && playlistsLoaded && playlistSelect.value !== currentPlaylist) {
            // Ensure dropdown matches currentPlaylist even if playlist didn't change
            playlistSelect.value = currentPlaylist;
        }
        updateSaveButtonState();
        
        // Show/hide pairing sections
        const pairingSection = document.getElementById('pairing-section');
        const pairedSection = document.getElementById('paired-section');
        
        if (paired) {
            pairingSection.style.display = 'none';
            pairedSection.style.display = 'block';
            qrLoaded = false;
        } else {
            pairingSection.style.display = 'block';
            pairedSection.style.display = 'none';
            
            if (serviceRunning) {
                if (!qrLoaded) {
                    loadQRCode();
                }
            } else {
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-content').style.display = 'none';
                document.getElementById('qr-error').style.display = 'block';
                qrLoaded = false;
            }
        }
    }
    
    function attemptAutoStart() {
        if (autoStartAttempted) {
            return;
        }
        autoStartAttempted = true;
        showMessage('Starting HomeKit service...', 'info');
        
        fetch(API_BASE + '/restart', { method: 'POST' })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.started || data.status === 'restarted') {
                    showMessage('HomeKit service started.', 'success');
                } else {
                    showMessage(data.message || 'Starting HomeKit service...', 'info');
                }
                // Give the service a moment before polling
                setTimeout(() => loadStatus(), 1200);
            })
            .catch(error => {
                showMessage('Unable to start service automatically: ' + error.message, 'error');
            });
    }
    
    // Load QR code
    function loadQRCode() {
        document.getElementById('qr-loading').style.display = 'block';
        document.getElementById('qr-content').style.display = 'none';
        document.getElementById('qr-error').style.display = 'none';
        
        fetch(API_BASE + '/pairing-info')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                const setupCode = data.setup_code || '123-45-678';
                
                document.getElementById('setup-code-text').textContent = setupCode;
                
                // Load QR code image
                const qrImage = document.getElementById('qr-image');
                qrImage.src = API_BASE + '/qr-code?' + new Date().getTime();
                qrImage.onload = () => {
                    document.getElementById('qr-loading').style.display = 'none';
                    document.getElementById('qr-content').style.display = 'block';
                    qrLoaded = true;
                };
                qrImage.onerror = () => {
                    document.getElementById('qr-loading').style.display = 'none';
                    document.getElementById('qr-error').style.display = 'block';
                    qrLoaded = false;
                };
            })
            .catch(error => {
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-error').style.display = 'block';
                qrLoaded = false;
            });
    }
    
    // Restart service
    window.restartService = function() {
        if (!confirm('Are you sure you want to restart the HomeKit service?')) {
            return;
        }
        
        debugLog('Restarting service...');
        const btn = document.getElementById('restart-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Restarting...';
        
        fetch(API_BASE + '/restart', {
            method: 'POST'
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200));
                });
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            debugLog('Service restart response', data);
            if (data && data.started) {
                showMessage('Service restarted successfully', 'success');
            } else {
                showMessage(data && data.message ? data.message : 'Service restart initiated. Please wait a few seconds...', 'info');
            }
            // Refresh status after a short delay
            setTimeout(() => {
                loadStatus();
            }, 2000);
            // Refresh again after longer delay to ensure status is updated
            setTimeout(() => {
                loadStatus();
                btn.disabled = false;
                btn.textContent = 'Restart Service';
            }, 5000);
        })
        .catch(error => {
            debugLog('Error restarting service', error.message);
            showMessage('Error restarting service: ' + error.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Restart Service';
        });
    };
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function loadMQTTConfig() {
        const mqttBrokerInput = document.getElementById('mqtt-broker');
        const mqttPortInput = document.getElementById('mqtt-port');
        
        if (!mqttBrokerInput || !mqttPortInput) {
            return;
        }
        
        fetch(API_BASE + '/config')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.mqtt) {
                    if (data.mqtt.broker && data.mqtt.broker !== 'localhost') {
                        mqttBrokerInput.value = data.mqtt.broker;
                    } else if (data.mqtt.broker === 'localhost') {
                        // Keep localhost but add a note
                        mqttBrokerInput.value = data.mqtt.broker;
                        mqttBrokerInput.placeholder = 'localhost (check FPP MQTT settings)';
                    }
                    if (data.mqtt.port) {
                        mqttPortInput.value = data.mqtt.port;
                    }
                } else {
                    // No MQTT config found - show placeholder hint
                    mqttBrokerInput.placeholder = 'Enter broker IP/hostname';
                }
            })
            .catch(error => {
                // MQTT config load failed silently
            });
    }
    
    function saveMQTTConfig() {
        const mqttBrokerInput = document.getElementById('mqtt-broker');
        const mqttPortInput = document.getElementById('mqtt-port');
        const saveMqttBtn = document.getElementById('save-mqtt-btn');
        
        if (!mqttBrokerInput || !mqttPortInput || !saveMqttBtn) {
            return;
        }
        
        const broker = mqttBrokerInput.value.trim();
        const port = parseInt(mqttPortInput.value);
        
        if (!port || port < 1 || port > 65535) {
            showMessage('Please enter a valid port number (1-65535).', 'warning');
            return;
        }
        
        saveMqttBtn.disabled = true;
        const originalLabel = saveMqttBtn.textContent;
        saveMqttBtn.innerHTML = '<span class="spinner"></span> Saving...';
        
        const formData = new FormData();
        if (broker) {
            formData.append('mqtt_broker', broker);
        }
        formData.append('mqtt_port', port);
        
        fetch(API_BASE + '/config', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200));
                    });
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                if (data && data.status === 'saved') {
                    showMessage('MQTT configuration saved. Restart service to apply changes.', 'success');
                    setTimeout(() => loadStatus(), 300);
                } else {
                    throw new Error(data && data.message ? data.message : 'Failed to save configuration');
                }
            })
            .catch(error => {
                showMessage('Error saving MQTT configuration: ' + error.message, 'error');
            })
            .finally(() => {
                saveMqttBtn.disabled = false;
                saveMqttBtn.textContent = originalLabel;
            });
    }
    
    function testMQTT() {
        const testMqttBtn = document.getElementById('test-mqtt-btn');
        const mqttBrokerInput = document.getElementById('mqtt-broker');
        const mqttPortInput = document.getElementById('mqtt-port');
        
        if (!testMqttBtn) {
            return;
        }
        
        // Get current values from UI
        const broker = mqttBrokerInput ? mqttBrokerInput.value.trim() : '';
        const port = mqttPortInput ? parseInt(mqttPortInput.value) : 1883;
        
        if (!broker) {
            showMessage('Please enter a broker hostname or IP address.', 'warning');
            return;
        }
        
        if (!port || port < 1 || port > 65535) {
            showMessage('Please enter a valid port number (1-65535).', 'warning');
            return;
        }
        
        testMqttBtn.disabled = true;
        const originalLabel = testMqttBtn.textContent;
        testMqttBtn.innerHTML = '<span class="spinner"></span> Testing...';
        
        // Send broker and port from UI to test
        const formData = new FormData();
        formData.append('mqtt_broker', broker);
        formData.append('mqtt_port', port);
        
        fetch(API_BASE + '/test-mqtt', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showMessage('✓ ' + (data.message || 'MQTT connection successful!'), 'success');
                } else {
                    let errorMsg = data.error || 'Unknown error';
                    // Add helpful guidance for common errors
                    if (errorMsg.includes('Connection refused') && errorMsg.includes('localhost')) {
                        errorMsg += '. The MQTT broker (mosquitto) is not running or not listening on this port. On FPP, run: sudo systemctl status mosquitto to check, or sudo systemctl start mosquitto to start it.';
                    } else if (errorMsg.includes('Connection refused')) {
                        errorMsg += '. Check that: 1) MQTT broker is running, 2) Broker IP/hostname is correct, 3) Port matches your broker configuration, 4) Firewall allows connections.';
                    }
                    showMessage('✗ MQTT test failed: ' + errorMsg, 'error');
                }
            })
            .catch(error => {
                showMessage('Error testing MQTT: ' + error.message, 'error');
            })
            .finally(() => {
                testMqttBtn.disabled = false;
                testMqttBtn.textContent = originalLabel;
            });
    }
    
    function loadHomekitNetworkConfig() {
        const homekitIpSelect = document.getElementById('homekit-ip');
        if (!homekitIpSelect) return;
        
        // Get available network interfaces from API
        fetch('/api/plugin/fpp-Homekit/network-interfaces')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON: ' + e.message);
                }
                
                // Clear all options
                homekitIpSelect.innerHTML = '';
                
                // Add default options
                homekitIpSelect.add(new Option('Auto-detect (Primary Interface)', ''));
                homekitIpSelect.add(new Option('All Interfaces (0.0.0.0)', '0.0.0.0'));
                
                // Safely check for interfaces
                const interfaces = data && data.interfaces ? data.interfaces : [];
                
                // Add separator if we have interfaces
                if (interfaces.length > 0) {
                    try {
                        const separator = new Option('──────────────────────', '', true, false);
                        separator.disabled = true;
                        homekitIpSelect.add(separator);
                    } catch (e) {
                        // Separator failed silently
                    }
                }
                
                // Add each detected interface as a separate option
                if (interfaces.length > 0) {
                    interfaces.forEach((iface, index) => {
                        try {
                            if (iface && iface.name && iface.ip) {
                                const label = iface.name + ' - ' + iface.ip;
                                const option = new Option(label, iface.ip);
                                homekitIpSelect.add(option);
                            }
                        } catch (e) {
                            // Interface option failed silently
                        }
                    });
                }
                
                // Set current value (empty string or saved IP)
                const currentIp = data && data.current_ip ? data.current_ip : '';
                try {
                    homekitIpSelect.value = currentIp;
                } catch (e) {
                    // IP value set failed silently
                }
            })
            .catch(error => {
                // Still add basic options so user can select something
                homekitIpSelect.innerHTML = '';
                homekitIpSelect.add(new Option('Auto-detect (Primary Interface)', ''));
                homekitIpSelect.add(new Option('All Interfaces (0.0.0.0)', '0.0.0.0'));
                
                showMessage('Could not load network interfaces. Using basic options.', 'warning');
            });
    }
    
    function saveHomekitNetwork() {
        const homekitIpSelect = document.getElementById('homekit-ip');
        const saveBtn = document.getElementById('save-homekit-network-btn');
        
        if (!homekitIpSelect || !saveBtn) {
            return;
        }
        
        const originalLabel = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        const homekitIp = homekitIpSelect.value || '';
        
        const formData = new FormData();
        formData.append('homekit_ip', homekitIp);
        
        fetch('/api/plugin/fpp-Homekit/config', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                return response.text();
            })
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
                
                // Check for success indicators: 'success', 'status === saved', or 'status === OK'
                if (data.success || data.status === 'saved' || data.status === 'OK') {
                    showMessage('✓ HomeKit network config saved. Restarting service...', 'success');
                    // Restart the service to apply changes
                    setTimeout(() => {
                        restartService();
                    }, 1000);
                } else {
                    const errorMsg = data.error || data.message || 'Unknown error';
                    showMessage('Error saving HomeKit network config: ' + errorMsg, 'error');
                }
            })
            .catch(error => {
                showMessage('Error saving HomeKit network config: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
            });
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        if (savePlaylistBtn) {
            savePlaylistBtn.addEventListener('click', savePlaylist);
            savePlaylistBtn.disabled = true;
        }
        if (playlistSelect) {
            playlistSelect.addEventListener('change', updateSaveButtonState);
        }
        
        const saveMqttBtn = document.getElementById('save-mqtt-btn');
        if (saveMqttBtn) {
            saveMqttBtn.addEventListener('click', saveMQTTConfig);
        }
        
        const testMqttBtn = document.getElementById('test-mqtt-btn');
        if (testMqttBtn) {
            testMqttBtn.addEventListener('click', testMQTT);
        }
        
        const saveHomekitNetworkBtn = document.getElementById('save-homekit-network-btn');
        if (saveHomekitNetworkBtn) {
            saveHomekitNetworkBtn.addEventListener('click', saveHomekitNetwork);
        }
        
        loadPlaylists(true);
        loadHomekitNetworkConfig();
        loadStatus();
        loadMQTTConfig();
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(function() {
            loadStatus();
            if (!playlistFetchInProgress) {
                loadPlaylists();
            }
        }, 10000);
        
        document.addEventListener('click', function(evt) {
            if (evt.target.closest('button')) {
                setTimeout(() => loadStatus(), 500);
            }
        });
        
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                loadStatus();
                if (!playlistFetchInProgress) {
                    loadPlaylists();
                }
            }
        });
    });
    
    // Clean up interval on page unload
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    });
})();
</script>
