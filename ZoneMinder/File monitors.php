sudo tee /usr/share/zoneminder/www/monitors.php <<'EOF'
<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /zm/login.php');
    exit;
}

require_once dirname(__FILE__).'/includes/config.php';

// Koneksi database
try {
    $dbh = new PDO('mysql:host=localhost;dbname=zm', 'zmuser', 'password123');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$username = $_SESSION['username'] ?? 'User';

// Get monitors
try {
    $monitorList = $dbh->query("SELECT Id, Name, Type, Enabled, Device, Host, Port, Function FROM Monitors ORDER BY Id");
} catch (Exception $e) {
    $monitorList = null;
    $error = $e->getMessage();
}

// Get total monitors
try {
    $stmt = $dbh->query("SELECT COUNT(*) FROM Monitors WHERE Enabled = 1");
    $totalMonitors = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalMonitors = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
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
        .panel h2 .badge { background: #3498db; color: white; padding: 2px 10px; border-radius: 10px; font-size: 12px; }
        
        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px; }
        .monitor-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60; }
        .monitor-card.offline { border-left-color: #e74c3c; }
        .monitor-card h3 { color: #2c3e50; margin-bottom: 5px; }
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
        
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2980b9; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #219a52; }
        .btn-red { background: #e74c3c; }
        .btn-red:hover { background: #c0392b; }
        
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .empty-state .icon { font-size: 48px; }
        .error-msg { background: #fde8e8; color: #e74c3c; padding: 15px; border-radius: 5px; border-left: 4px solid #e74c3c; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: inline-block; width: 120px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; width: 250px; }
        .form-group .help-text { color: #7f8c8d; font-size: 11px; display: block; margin-left: 125px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📷 Zone<span>Minder</span> - Monitors</h1>
        <a href="/zm/index.php" class="back">← Back</a>
        <div class="clearfix"></div>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>📋 Monitors <span class="badge"><?php echo $totalMonitors; ?></span></h2>
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
                <p>Add your first monitor to start recording.</p>
                <p style="font-size:12px;color:#95a5a6;margin-top:10px;">
                    Go to Options → Add Monitor or use the API.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
EOF

sudo chown www-data:www-data /usr/share/zoneminder/www/monitors.php



sudo systemctl restart apache2




