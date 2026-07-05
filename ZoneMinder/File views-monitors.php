sudo tee /usr/share/zoneminder/www/views/monitors.php <<'EOF'
<?php
if (!isset($_SESSION['username'])) {
    header('Location: /zm/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Koneksi database
try {
    $dbh = new PDO('mysql:host=localhost;dbname=zm', 'zmuser', 'password123');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$message = '';

// Handle Add Monitor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_monitor') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'Local';
    $device = $_POST['device'] ?? '/dev/video0';
    $channel = intval($_POST['channel'] ?? 0);
    $host = $_POST['host'] ?? '';
    $port = intval($_POST['port'] ?? 0);
    $path = $_POST['path'] ?? '';
    $function = $_POST['function'] ?? 'Monitor';
    
    try {
        $stmt = $dbh->prepare("INSERT INTO Monitors (Name, Type, Device, Channel, Enabled, Host, Port, Path, `Function`) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $device, $channel, $host, $port, $path, $function]);
        $message = "✅ Monitor '$name' added successfully!";
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get monitors
$monitorList = $dbh->query("SELECT Id, Name, Type, Enabled, Device, Channel, Host, Port, Path, `Function` FROM Monitors ORDER BY Id");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Monitors - ZoneMinder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ecf0f1; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; overflow: hidden; }
        .header h1 { float: left; margin: 0; }
        .header h1 span { color: #27ae60; }
        .header .back { float: right; background: #3498db; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; }
        .header .back:hover { background: #2980b9; }
        .clearfix { clear: both; }
        
        .panel { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .panel h2 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: inline-block; width: 120px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; width: 250px; }
        .form-group .help-text { color: #7f8c8d; font-size: 11px; display: block; margin-left: 125px; }
        
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 13px; border: none; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #219a52; }
        
        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px; }
        .monitor-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60; }
        .monitor-card.offline { border-left-color: #e74c3c; }
        .monitor-card h3 { color: #2c3e50; }
        .monitor-card .info { color: #7f8c8d; font-size: 12px; margin: 3px 0; }
        .monitor-card .status { font-weight: bold; }
        .monitor-card .status.online { color: #27ae60; }
        .monitor-card .status.offline { color: #e74c3c; }
        .function-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .function-badge.monitor { background: #3498db; color: white; }
        .function-badge.modect { background: #f39c12; color: white; }
        .function-badge.record { background: #e74c3c; color: white; }
        .function-badge.mocord { background: #9b59b6; color: white; }
        .function-badge.none { background: #95a5a6; color: white; }
        
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background: #e8fde8; color: #27ae60; border-left: 4px solid #27ae60; }
        .message.error { background: #fde8e8; color: #e74c3c; border-left: 4px solid #e74c3c; }
        
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .empty-state .icon { font-size: 48px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📷 Zone<span>Minder</span> - Monitors</h1>
        <a href="/zm/index.php" class="back">← Back</a>
        <div class="clearfix"></div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Add Monitor Form -->
    <div class="panel">
        <h2>➕ Add Monitor</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_monitor">
            
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Camera 1" required>
            </div>
            
            <div class="form-group">
                <label>Function:</label>
                <select name="function">
                    <option value="Monitor">Monitor (Live only)</option>
                    <option value="Modect">Modect (Record on motion)</option>
                    <option value="Record">Record (Continuous)</option>
                    <option value="Mocord">Mocord (Record + motion)</option>
                    <option value="None">None (Disabled)</option>
                </select>
                <div class="help-text">Choose recording mode</div>
            </div>
            
            <div class="form-group">
                <label>Type:</label>
                <select name="type">
                    <option value="Local">Local (USB)</option>
                    <option value="Ffmpeg">Ffmpeg (IP/RTSP)</option>
                    <option value="Remote">Remote</option>
                    <option value="File">File</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Device:</label>
                <input type="text" name="device" value="/dev/video0" placeholder="/dev/video0 or rtsp://...">
                <div class="help-text">USB: /dev/video0 | IP: rtsp://user:pass@ip:554/stream</div>
            </div>
            
            <div class="form-group">
                <label>Channel:</label>
                <input type="number" name="channel" value="0" min="0">
            </div>
            
            <div class="form-group">
                <label>Host:</label>
                <input type="text" name="host" placeholder="192.168.1.100">
            </div>
            
            <div class="form-group">
                <label>Port:</label>
                <input type="number" name="port" value="0" min="0">
            </div>
            
            <div class="form-group">
                <label>Path:</label>
                <input type="text" name="path" placeholder="/stream">
            </div>
            
            <button type="submit" class="btn btn-green">➕ Add Monitor</button>
        </form>
    </div>

    <!-- Monitor List -->
    <div class="panel">
        <h2>📋 Monitors List</h2>
        <?php if ($monitorList && $monitorList->rowCount() > 0): ?>
            <div class="monitor-grid">
                <?php while ($monitor = $monitorList->fetch()): 
                    $func = $monitor['Function'] ?? 'Monitor';
                    $funcClass = strtolower($func);
                ?>
                    <div class="monitor-card <?php echo $monitor['Enabled'] ? '' : 'offline'; ?>">
                        <h3>
                            <?php echo htmlspecialchars($monitor['Name']); ?>
                            <span class="function-badge <?php echo $funcClass; ?>"><?php echo $func; ?></span>
                        </h3>
                        <div class="info">Type: <?php echo htmlspecialchars($monitor['Type']); ?></div>
                        <div class="info">Device: <?php echo htmlspecialchars($monitor['Device'] ?? '-'); ?></div>
                        <?php if (!empty($monitor['Host'])): ?>
                            <div class="info">Host: <?php echo htmlspecialchars($monitor['Host']); ?></div>
                        <?php endif; ?>
                        <div class="info">Status: <span class="status <?php echo $monitor['Enabled'] ? 'online' : 'offline'; ?>">
                            <?php echo $monitor['Enabled'] ? '🟢 Online' : '🔴 Offline'; ?>
                        </span></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📷</div>
                <h3>No Monitors</h3>
                <p>Add your first monitor using the form above.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
EOF

sudo chown www-data:www-data /usr/share/zoneminder/www/views/monitors.php
