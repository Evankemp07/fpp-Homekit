<?php
/*
A way for plugins to provide their own PHP API endpoints.

To use, create a file called api.php file in the plugin's directory
and provide a getEndpointsPLUGINNAME() function which returns an
array describing the endpoints the plugin implements.  Since PHP
does not allow hyphens in function names, any hyphens in the plugin
name must be removed when substituting for PLUGINNAME above and if
the plugin name is used in any callback function names.  It is
also best to use unique endpoint names as shown below to eliminate
any conflicts with stock FPP code or other plugin API callbacks.

All endpoints are prefixed with /api/plugin/PLUGIN-NAME but only
the part after PLUGIN-NAME is specified in the getEndpointsPLUGINNAME()
data.  The plugin name is used as-is in the endpoint URL, hyphens
are not removed.  -- limonade.php is used for the underlying implementation so
param("param1" ) can be used for an api like /api/plugin/fpp-BigButtons/:param1

Here is a simple example which would add a
/api/plugin/fpp-BigButtons/version endpoint to the fpp-Bigbuttons plugin.
*/


function getEndpointsfppHomekit() {
    $result = array();

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'status',
        'callback' => 'fppHomekitStatus');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'qr-code',
        'callback' => 'fppHomekitQRCode');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'restart',
        'callback' => 'fppHomekitRestart');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'pairing-info',
        'callback' => 'fppHomekitPairingInfo');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'playlists',
        'callback' => 'fppHomekitPlaylists');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'config',
        'callback' => 'fppHomekitGetConfig');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'config',
        'callback' => 'fppHomekitSaveConfig');
    array_push($result, $ep);

    return $result;
}

// GET /api/plugin/fpp-Homekit/status
function fppHomekitStatus() {
    $pluginDir = dirname(__FILE__);
    $pidFile = $pluginDir . '/scripts/homekit_service.pid';
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    $result = array();
    
    // Check if service is running
    $running = false;
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid) {
            // Check if process is running (works on Unix-like systems)
            if (function_exists('posix_kill')) {
                if (posix_kill($pid, 0)) {
                    $running = true;
                }
            } else {
                // Fallback for systems without posix_kill (Windows, some macOS setups)
                // Try using ps command
                $output = array();
                $return_var = 0;
                @exec("ps -p $pid 2>&1", $output, $return_var);
                if ($return_var === 0 && !empty($output)) {
                    $running = true;
                }
            }
        }
    }
    $result['service_running'] = $running;
    
    // Check pairing status
    $paired = false;
    if (file_exists($stateFile)) {
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state && isset($state['paired_clients']) && count($state['paired_clients']) > 0) {
                $paired = true;
            }
        }
    }
    $result['paired'] = $paired;
    
    // Get FPP status
    $fppStatus = array('playing' => false, 'status_name' => 'unknown');
    try {
        $ch = curl_init('http://localhost:32320/api/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $fppStatus = json_decode($response, true);
        }
    } catch (Exception $e) {
        // Ignore errors
    }
    $result['fpp_status'] = $fppStatus;
    
    // Get configured playlist
    $playlist = '';
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config && isset($config['playlist_name'])) {
            $playlist = $config['playlist_name'];
        }
    }
    $result['playlist'] = $playlist;
    
    return json($result);
}

