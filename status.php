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
        <h2>HomeKit Status</h2>
        
        <div id="message-container"></div>
        
        <div id="status-content">
            <div class="status-row">
                <span class="status-label">HomeKit Service</span>
                <span class="status-value" id="service-status">
                    <span class="status-indicator stopped"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">Pairing Status</span>
                <span class="status-value" id="pairing-status">
                    <span class="status-indicator not-paired"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">FPP Status</span>
                <span class="status-value" id="fpp-status">
                    <span class="status-indicator stopped"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">Configured Playlist</span>
                <span class="status-value" id="playlist-status">
                    Loading...
                </span>
            </div>
            
            <div class="playlist-config">
                <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">Playlist Configuration</h3>
                <div class="playlist-config-controls">
                    <select class="form-select" id="playlist-select" aria-label="Select playlist to start">
                        <option value="">-- Loading playlists... --</option>
                    </select>
                    <button class="homekit-button" type="button" id="save-playlist-btn">Save Playlist</button>
                    <button class="homekit-button secondary" type="button" id="refresh-playlists-btn">Refresh</button>
                </div>
            </div>
            
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;">
                <button class="homekit-button" onclick="restartService()" id="restart-btn">Restart Service</button>
                <button class="homekit-button secondary" onclick="refreshStatus()" id="refresh-btn">Refresh</button>
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
    let currentSetupCode = '';
    let currentSetupId = '';
    let autoStartAttempted = false;
    
    const playlistSelect = document.getElementById('playlist-select');
    const savePlaylistBtn = document.getElementById('save-playlist-btn');
    const refreshPlaylistsBtn = document.getElementById('refresh-playlists-btn');
    
    // Debug logging
    function debugLog(message, data = null) {
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
            toggleIcon.classList.add('open');
        } else {
            debugMessages.style.display = 'none';
            debugMessages.classList.remove('open');
            toggleIcon.style.transform = 'rotate(-90deg)';
            toggleIcon.classList.remove('open');
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
            playlistStatusEl.innerHTML = '<span style="color: var(--success-color);">' + escapeHtml(name) + '</span>';
        } else {
            playlistStatusEl.innerHTML = '<span style="color: var(--warning-color);">Not configured</span>';
        }
    }
    
    function setPlaylistLoadingState(isLoading) {
        if (playlistSelect) {
            playlistSelect.disabled = isLoading;
        }
        if (refreshPlaylistsBtn) {
            refreshPlaylistsBtn.disabled = isLoading;
            refreshPlaylistsBtn.innerHTML = isLoading ? '<span class="spinner"></span> Refreshing...' : 'Refresh';
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
    
    function loadPlaylists(forceMessage = false) {
        if (!playlistSelect) {
            return;
        }
        
        setPlaylistLoadingState(true);
        debugLog('Loading playlists...');
        
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
                    if (forceMessage) {
                        showMessage('No playlists found. Create playlists in FPP first.', 'warning');
                    }
                }
                
                playlistSelect.value = currentPlaylist || '';
                updateSaveButtonState();
                debugLog('Playlists loaded', data);
            })
            .catch(error => {
                debugLog('Error loading playlists', { error: error.message });
                showMessage('Error loading playlists: ' + error.message, 'error');
            })
            .finally(() => {
                setPlaylistLoadingState(false);
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
        
        debugLog('Saving playlist configuration', { playlist: playlistName });
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
                debugLog('Save response', data);
                if (data.status === 'saved') {
                    currentPlaylist = playlistName;
                    updatePlaylistStatusText(currentPlaylist);
                    showMessage('Configuration saved.', 'success');
                    setTimeout(() => loadStatus(), 300);
                } else {
                    throw new Error(data.message || 'Failed to save configuration');
                }
            })
            .catch(error => {
                debugLog('Error saving playlist', { error: error.message });
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
            debugLog('Failed to copy setup code', { error: err.message });
        });
    };
    
    // Load status
    function loadStatus() {
        // Prevent concurrent updates
        if (isUpdating) {
            debugLog('Update already in progress, skipping...');
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
                debugLog('Status response', data);
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
                debugLog('Error loading status', { error: error.message });
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
        
        // Update service status
        const serviceStatusEl = document.getElementById('service-status');
        serviceStatusEl.innerHTML = '<span class="status-indicator ' + (serviceRunning ? 'running' : 'stopped') + '"></span>' + 
            (serviceRunning ? 'Running' : 'Stopped');
        if (serviceRunning) {
            autoStartAttempted = false;
        } else if (!autoStartAttempted) {
            attemptAutoStart();
        }
        
        // Update pairing status
        const pairingStatusEl = document.getElementById('pairing-status');
        pairingStatusEl.innerHTML = '<span class="status-indicator ' + (paired ? 'paired' : 'not-paired') + '"></span>' + 
            (paired ? 'Paired' : 'Not Paired');
        
        // Update FPP status
        const playing = fppStatus.playing || false;
        let statusText = fppStatus.status_text || fppStatus.status_name || 'Unknown';
        const statusName = (fppStatus.status_name || 'unknown').toLowerCase();
        const errorDetail = fppStatus.error_detail || '';
        const fppCurrentPlaylist = fppStatus.current_playlist || '';
        const fppCurrentSequence = fppStatus.current_sequence || '';
        
        // Determine status indicator class based on status
        let statusClass = 'stopped';
        if (playing || statusName === 'playing') {
            statusClass = 'playing';
        } else if (statusName === 'paused') {
            statusClass = 'paused';
        } else if (statusName === 'testing') {
            statusClass = 'testing';
        } else if (statusText.includes('Not Running') || statusText.includes('Unavailable') || statusText.includes('Unreachable')) {
            statusClass = 'stopped';
        }
        
        // Build status display with better formatting
        let statusHtml = '<span class="status-indicator ' + statusClass + '"></span><div style="flex: 1; min-width: 0;">';
        
        if (playing) {
            statusHtml += '<strong>Playing</strong>';
            if (fppCurrentPlaylist) {
                statusHtml += '<div style="color: var(--text-secondary); font-size: 13px; margin-top: 4px;">Playlist: ' + escapeHtml(fppCurrentPlaylist) + '</div>';
            }
            if (fppCurrentSequence) {
                statusHtml += '<div style="color: var(--text-secondary); font-size: 13px; margin-top: 2px;">Sequence: ' + escapeHtml(fppCurrentSequence) + '</div>';
            }
        } else {
            statusHtml += '<div>' + escapeHtml(statusText) + '</div>';
            if (errorDetail) {
                // Format error detail with better styling - split into readable lines
                const errorParts = errorDetail.split('. ').filter(part => part.trim());
                statusHtml += '<div style="color: var(--text-secondary); font-size: 12px; margin-top: 6px; line-height: 1.5; text-align: left;">';
                errorParts.forEach((part, index) => {
                    if (part.trim()) {
                        const trimmedPart = part.trim();
                        statusHtml += escapeHtml(trimmedPart);
                        if (!trimmedPart.endsWith('.') && !trimmedPart.endsWith(':')) {
                            statusHtml += '.';
                        }
                        if (index < errorParts.length - 1) {
                            statusHtml += '<br>';
                        }
                    }
                });
                statusHtml += '</div>';
            }
        }
        statusHtml += '</div>';
        
        const fppStatusEl = document.getElementById('fpp-status');
        fppStatusEl.innerHTML = statusHtml;
        
        // Update playlist
        currentPlaylist = playlist || '';
        updatePlaylistStatusText(currentPlaylist);
        if (playlistSelect && playlistsLoaded) {
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
        debugLog('Attempting to auto-start HomeKit service...');
        showMessage('Starting HomeKit service...', 'info');
        
        fetch(API_BASE + '/restart', { method: 'POST' })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Auto-start response', data);
                if (data.started || data.status === 'restarted') {
                    showMessage('HomeKit service started.', 'success');
                } else {
                    showMessage(data.message || 'Starting HomeKit service...', 'info');
                }
                // Give the service a moment before polling
                setTimeout(() => loadStatus(), 1200);
            })
            .catch(error => {
                debugLog('Auto-start failed', { error: error.message });
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
                debugLog('Pairing info response', data);
                const setupCode = data.setup_code || '123-45-678';
                const setupId = data.setup_id || 'HOME';
                
                currentSetupCode = setupCode;
                currentSetupId = setupId;
                
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
                debugLog('Error loading pairing info', { error: error.message });
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
        
        const btn = document.getElementById('restart-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Restarting...';
        
        debugLog('Restarting service...');
        fetch(API_BASE + '/restart', {
            method: 'POST'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            debugLog('Restart response', data);
            if (data.started) {
                showMessage('Service restarted successfully', 'success');
            } else {
                showMessage(data.message || 'Service restart initiated. Please wait a few seconds...', 'info');
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
            debugLog('Error restarting service', { error: error.message });
            showMessage('Error restarting service: ' + error.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Restart Service';
        });
    };
    
    // Refresh status
    window.refreshStatus = function() {
        const btn = document.getElementById('refresh-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Refreshing...';
        loadStatus();
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = 'Refresh';
        }, 1000);
    };
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        if (refreshPlaylistsBtn) {
            refreshPlaylistsBtn.addEventListener('click', () => loadPlaylists(true));
        }
        if (savePlaylistBtn) {
            savePlaylistBtn.addEventListener('click', savePlaylist);
            savePlaylistBtn.disabled = true;
        }
        if (playlistSelect) {
            playlistSelect.addEventListener('change', updateSaveButtonState);
        }
        
        loadPlaylists(true);
        loadStatus();
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(function() {
            loadStatus();
        }, 30000);
        
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                loadStatus();
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
