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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">FPP Homekit</h2>
            <div style="display: flex; gap: 8px;">
                <button class="settings-button" onclick="showMqttSettings()" id="settings-btn" title="MQTT Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg>
                </button>
                <button class="info-button" onclick="showMqttInfo()" title="MQTT Setup Information">
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
                            <button class="restart-icon-btn" onclick="restartService()" id="restart-btn" title="Restart Service">
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

                <div class="status-card" id="emulation-section">
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
                </div>
                
                <div class="config-right">
                    <div class="status-card pairing-card">
                        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">Pair with HomeKit</h3>
                        <div id="pairing-section">
                            <div class="qr-container">
                                <div id="qr-loading" style="display: block;">
                                    <div class="qr-code-box">
                                        <div class="qr-placeholder">
                                            <div class="qr-placeholder-grid"></div>
                                        </div>
                                        <div class="setup-code-placeholder">
                                            <span class="setup-code-label">Setup Code</span>
                                            <div class="setup-code-value">
                                                <span>XXX-XX-XXX</span>
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
                                                <button class="copy-btn" onclick="copySetupCode()" title="Copy Setup Code" id="copy-btn">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M5.5 2.5H11.5C12.0523 2.5 12.5 2.94772 12.5 3.5V9.5C12.5 10.0523 12.0523 10.5 11.5 10.5H9.5V12.5C9.5 13.0523 9.05228 13.5 8.5 13.5H2.5C1.94772 13.5 1.5 13.0523 1.5 12.5V6.5C1.5 5.94772 1.94772 5.5 2.5 5.5H4.5V3.5C4.5 2.94772 4.94772 2.5 5.5 2.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M4.5 5.5H8.5C9.05228 5.5 9.5 5.94772 9.5 6.5V10.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
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
                            <p class="success-message">✓ Successfully paired with HomeKit</p>
                            <p class="info-text">You can now control FPP from the Home app on your iOS devices.</p>
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
                        <input type="checkbox" id="emulation-visibility-toggle" checked onchange="toggleEmulationVisibility(this)">
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
    let isUpdating = false;
    let playlistsLoaded = false;
    let currentPlaylist = '';
    let qrLoaded = false;
    let autoStartAttempted = false;
    let playlistFetchInProgress = false;
    let lastStatusUpdate = 0;
    
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

    // Toggle emulation section visibility preference
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
    function loadStatus() {
        // Prevent concurrent updates
        if (isUpdating) {
            return;
        }

        // Debounce rapid successive calls (within 1 second)
        const now = Date.now();
        if (lastStatusUpdate && (now - lastStatusUpdate) < 1000) {
            return;
        }
        lastStatusUpdate = now;

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
        const serviceStatusTextEl = document.getElementById('service-status-text');
        const serviceStatusDotEl = document.getElementById('service-status-dot');
        const serviceStatusText = serviceRunning ? 'Running' : 'Stopped';
        if (serviceStatusTextEl) {
            serviceStatusTextEl.textContent = serviceStatusText;
        }
        if (serviceStatusDotEl) {
            serviceStatusDotEl.className = 'status-dot-large ' + (serviceRunning ? 'running' : 'stopped');
        }
        if (serviceRunning) {
            autoStartAttempted = false;
        } else if (!autoStartAttempted) {
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
            fppStatusText = '<svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><path d="M160-160v-320h160v320H160Zm240 0v-640h160v640H400Zm240 0v-440h160v440H640Z"/></svg>Playing';
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
            fppStatusTextEl.innerHTML = fppStatusText;
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
                // Show placeholder QR code instead of error message
                document.getElementById('qr-loading').style.display = 'block';
                document.getElementById('qr-content').style.display = 'none';
                document.getElementById('qr-error').style.display = 'none';
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
        btn.classList.add('restarting');
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M160-160v-80h110l-16-14q-52-46-73-105t-21-119q0-111 66.5-197.5T400-790v84q-72 26-116 88.5T240-478q0 45 17 87.5t53 78.5l10 10v-98h80v240H160Zm400-10v-84q72-26 116-88.5T720-482q0-45-17-87.5T650-648l-10-10v98h-80v-240h240v80H690l16 14q49 49 71.5 106.5T800-482q0 111-66.5 197.5T560-170Z"/></svg>';
        
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
            // Refresh status after a short delay
            setTimeout(() => {
                loadStatus();
            }, 2000);
            // Refresh again after longer delay to ensure status is updated
            setTimeout(() => {
                loadStatus();
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
        });
    };

    // Emulate HomeKit commands for testing
    window.emulateHomeKit = function(on) {
        const action = on ? 'ON' : 'OFF';
        const btnId = on ? 'emulate-on-btn' : 'emulate-off-btn';
        const btn = document.getElementById(btnId);

        // Disable button temporarily
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

            // Refresh status after a short delay
            setTimeout(() => {
                loadStatus();
            }, 1000);
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
        
        // Stagger initial API calls to prevent overwhelming the server
        loadPlaylists(true);
        setTimeout(() => loadHomekitNetworkConfig(), 100);
        setTimeout(() => loadStatus(), 200);
        setTimeout(() => loadMQTTConfig(), 300);

        // Initialize section visibility preferences
        setTimeout(() => {
            // Emulation section (show by default)
            const showEmulation = localStorage.getItem('fppHomekitShowEmulation') !== 'false'; // Default to true
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
        
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(function() {
            loadStatus();
            if (!playlistFetchInProgress) {
                loadPlaylists();
            }
        }, 15000); // Increased from 10s to 15s to reduce server load
        
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