// GET /api/plugin/fpp-Homekit/qr-code
function fppHomekitQRCode() {
    $pluginDir = dirname(__FILE__);
    $infoFile = $pluginDir . '/scripts/homekit_pairing_info.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    // Try to get setup code and setup ID from pairing info file first
    $setupCode = '123-45-678';
    $setupID = 'HOME';
    
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info) {
                if (isset($info['setup_code'])) {
                    $setupCode = $info['setup_code'];
                }
                if (isset($info['setup_id'])) {
                    $setupID = $info['setup_id'];
                }
            }
        }
    } elseif (file_exists($stateFile)) {
        // Fallback to state file
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state) {
                if (isset($state['pincode'])) {
                    $setupCode = $state['pincode'];
                }
                if (isset($state['mac'])) {
                    // Use last 4 chars of MAC as setup ID
                    $setupID = strtoupper(substr($state['mac'], -4));
                }
            }
        }
    }
    
    // Check if QR code data is already in the info file
    $qrData = null;
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info && isset($info['qr_data'])) {
                $qrData = $info['qr_data'];
            }
        }
    }
    
    // If not available, generate QR code data
    if (!$qrData) {
        // Format: X-HM://[8-char hex setup ID][setup code without dashes]
        $setupCodeClean = str_replace('-', '', $setupCode);
        $setupIDHex = bin2hex($setupID);
        if (strlen($setupIDHex) < 8) {
            $setupIDHex = str_pad($setupIDHex, 8, '0', STR_PAD_LEFT);
        }
        $qrData = "X-HM://" . substr($setupIDHex, 0, 8) . $setupCodeClean;
    }
    
    // Use Python to generate QR code image
    $pythonScript = "import qrcode; import io; import base64; qr = qrcode.QRCode(version=1, box_size=10, border=4); qr.add_data('" . escapeshellarg($qrData) . "'); qr.make(fit=True); img = qr.make_image(fill_color='black', back_color='white'); buf = io.BytesIO(); img.save(buf, format='PNG'); print(base64.b64encode(buf.getvalue()).decode())";
    
    $output = shell_exec("python3 -c " . escapeshellarg($pythonScript) . " 2>&1");
    
    if ($output) {
        header('Content-Type: image/png');
        echo base64_decode(trim($output));
        return;
    }
    
    // Fallback: return QR code data as JSON
    $result = array(
        'qr_data' => $qrData,
        'setup_code' => $setupCode,
        'setup_id' => $setupID
    );
    return json($result);
}

// POST /api/plugin/fpp-Homekit/restart
function fppHomekitRestart() {
    $pluginDir = dirname(__FILE__);
    $pidFile = $pluginDir . '/scripts/homekit_service.pid';
    
    // Stop service
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid && posix_kill($pid, 0)) {
            posix_kill($pid, SIGTERM);
            sleep(1);
            if (posix_kill($pid, 0)) {
                posix_kill($pid, SIGKILL);
            }
        }
        @unlink($pidFile);
    }
    
    // Start service
    $script = $pluginDir . '/scripts/homekit_service.py';
    if (file_exists($script)) {
        $cmd = "cd " . escapeshellarg($pluginDir . '/scripts') . " && nohup python3 " . escapeshellarg($script) . " > /dev/null 2>&1 &";
        shell_exec($cmd);
        sleep(2);
    }
    
    $result = array('status' => 'restarted');
    return json($result);
}

// GET /api/plugin/fpp-Homekit/pairing-info
function fppHomekitPairingInfo() {
    $pluginDir = dirname(__FILE__);
    $infoFile = $pluginDir . '/scripts/homekit_pairing_info.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    $result = array(
        'paired' => false,
        'setup_code' => '123-45-678',
        'setup_id' => 'HOME'
    );
    
    // Get setup code and ID from pairing info file
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info) {
                if (isset($info['setup_code'])) {
                    $result['setup_code'] = $info['setup_code'];
                }
                if (isset($info['setup_id'])) {
                    $result['setup_id'] = $info['setup_id'];
                }
            }
        }
    }
    
    // Check pairing status from state file
    if (file_exists($stateFile)) {
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state) {
                if (isset($state['paired_clients']) && count($state['paired_clients']) > 0) {
                    $result['paired'] = true;
                }
            }
        }
    }
    
    return json($result);
}

