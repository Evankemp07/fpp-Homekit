<?php
$pluginDir = dirname(__FILE__);
$plugin = basename($pluginDir);
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">FPP Homekit</h2>
            <div style="display: flex; gap: 8px;">
                <button class="settings-button" onclick="showMqttSettings()" id="settings-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg>
                </button>
                <button class="info-button" onclick="showMqttInfo()">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <div id="message-container"></div>
        
        <div id="status-content">
            <div class="status-cards-container">
                <div class="status-card">
                    <div class="status-card-label">HomeKit Service</div>
                    <div class="status-card-value" id="service-status" style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span id="service-status-text">Loading...</span>
                            <button class="restart-icon-btn" onclick="restartService()" id="restart-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                                    <path d="M160-160v-80h110l-16-14q-52-46-73-105t-21-119q0-111 66.5-197.5T400-790v84q-72 26-116 88.5T240-478q0 45 17 87.5t53 78.5l10 10v-98h80v240H160Zm400-10v-84q72-26 116-88.5T720-482q0-45-17-87.5T650-648l-10-10v98h-80v-240h240v80H690l16 14q49 49 71.5 106.5T800-482q0 111-66.5 197.5T560-170Z"/>
                                </svg>
                            </button>
                        </div>
                        <span class="status-dot-large" id="service-status-dot"></span>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-card-label">Pairing Status</div>
                    <div class="status-card-value" id="pairing-status" style="display: flex; align-items: center; justify-content: space-between;">
                        <span id="pairing-status-text">Loading...</span>
                        <span class="status-dot-large" id="pairing-status-dot"></span>
                    </div>
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

                <div class="status-card" id="emulation-section" style="display: none;">
                    <div class="status-card-label">HomeKit Emulation</div>
                    <div class="status-card-value" style="display: flex; gap: 8px;">
                        <button class="homekit-button" style="font-size: 14px; padding: 8px 16px; background: #34c759;" onclick="emulateHomeKit(true)" id="emulate-on-btn">
                            Emulate ON
                        </button>
                        <button class="homekit-button secondary" style="font-size: 14px; padding: 8px 16px;" onclick="emulateHomeKit(false)" id="emulate-off-btn">
                            Emulate OFF
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="config-layout">
                <div class="config-left">
                    <div class="playlist-config">
                        <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">Playlist Configuration</h3>
                        <div class="playlist-config-controls">
                            <select class="form-select" id="playlist-select" aria-label="Select playlist to start">
                                <option value="">-- Loading playlists... --</option>
                            </select>
                            <button class="homekit-button" type="button" id="save-playlist-btn">Save Playlist</button>
                        </div>
                    </div>
                    
                    <div class="playlist-config" style="margin-top: 24px;" id="mqtt-config-section">
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
                            <select class="form-select" id="homekit-ip" aria-label="Select network interface">
                                <option value="">Auto-detect (Primary Interface)</option>
                                <option value="0.0.0.0">All Interfaces (0.0.0.0)</option>
                            </select>
                            <button class="homekit-button" type="button" id="save-homekit-network-btn">Save & Restart</button>
                        </div>
                        <div style="margin-top: 8px; font-size: 12px; color: var(--text-secondary);">
                            Select which network interface HomeKit should listen on. Choose a different interface if you get "not reachable" errors.
                        </div>
                    </div>
                </div>
                
                <div class="config-right">
                    <div class="status-card pairing-card">
                        <div class="status-card-label">Pair with HomeKit</div>
                        <div id="pairing-section">
                            <div class="qr-container">
                                <div id="qr-loading" style="display: block;">
                                    <div class="qr-code-box">
                                        <div class="qr-placeholder">
                                            <div class="qr-placeholder-grid"></div>
                                        </div>
                                        <div class="setup-code-placeholder">
                                            <div class="setup-code-label">SETUP CODE</div>
                                            <div class="setup-code-value">
                                                <span>XXXX-XXXX</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="qr-content" style="display: none;">
                                    <div class="qr-code-box">
                                        <div class="qr-code">
                                            <img id="qr-image" alt="HomeKit QR Code" />
                                        </div>
                                        <div class="setup-code">
                                            <span class="setup-code-label">Setup Code</span>
                                            <div class="setup-code-value">
                                                <span id="setup-code-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="qr-error" style="display: none;">
                                    <p style="color: var(--error-color);">Service is not running. Please start the service to generate QR code.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div id="paired-section" style="display: none;">
                            <p class="success-message"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#4caf50" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>Successfully paired with HomeKit</p>
                            <p class="info-text">You can now control FPP from the Home app on your iOS devices.</p>
                            <p class="info-text" id="last-command-text" style="display: none;">Last command: <span id="last-command-time">none</span></p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <div class="info-debug-layout" style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <div class="info-section">
                <h3>Information</h3>
                <p class="info-text"><strong>Accessory Name:</strong> FPP-Controller</p>
                <p class="info-text"><strong>Accessory Type:</strong> Light</p>
                <p class="info-text"><strong>Control:</strong> Turning the light ON will start the configured playlist. Turning it OFF will stop playback.</p>
            </div>
            
            <div class="debug-section">
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
</div>