// GET /api/plugin/fpp-Homekit/playlists
function fppHomekitPlaylists() {
    $playlists = array();
    
    try {
        $ch = curl_init('http://localhost:32320/api/playlists');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            
            if ($data) {
                // Handle different FPP API response formats
                $playlistArray = null;
                
                // Format 1: {"playlists": [...]}
                if (isset($data['playlists']) && is_array($data['playlists'])) {
                    $playlistArray = $data['playlists'];
                }
                // Format 2: Direct array response
                elseif (is_array($data) && isset($data[0])) {
                    $playlistArray = $data;
                }
                
                if ($playlistArray) {
                    foreach ($playlistArray as $playlist) {
                        // Handle object format: {"name": "PlaylistName"}
                        if (is_array($playlist) && isset($playlist['name'])) {
                            $playlists[] = $playlist['name'];
                        }
                        // Handle string format: "PlaylistName"
                        elseif (is_string($playlist)) {
                            $playlists[] = $playlist;
                        }
                        // Handle object with different key: {"playlist": "PlaylistName"} or {"PlaylistName": {...}}
                        elseif (is_array($playlist)) {
                            // Try common keys
                            if (isset($playlist['playlist'])) {
                                $playlists[] = $playlist['playlist'];
                            } elseif (isset($playlist['PlaylistName'])) {
                                $playlists[] = $playlist['PlaylistName'];
                            } elseif (isset($playlist['playlistName'])) {
                                $playlists[] = $playlist['playlistName'];
                            }
                        }
                    }
                }
            }
        } else {
            // Log error for debugging (only in dev mode)
            if (defined('DEBUG') && DEBUG) {
                error_log("FPP API Error: HTTP $httpCode" . ($curlError ? " - $curlError" : ""));
            }
        }
    } catch (Exception $e) {
        // Log error for debugging (only in dev mode)
        if (defined('DEBUG') && DEBUG) {
            error_log("FPP API Exception: " . $e->getMessage());
        }
    }
    
    // Fallback: Read playlists directly from filesystem if API failed
    // FPP playlists are stored in /home/fpp/media/playlists as JSON files
    if (empty($playlists)) {
        $playlistDirs = array(
            '/home/fpp/media/playlists',  // Standard FPP location
            '/opt/fpp/media/playlists'     // Alternative location
        );
        
        // Add environment variable path if set
        $fppMediaDir = getenv('FPP_MEDIA_DIR');
        if ($fppMediaDir) {
            $playlistDirs[] = $fppMediaDir . '/playlists';
        }
        
        foreach ($playlistDirs as $playlistDir) {
            if ($playlistDir && is_dir($playlistDir) && is_readable($playlistDir)) {
                $files = scandir($playlistDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                        $playlistName = pathinfo($file, PATHINFO_FILENAME);
                        if ($playlistName && !in_array($playlistName, $playlists)) {
                            $playlists[] = $playlistName;
                        }
                    }
                }
                break; // Found a valid directory, no need to check others
            }
        }
    }
    
    // Sort playlists alphabetically
    sort($playlists);
    
    $result = array('playlists' => $playlists);
    return json($result);
}

// GET /api/plugin/fpp-Homekit/config
function fppHomekitGetConfig() {
    $pluginDir = dirname(__FILE__);
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    
    $result = array('playlist_name' => '');
    
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config) {
            $result = $config;
        }
    }
    
    return json($result);
}

// POST /api/plugin/fpp-Homekit/config
function fppHomekitSaveConfig() {
    $pluginDir = dirname(__FILE__);
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    
    $playlistName = '';
    if (isset($_POST['playlist_name'])) {
        $playlistName = $_POST['playlist_name'];
    } elseif (isset($_GET['playlist_name'])) {
        $playlistName = $_GET['playlist_name'];
    }
    
    $config = array('playlist_name' => $playlistName);
    
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false) {
        $result = array('status' => 'saved', 'config' => $config);
    } else {
        $result = array('status' => 'error', 'message' => 'Failed to save config');
    }
    
    return json($result);
}

?>