<!-- MQTT Info Modal -->
<div id="mqtt-info-modal" class="info-modal" style="display: none;">
    <div class="info-modal-overlay" onclick="hideMqttInfo()"></div>
    <div class="info-modal-content">
        <div class="info-modal-header">
            <h3>MQTT Setup Instructions</h3>
            <button class="info-modal-close" onclick="hideMqttInfo()" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
                </svg>
            </button>
        </div>
        <div class="info-modal-body">
            <p><strong>To configure MQTT settings in FPP:</strong></p>
            <ol>
                <li>Enable <strong>Developer Mode</strong> in FPP settings to see MQTT configuration options</li>
                <li>In MQTT settings, add <strong>'#'</strong> to the subscriptions list</li>
                <li>Set the value to <strong>1</strong> for updates on everything</li>
                <li>Use <strong>localhost</strong> as the hostname</li>
                <li>If you changed the MQTT port from the default (1883), you can adjust it in this plugin's MQTT Configuration section</li>
            </ol>
        </div>
    </div>
</div>

<!-- MQTT Settings Modal -->
<div id="mqtt-settings-modal" class="info-modal" style="display: none;">
    <div class="info-modal-overlay" onclick="hideMqttSettings()"></div>
    <div class="info-modal-content">
        <div class="info-modal-header">
            <h3>MQTT Configuration Settings</h3>
            <button class="info-modal-close" onclick="hideMqttSettings(); event.stopPropagation();" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                    <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
                </svg>
            </button>
        </div>
        <div class="info-modal-body">
            <p><strong>Section Visibility Settings</strong></p>

            <div class="settings-toggle-container">
                <div class="settings-toggle-row">
                    <label for="emulation-visibility-toggle" class="settings-toggle-label">
                        Show HomeKit Emulation Section
                        <span id="emulation-visibility-text" class="settings-toggle-description">(currently hidden)</span>
                    </label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="emulation-visibility-toggle" onchange="toggleEmulationVisibility(this)">
                        <label for="emulation-visibility-toggle" class="toggle-slider"></label>
                    </div>
                </div>
                <p class="settings-description">
                    Control whether the HomeKit emulation buttons are visible on the main page.
                    Useful for testing playlist control without needing actual HomeKit devices.
                </p>
            </div>

            <div class="settings-toggle-container">
                <div class="settings-toggle-row">
                    <label for="mqtt-visibility-toggle" class="settings-toggle-label">
                        Show MQTT Configuration Section
                        <span id="mqtt-visibility-text" class="settings-toggle-description">(currently hidden)</span>
                    </label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="mqtt-visibility-toggle" onchange="toggleMqttConfigVisibility(this)">
                        <label for="mqtt-visibility-toggle" class="toggle-slider"></label>
                    </div>
                </div>
                <p class="settings-description">
                    Control whether the MQTT configuration section is visible on the main page.
                    Advanced users can show it to modify broker settings, while basic users can keep it hidden for a cleaner interface.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const API_BASE = '/api/plugin/<?php echo $plugin; ?>';
    let refreshInterval = null;
    let eventSource = null;
    let isUpdating = false;
    let playlistsLoaded = false;
    let currentPlaylist = '';
    let qrLoaded = false;
    let autoStartAttempted = false;
    let isServiceRestarting = false;
    let playlistFetchInProgress = false;
    let lastStatusUpdate = 0;
    let restartStartTime = 0;
    let restartPollInterval = null;
    let fppStatusLoading = false;
    let fppStatusLoadStartTime = 0;
    
    const playlistSelect = document.getElementById('playlist-select');
    const savePlaylistBtn = document.getElementById('save-playlist-btn');

    // Initialize Server-Sent Events for real-time updates
    function initializeEventSource() {
        if (eventSource) {
            eventSource.close();
        }

        eventSource = new EventSource(API_BASE + '/events');

        eventSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                handleRealtimeUpdate(data);
            } catch (e) {
                debugLog('Error parsing SSE data', e.message);
            }
        };

        eventSource.onopen = function() {
            debugLog('Real-time connection established');
            // SSE is active, disable any polling
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        };

        eventSource.onerror = function(event) {
            debugLog('SSE connection error', event);
            // Close and try to reconnect after a delay
            eventSource.close();
            setTimeout(function() {
                if (!refreshInterval) {
                    debugLog('SSE failed, falling back to polling mode');
                    refreshInterval = setInterval(function() {
                        loadStatus();
                        if (!playlistFetchInProgress) {
                            loadPlaylists();
                        }
                    }, 5000); // Slower polling as fallback
                }
            }, 2000);
        };
    }

    // Handle real-time updates from server
    function handleRealtimeUpdate(data) {
        debugLog('Real-time update received', data.type);

        switch (data.type) {
            case 'status_update':
                if (data.data) {
                    // SSE now sends full status on initial connection
                    updateStatusDisplay(data.data);
                }
                break;

            case 'command_update':
                if (data.data) {
                    // Update last command display - only if command is recent (within last hour)
                    const now = Math.floor(Date.now() / 1000);
                    const commandAge = now - data.data.timestamp;
                    const oneHourAgo = 3600; // 1 hour in seconds
                    
                    const timeElement = document.getElementById('last-command-time');
                    const textElement = document.getElementById('last-command-text');

                    if (commandAge <= oneHourAgo && timeElement && textElement) {
                        const date = new Date(data.data.timestamp * 1000);
                        const timeString = date.toLocaleTimeString();
                        const sourceText = data.data.source === 'homekit' ? 'HomeKit' : 'Emulate';
                        timeElement.textContent = `${data.data.action} (${sourceText}) at ${timeString}`;
                        textElement.style.display = 'block';
                    } else if (textElement) {
                        // Hide if command is too old
                        textElement.style.display = 'none';
                    }
                }
                break;

            case 'connected':
                debugLog('Real-time connection confirmed');
                // SSE already sent initial status, only load playlists if needed
                if (!playlistFetchInProgress) {
                    loadPlaylists();
                }
                // Ensure QR code loads if service is running (fallback if status_update didn't trigger it)
                setTimeout(function() {
                    if (!qrLoaded) {
                        loadStatus();
                    }
                }, 500);
                break;

            case 'heartbeat':
                // Connection is alive, no action needed
                break;

            default:
                debugLog('Unknown update type', data.type);
        }
    }

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
    
    // Show MQTT info modal
    window.showMqttInfo = function() {
        const modal = document.getElementById('mqtt-info-modal');
        if (modal) {
            modal.style.display = 'flex';
            // Animate in
            requestAnimationFrame(() => {
                const content = modal.querySelector('.info-modal-content');
                if (content) {
                    content.style.opacity = '0';
                    content.style.transform = 'scale(0.95)';
                    requestAnimationFrame(() => {
                        content.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                        content.style.opacity = '1';
                        content.style.transform = 'scale(1)';
                    });
                }
            });
        }
    };
    
    // Hide MQTT info modal
    window.hideMqttInfo = function() {
        const modal = document.getElementById('mqtt-info-modal');
        if (modal) {
            const content = modal.querySelector('.info-modal-content');
            if (content) {
                content.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                content.style.opacity = '0';
                content.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            } else {
                modal.style.display = 'none';
            }
        }
    };
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideMqttInfo();
        }
    });
    
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
        
        // Clear existing messages
        container.innerHTML = '';
        container.appendChild(msgDiv);
        
        // Start hidden, positioned above viewport
        msgDiv.style.opacity = '0';
        msgDiv.style.transform = 'translateY(-100%)';
        msgDiv.style.transition = 'none';
        
        // Animate in from top
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                msgDiv.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                msgDiv.style.opacity = '1';
                msgDiv.style.transform = 'translateY(0)';
            });
        });
        
        // Auto-hide success messages after 5 seconds with slide-out animation
        if (type === 'success') {
            setTimeout(() => {
                msgDiv.style.opacity = '0';
                msgDiv.style.transform = 'translateY(-100%)';
                setTimeout(() => {
                    if (msgDiv.parentNode) {
                        msgDiv.remove();
                    }
                }, 300);
            }, 5000);
        }
    }
    
    function updatePlaylistStatusText(name) {
        const playlistStatusEl = document.getElementById('playlist-status');
        if (!playlistStatusEl) {
            return;
        }
        if (name) {
            const playlistIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#e3e3e3" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M640-160q-50 0-85-35t-35-85q0-50 35-85t85-35q11 0 21 1.5t19 6.5v-328h200v80H760v360q0 50-35 85t-85 35ZM120-320v-80h320v80H120Zm0-160v-80h480v80H120Zm0-160v-80h480v80H120Z"/></svg>';
            playlistStatusEl.innerHTML = playlistIcon + escapeHtml(name);
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
                    // Reload playlists to ensure sync (status will update via SSE)
                    setTimeout(() => {
                        if (!playlistFetchInProgress) {
                            loadPlaylists();
                        }
                        // Only refresh status if SSE is not active
                        if (!eventSource || eventSource.readyState !== EventSource.OPEN) {
                            loadStatus();
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
    

    // Show MQTT settings modal
    window.showMqttSettings = function() {
        const modal = document.getElementById('mqtt-settings-modal');
        if (modal) {
            modal.style.display = 'flex';
            // Animate in
            requestAnimationFrame(() => {
                const content = modal.querySelector('.info-modal-content');
                if (content) {
                    content.style.opacity = '0';
                    content.style.transform = 'scale(0.95)';
                    requestAnimationFrame(() => {
                        content.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                        content.style.opacity = '1';
                        content.style.transform = 'scale(1)';
                    });
                }
            });
        }
    };

    // Hide MQTT settings modal
    window.hideMqttSettings = function() {
        console.log('hideMqttSettings called');
        const modal = document.getElementById('mqtt-settings-modal');
        if (modal) {
            console.log('modal found, hiding...');
            const content = modal.querySelector('.info-modal-content');
            if (content) {
                content.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                content.style.opacity = '0';
                content.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 200);
            } else {
                console.log('content not found');
            }
        } else {
            console.log('modal not found');
        }
    };

    window.toggleEmulationVisibility = function(checkbox) {
        const showEmulation = checkbox.checked;
        localStorage.setItem('fppHomekitShowEmulation', showEmulation);

        const section = document.getElementById('emulation-section');
        if (section) {
            if (showEmulation) {
                section.style.display = 'block';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            } else {
                section.style.display = 'none';
            }
        }

        // Update the toggle text
        const toggleText = document.getElementById('emulation-visibility-text');
        if (toggleText) {
            toggleText.textContent = showEmulation ? 'Hide HomeKit Emulation Section' : 'Show HomeKit Emulation Section';
        }
    };

    // Toggle MQTT config visibility preference
    window.toggleMqttConfigVisibility = function(checkbox) {
        const showMqtt = checkbox.checked;
        localStorage.setItem('fppHomekitShowMqttConfig', showMqtt);

        const section = document.getElementById('mqtt-config-section');
        if (section) {
            if (showMqtt) {
                section.style.display = 'block';
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            } else {
                section.style.display = 'none';
            }
        }

        // Update the toggle text
        const toggleText = document.getElementById('mqtt-visibility-text');
        if (toggleText) {
            toggleText.textContent = showMqtt ? 'Hide MQTT Configuration' : 'Show MQTT Configuration';
        }
    };

    // Load status
    function loadStatus(forceFresh = false) {
        // Prevent concurrent updates
        if (isUpdating) {
            return;
        }

        // Debounce rapid successive calls (within 300ms for faster updates)
        // But allow forced fresh checks to bypass debounce
        const now = Date.now();
        if (!forceFresh && lastStatusUpdate && (now - lastStatusUpdate) < 300) {
            return;
        }
        lastStatusUpdate = now;

        isUpdating = true;
        
        // Set FPP status loading state
        fppStatusLoading = true;
        fppStatusLoadStartTime = Date.now();
        
        // Show loading state with yellow bouncing animation
        const fppStatusTextEl = document.getElementById('fpp-status-text');
        const fppStatusDotEl = document.getElementById('fpp-status-dot');
        if (fppStatusTextEl && fppStatusTextEl.textContent === 'Loading...') {
            // Only show loading animation if still in initial loading state
            if (fppStatusDotEl) {
                fppStatusDotEl.className = 'status-dot-large restarting';
            }
        } else if (fppStatusDotEl && fppStatusTextEl && !fppStatusTextEl.textContent.includes('Unable to Check')) {
            // Show loading animation for subsequent loads (but not if already showing error)
            fppStatusDotEl.className = 'status-dot-large restarting';
        }
        
        debugLog('Loading status...' + (forceFresh ? ' (forcing fresh check)' : ''));
        const url = API_BASE + '/status' + (forceFresh ? '?force=1' : '');
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Status updated', { playing: data.fpp_status?.playing, status: data.fpp_status?.status_name });
                updateStatusDisplay(data);
                // Clear loading state after display is updated (updateStatusDisplay will also clear it if valid data received)
                if (!fppStatusLoading) {
                    fppStatusLoadStartTime = 0;
                }
                // Clear any error messages on successful update with slide-out animation
                const messageContainer = document.getElementById('message-container');
                if (messageContainer) {
                    const errorMessages = messageContainer.querySelectorAll('.message.error');
                    errorMessages.forEach(msg => {
                        if (msg.textContent.includes('Error loading status')) {
                            msg.style.opacity = '0';
                            msg.style.transform = 'translateY(-100%)';
                            setTimeout(() => {
                                if (msg.parentNode) {
                                    msg.remove();
                                }
                            }, 300);
                        }
                    });
                }
                isUpdating = false;
            })
            .catch(error => {
                debugLog('Error loading status', error.message);
                // Clear loading state
                fppStatusLoading = false;
                fppStatusLoadStartTime = 0;
                // Set "Unable to Check Status" and keep it red
                const fppStatusTextEl = document.getElementById('fpp-status-text');
                const fppStatusDotEl = document.getElementById('fpp-status-dot');
                if (fppStatusTextEl) {
                    fppStatusTextEl.textContent = 'Unable to Check Status';
                }
                if (fppStatusDotEl) {
                    fppStatusDotEl.className = 'status-dot-large stopped';
                }
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
    // Store last known good values to prevent UI from resetting on incomplete updates
    let lastKnownStatus = {
        service_running: false,
        paired: false,
        fpp_status: {},
        playlist: ''
    };
    
    function updateStatusDisplay(data) {
        // Only update fields that are actually present in the response
        // This prevents clearing valid data when an update is incomplete
        if (data.service_running !== undefined) {
            lastKnownStatus.service_running = data.service_running;
        }
        if (data.paired !== undefined) {
            lastKnownStatus.paired = data.paired;
        }
        
        // Check if we have valid fpp_status data in this update
        const hasValidFppStatus = data.fpp_status !== undefined && 
                                   data.fpp_status !== null && 
                                   Object.keys(data.fpp_status).length > 0;
        
        if (hasValidFppStatus) {
            // Only update fpp_status if we have valid data (not empty object)
            // Merge fpp_status to preserve existing fields if new data is incomplete
            lastKnownStatus.fpp_status = Object.assign({}, lastKnownStatus.fpp_status, data.fpp_status);
        }
        
        if (data.playlist !== undefined) {
            lastKnownStatus.playlist = data.playlist;
        }
        
        // Use last known values (which may have been updated above)
        const serviceRunning = lastKnownStatus.service_running;
        const paired = lastKnownStatus.paired;
        const fppStatus = lastKnownStatus.fpp_status || {};
        const playlist = lastKnownStatus.playlist || '';
        
        // Update service status card
        const serviceStatusTextEl = document.getElementById('service-status-text');
        const serviceStatusDotEl = document.getElementById('service-status-dot');
        
        // Keep restarting state visible for minimum 2 seconds to show bouncing animation
        const minRestartDuration = 2000; // 2 seconds - enough time to see the bouncing animation
        const elapsedSinceRestart = restartStartTime > 0 ? Date.now() - restartStartTime : 0;
        const shouldShowRestarting = isServiceRestarting && (elapsedSinceRestart < minRestartDuration || !serviceRunning);
        
        if (shouldShowRestarting && serviceRunning && elapsedSinceRestart >= minRestartDuration) {
            // Service is back and minimum duration has passed
            isServiceRestarting = false;
            restartStartTime = 0;
            if (restartPollInterval) {
                clearInterval(restartPollInterval);
                restartPollInterval = null;
            }
        }

        let serviceStatusText = serviceRunning ? 'Running' : 'Stopped';
        let serviceStatusClass = serviceRunning ? 'running' : 'stopped';

        if (shouldShowRestarting) {
            serviceStatusText = 'Restarting...';
            serviceStatusClass = 'restarting';
        }

        if (serviceStatusTextEl) {
            serviceStatusTextEl.textContent = serviceStatusText;
        }
        if (serviceStatusDotEl) {
            // Always apply the restarting class if we're in restarting state, even if service is running
            // This ensures the bouncing animation shows during the restart process
            if (shouldShowRestarting) {
                serviceStatusDotEl.className = 'status-dot-large restarting';
            } else {
                serviceStatusDotEl.className = 'status-dot-large ' + serviceStatusClass;
            }
        }
        if (serviceRunning) {
            autoStartAttempted = false;
        } else if (!autoStartAttempted && !isServiceRestarting) {
            attemptAutoStart();
        }
        
        // Update pairing status card
        const pairingStatusTextEl = document.getElementById('pairing-status-text');
        const pairingStatusDotEl = document.getElementById('pairing-status-dot');
        const pairingStatusText = paired ? 'Paired' : 'Not Paired';
        if (pairingStatusTextEl) {
            pairingStatusTextEl.textContent = pairingStatusText;
        }
        if (pairingStatusDotEl) {
            pairingStatusDotEl.className = 'status-dot-large ' + (paired ? 'paired' : 'not-paired');
        }
        
        // Update FPP status - only if we have valid fpp_status data
        // Preserve "Unable to Check Status" if fpp_status is null/undefined/empty
        const fppStatusTextEl = document.getElementById('fpp-status-text');
        const currentFppStatusText = fppStatusTextEl ? fppStatusTextEl.textContent : '';
        
        // If we don't have valid fpp_status data in THIS update and we already have "Unable to Check Status", preserve it
        if (!hasValidFppStatus && currentFppStatusText.includes('Unable to Check')) {
            // Keep "Unable to Check Status" and red dot
            const fppStatusDotEl = document.getElementById('fpp-status-dot');
            if (fppStatusDotEl && !fppStatusLoading) {
                fppStatusDotEl.className = 'status-dot-large stopped';
            }
            // Clear loading state since we're not updating
            fppStatusLoading = false;
            fppStatusLoadStartTime = 0;
            return; // Don't update FPP status
        }
        
        // Clear loading state if we got valid data
        if (hasValidFppStatus) {
            fppStatusLoading = false;
            fppStatusLoadStartTime = 0;
        }
        
        // If we don't have valid fpp_status data and we're not preserving "Unable to Check Status", 
        // we might be in initial load - don't update yet, but ensure loading animation is showing
        if (!hasValidFppStatus && Object.keys(fppStatus).length === 0) {
            // Still loading, keep loading animation
            const fppStatusDotEl = document.getElementById('fpp-status-dot');
            if (fppStatusDotEl && fppStatusLoading) {
                fppStatusDotEl.className = 'status-dot-large restarting';
            }
            return;
        }
        
        const playing = fppStatus.playing || false;
        let statusText = fppStatus.status_text || fppStatus.status_name || 'Unknown';
        const statusName = (fppStatus.status_name || 'unknown').toLowerCase();
        const errorDetail = fppStatus.error_detail || '';
        const fppCurrentPlaylist = fppStatus.current_playlist || '';
        const fppCurrentSequence = fppStatus.current_sequence || '';
        const statusCode = fppStatus.status || 0;
        
        // Determine FPP status text and dot class
        let fppStatusText = 'Unknown';
        let fppDotClass = 'stopped';
        let fppStatusDetails = '';
        
        if (playing || statusName === 'playing') {
            fppStatusText = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M160-160v-320h160v320H160Zm240 0v-640h160v640H400Zm240 0v-440h160v440H640Z"/></svg>Playing';
            fppDotClass = 'playing';
            // Add playlist and sequence details
            if (fppCurrentPlaylist) {
                fppStatusDetails += '<div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Playlist: ' + escapeHtml(fppCurrentPlaylist) + '</div>';
            }
            if (fppCurrentSequence) {
                fppStatusDetails += '<div style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">Sequence: ' + escapeHtml(fppCurrentSequence) + '</div>';
            }
        } else if (statusName === 'testing') {
            fppStatusText = 'Testing';
            fppDotClass = 'testing';
        } else if (statusText.includes('Running') && !statusText.includes('Not Running') && !statusText.includes('Unreachable')) {
            fppStatusText = 'Running';
            fppDotClass = 'running';
        } else if (statusText.includes('Available') || statusCode === 0 || statusName === 'idle' || (!errorDetail && statusName !== 'unknown' && statusName !== '')) {
            // Check if FPP is paused (status code 2) vs truly idle
            const isPaused = statusCode === 2 || statusName === 'paused' || statusName.toLowerCase().includes('paused');
            const idleText = isPaused ? 'Paused' : 'idle';
            const idleIcon = isPaused ?
                '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#e3e3e3" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M560-200v-560h160v560H560Zm-320 0v-560h160v560H240Z"/></svg>' :
                '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#e3e3e3" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M520-200v-560h240v560H520Zm-320 0v-560h240v560H200Zm400-80h80v-400h-80v400Zm-320 0h80v-400h-80v400Zm0-400v400-400Zm320 0v400-400Z"/></svg>';
            fppStatusText = idleIcon + idleText;
            fppDotClass = isPaused ? 'paused' : 'running';
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
        const fppStatusDotEl = document.getElementById('fpp-status-dot');
        const fppStatusCard = document.getElementById('fpp-status-card');
        if (fppStatusTextEl) {
            fppStatusTextEl.innerHTML = fppStatusText;
        }
        if (fppStatusDotEl) {
            // Show loading animation if still loading, otherwise show actual status
            if (fppStatusLoading) {
                fppStatusDotEl.className = 'status-dot-large restarting';
            } else {
                fppStatusDotEl.className = 'status-dot-large ' + fppDotClass;
            }
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
        
        // Update playlist - only update if a new value is provided
        if (playlist && playlist !== '') {
            // Only update if we got a non-empty playlist value
            if (playlist !== currentPlaylist) {
                currentPlaylist = playlist;
                updatePlaylistStatusText(currentPlaylist);
                // Update dropdown if it's loaded and the value changed
                if (playlistSelect && playlistsLoaded) {
                    playlistSelect.value = currentPlaylist;
                }
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
                // Show placeholder QR code instead of error message
                document.getElementById('qr-loading').style.display = 'block';
                document.getElementById('qr-content').style.display = 'none';
                document.getElementById('qr-error').style.display = 'none';
                qrLoaded = false;
            }
        }

        // Update last command display with recent HomeKit commands
        // Only show commands from the last hour to avoid showing stale/old data
        const timeElement = document.getElementById('last-command-time');
        const textElement = document.getElementById('last-command-text');
        
        if (data.recent_homekit_commands && data.recent_homekit_commands.length > 0) {
            const lastCommand = data.recent_homekit_commands[data.recent_homekit_commands.length - 1];
            const now = Math.floor(Date.now() / 1000);
            const commandAge = now - lastCommand.timestamp;
            const oneHourAgo = 3600; // 1 hour in seconds
            
            // Only show command if it's from the last hour
            if (commandAge <= oneHourAgo && timeElement && textElement) {
                const date = new Date(lastCommand.timestamp * 1000);
                const timeString = date.toLocaleTimeString();
                const sourceText = lastCommand.source === 'homekit' ? 'HomeKit' : 'Emulate';
                timeElement.textContent = `${lastCommand.action} (${sourceText}) at ${timeString}`;
                textElement.style.display = 'block';
            } else if (textElement) {
                // Hide if command is too old
                textElement.style.display = 'none';
            }
        } else if (textElement) {
            // Hide if no commands available
            textElement.style.display = 'none';
        }
    }
    
    function attemptAutoStart() {
        if (autoStartAttempted) {
            return;
        }
        autoStartAttempted = true;
        showMessage('Starting HomeKit service...', 'info');
        
        // Set restarting state immediately to show bouncing yellow animation
        restartStartTime = Date.now();
        isServiceRestarting = true;
        
        // Update status dot immediately to show bouncing animation
        const serviceStatusTextEl = document.getElementById('service-status-text');
        const serviceStatusDotEl = document.getElementById('service-status-dot');
        if (serviceStatusTextEl) {
            serviceStatusTextEl.textContent = 'Restarting...';
        }
        if (serviceStatusDotEl) {
            serviceStatusDotEl.className = 'status-dot-large restarting';
        }
        
        // Start aggressive polling during restart
        if (restartPollInterval) {
            clearInterval(restartPollInterval);
        }
        restartPollInterval = setInterval(() => {
            loadStatus();
        }, 500);
        
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
                // Status will update via SSE automatically, no need to poll
                // Only refresh if SSE is not active
                if (!eventSource || eventSource.readyState !== EventSource.OPEN) {
                    setTimeout(() => loadStatus(), 1200);
                }
            })
            .catch(error => {
                showMessage('Unable to start service automatically: ' + error.message, 'error');
                // Clear restarting state on error
                isServiceRestarting = false;
                restartStartTime = 0;
                if (restartPollInterval) {
                    clearInterval(restartPollInterval);
                    restartPollInterval = null;
                }
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
                let setupCode = data.setup_code || '0000-0000';
                
                // Format setup code to XXXX-XXXX format
                // Remove all dashes first, then add one dash in the middle
                setupCode = setupCode.replace(/-/g, '');
                if (setupCode.length === 8) {
                    setupCode = setupCode.substring(0, 4) + '-' + setupCode.substring(4);
                }
                
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
        btn.classList.add('restarting');
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M160-160v-80h110l-16-14q-52-46-73-105t-21-119q0-111 66.5-197.5T400-790v84q-72 26-116 88.5T240-478q0 45 17 87.5t53 78.5l10 10v-98h80v240H160Zm400-10v-84q72-26 116-88.5T720-482q0-45-17-87.5T650-648l-10-10v98h-80v-240h240v80H690l16 14q49 49 71.5 106.5T800-482q0 111-66.5 197.5T560-170Z"/></svg>';
        
        // Set restarting state immediately with minimum duration
        restartStartTime = Date.now();
        isServiceRestarting = true;
        const serviceStatusTextEl = document.getElementById('service-status-text');
        const serviceStatusDotEl = document.getElementById('service-status-dot');
        if (serviceStatusTextEl) {
            serviceStatusTextEl.textContent = 'Restarting...';
        }
        if (serviceStatusDotEl) {
            serviceStatusDotEl.className = 'status-dot-large restarting';
        }

        // Start aggressive polling during restart (every 500ms)
        if (restartPollInterval) {
            clearInterval(restartPollInterval);
        }
        restartPollInterval = setInterval(() => {
            loadStatus();
        }, 500);

        // Remove the restarting class after 5 seconds
        setTimeout(() => {
            btn.classList.remove('restarting');
        }, 5000);
        
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
            // Status will be polled aggressively by the interval, no need for manual refreshes
            // Clean up after minimum duration has passed (handled in updateStatusDisplay)
            setTimeout(() => {
                btn.disabled = false;
                btn.classList.remove('restarting');
                // Restore original button content
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M160-160v-80h110l-16-14q-52-46-73-105t-21-119q0-111 66.5-197.5T400-790v84q-72 26-116 88.5T240-478q0 45 17 87.5t53 78.5l10 10v-98h80v240H160Zm400-10v-84q72-26 116-88.5T720-482q0-45-17-87.5T650-648l-10-10v98h-80v-240h240v80H690l16 14q49 49 71.5 106.5T800-482q0 111-66.5 197.5T560-170Z"/></svg>';
            }, 5000);
        })
        .catch(error => {
            debugLog('Error restarting service', error.message);
            showMessage('Error restarting service: ' + error.message, 'error');
            btn.disabled = false;
            btn.classList.remove('restarting');
            // Restore original button content
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M160-160v-80h110l-16-14q-52-46-73-105t-21-119q0-111 66.5-197.5T400-790v84q-72 26-116 88.5T240-478q0 45 17 87.5t53 78.5l10 10v-98h80v240H160Zm400-10v-84q72-26 116-88.5T720-482q0-45-17-87.5T650-648l-10-10v98h-80v-240h240v80H690l16 14q49 49 71.5 106.5T800-482q0 111-66.5 197.5T560-170Z"/></svg>';
            isServiceRestarting = false;
            restartStartTime = 0;
            if (restartPollInterval) {
                clearInterval(restartPollInterval);
                restartPollInterval = null;
            }
        });
    };

    // Update last command timestamp and type
    function updateLastCommandTime(commandType) {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        const timeElement = document.getElementById('last-command-time');
        const textElement = document.getElementById('last-command-text');
        if (timeElement && textElement) {
            timeElement.textContent = `${commandType} at ${timeString}`;
            textElement.style.display = 'block';
        }
    }

    window.emulateHomeKit = function(on) {
        const action = on ? 'ON' : 'OFF';
        const btnId = on ? 'emulate-on-btn' : 'emulate-off-btn';
        const btn = document.getElementById(btnId);

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = `Sending ${action}...`;

        debugLog(`Emulating HomeKit ${action} command`);

        fetch(API_BASE + '/emulate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'value=' + (on ? '1' : '0')
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            debugLog(`HomeKit ${action} emulation successful`, data);
            showMessage(`✓ HomeKit ${action} command emulated successfully`, 'success');

            // Update last command timestamp and type
            const commandType = on ? 'start' : 'stop';
            updateLastCommandTime(commandType);

            // Status will update via SSE automatically when FPP state changes
            // Don't manually refresh status here - let SSE handle it to avoid clearing UI
            // If SSE is not active, wait a bit longer before refreshing to let FPP state update
            if (!eventSource || eventSource.readyState !== EventSource.OPEN) {
                // Wait 2 seconds for FPP to process the command, then refresh
                setTimeout(() => {
                    // Only refresh if we still don't have SSE
                    if (!eventSource || eventSource.readyState !== EventSource.OPEN) {
                        loadStatus();
                    }
                }, 2000);
            }
        })
        .catch(error => {
            debugLog(`Error emulating HomeKit ${action}`, error.message);
            showMessage(`✗ Failed to emulate HomeKit ${action}: ${error.message}`, 'error');
        })
        .finally(() => {
            // Re-enable button
            btn.disabled = false;
            btn.textContent = originalText;
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
                    // Status will update via SSE automatically
                    if (!eventSource || eventSource.readyState !== EventSource.OPEN) {
                        setTimeout(() => loadStatus(), 300);
                    }
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
        // Show loading animation for FPP status immediately on page load
        const fppStatusDotEl = document.getElementById('fpp-status-dot');
        if (fppStatusDotEl) {
            fppStatusDotEl.className = 'status-dot-large restarting';
        }
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
        Promise.all([
            new Promise(resolve => setTimeout(() => { loadHomekitNetworkConfig(); resolve(); }, 100)),
            new Promise(resolve => setTimeout(() => { loadMQTTConfig(); resolve(); }, 200))
        ]);

        setTimeout(() => {
            const showEmulation = localStorage.getItem('fppHomekitShowEmulation') === 'true';
            const emulationSection = document.getElementById('emulation-section');
            const emulationToggle = document.getElementById('emulation-visibility-toggle');
            const emulationText = document.getElementById('emulation-visibility-text');

            if (emulationSection) {
                emulationSection.style.display = showEmulation ? 'block' : 'none';
            }
            if (emulationToggle) {
                emulationToggle.checked = showEmulation;
            }
            if (emulationText) {
                emulationText.textContent = showEmulation ? 'Hide HomeKit Emulation Section' : 'Show HomeKit Emulation Section';
            }

            // MQTT config section (hide by default)
            const showMqtt = localStorage.getItem('fppHomekitShowMqttConfig') === 'true';
            const mqttSection = document.getElementById('mqtt-config-section');
            const mqttToggle = document.getElementById('mqtt-visibility-toggle');
            const mqttText = document.getElementById('mqtt-visibility-text');

            if (mqttSection) {
                mqttSection.style.display = showMqtt ? 'block' : 'none';
            }
            if (mqttToggle) {
                mqttToggle.checked = showMqtt;
            }
            if (mqttText) {
                mqttText.textContent = showMqtt ? 'Hide MQTT Configuration' : 'Show MQTT Configuration';
            }
        }, 500);
        
        // Load status immediately on page load (don't wait for SSE)
        // Force fresh FPP status check on initial load
        loadStatus(true);
        loadPlaylists();
        
        initializeEventSource();
        
        // Fallback: If QR code hasn't loaded after SSE connects, trigger load again
        setTimeout(function() {
            const qrLoadingEl = document.getElementById('qr-loading');
            const qrContentEl = document.getElementById('qr-content');
            // If still showing loading state and content not shown, trigger load
            if (qrLoadingEl && qrLoadingEl.style.display !== 'none' && 
                (!qrContentEl || qrContentEl.style.display === 'none')) {
                // SSE might not have sent status yet, trigger another load
                loadStatus();
            }
        }, 1500); // Reduced from 2 seconds - just a quick fallback check
        
        // Only refresh on visibility change if SSE is not active
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && !eventSource) {
                // SSE not active, do a one-time refresh
                loadStatus();
                if (!playlistFetchInProgress) {
                    loadPlaylists();
                }
            }
        });
    });
    
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    });
})();
</script>
