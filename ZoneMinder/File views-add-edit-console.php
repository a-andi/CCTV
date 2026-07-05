sudo mkdir -p /usr/share/zoneminder/www/views

sudo tee /usr/share/zoneminder/www/views/console.php <<'EOF'
<?php
if (!isset($_SESSION['username'])) {
    header('Location: /zm/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Get stats
try {
    $dbh = new PDO('mysql:host=localhost;dbname=zm', 'zmuser', 'password123');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $dbh->query("SELECT COUNT(*) FROM Monitors WHERE Enabled = 1");
    $monitors = $stmt->fetchColumn();
    
    $stmt = $dbh->query("SELECT COUNT(*) FROM Events");
    $events = $stmt->fetchColumn();
    
    $stmt = $dbh->query("SELECT SUM(DiskSpace) FROM Events");
    $storageBytes = $stmt->fetchColumn();
    $storageGB = $storageBytes ? $storageBytes / 1024 / 1024 / 1024 : 0;
    
    $monitorList = $dbh->query("SELECT Id, Name, Type, Enabled, Device FROM Monitors ORDER BY Id");
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ZoneMinder - Console</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ecf0f1; min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 220px; background: #2c3e50; color: white; padding: 20px 0; overflow-y: auto; }
        .sidebar h1 { font-size: 20px; padding: 0 20px 20px; border-bottom: 1px solid #34495e; }
        .sidebar h1 span { color: #27ae60; }
        .sidebar .user-info { padding: 15px 20px; border-bottom: 1px solid #34495e; font-size: 13px; color: #bdc3c7; }
        .sidebar .user-info strong { color: white; }
        .sidebar .nav { list-style: none; padding: 10px 0; }
        .sidebar .nav a { display: block; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar .nav a:hover { background: #34495e; color: white; border-left-color: #27ae60; }
        .sidebar .nav a.active { background: #34495e; color: white; border-left-color: #27ae60; }
        .sidebar .nav a .icon { margin-right: 10px; }
        
        .main { margin-left: 220px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow: hidden; }
        .header h2 { float: left; color: #2c3e50; }
        .header .logout { float: right; background: #e74c3c; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; }
        .header .logout:hover { background: #c0392b; }
        .clearfix { clear: both; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-box h3 { color: #7f8c8d; font-size: 13px; text-transform: uppercase; }
        .stat-box .number { font-size: 32px; font-weight: bold; color: #2c3e50; margin-top: 5px; }
        .stat-box .number.green { color: #27ae60; }
        .stat-box .number.blue { color: #3498db; }
        .stat-box .number.orange { color: #f39c12; }
        
        .panel { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .panel h3 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        
        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .monitor-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60; }
        .monitor-card.offline { border-left-color: #e74c3c; }
        .monitor-card h4 { color: #2c3e50; }
        .monitor-card .info { color: #7f8c8d; font-size: 12px; margin: 3px 0; }
        .monitor-card .status { font-weight: bold; }
        .monitor-card .status.online { color: #27ae60; }
        .monitor-card .status.offline { color: #e74c3c; }
        
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 13px; border: none; cursor: pointer; }
        .btn:hover { background: #2980b9; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #219a52; }
        
        .admin-badge { background: #27ae60; padding: 2px 10px; border-radius: 10px; font-size: 11px; color: white; margin-left: 5px; }
        .error-msg { background: #fde8e8; color: #e74c3c; padding: 15px; border-radius: 5px; border-left: 4px solid #e74c3c; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .empty-state .icon { font-size: 48px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h1>📹 Zone<span>Minder</span></h1>
        <div class="user-info">
            👤 <strong><?php echo htmlspecialchars($username); ?></strong>
        </div>
        <ul class="nav">
            <li><a href="/zm/index.php" class="active"><span class="icon">📊</span> <span>Console</span></a></li>
            <li><a href="/zm/index.php?view=monitors"><span class="icon">📷</span> <span>Monitors</span></a></li>
            <li><a href="/zm/index.php?view=events"><span class="icon">📹</span> <span>Events</span></a></li>
            <li><a href="/zm/index.php?view=options"><span class="icon">⚙️</span> <span>Options</span></a></li>
            <li><a href="/zm/logout.php"><span class="icon">🚪</span> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">
            <h2>📊 Console</h2>
            <a href="/zm/logout.php" class="logout">🚪 Logout</a>
            <div class="clearfix"></div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-box">
                <h3>📷 Active Monitors</h3>
                <div class="number green"><?php echo $monitors ?? 0; ?></div>
            </div>
            <div class="stat-box">
                <h3>📹 Total Events</h3>
                <div class="number blue"><?php echo $events ?? 0; ?></div>
            </div>
            <div class="stat-box">
                <h3>💾 Storage Used</h3>
                <div class="number orange"><?php echo isset($storageGB) ? round($storageGB, 2) : 0; ?> GB</div>
            </div>
        </div>

        <div class="panel">
            <h3>📋 Monitors</h3>
            <?php if (isset($monitorList) && $monitorList->rowCount() > 0): ?>
                <div class="monitor-grid">
                    <?php while ($monitor = $monitorList->fetch()): ?>
                        <div class="monitor-card <?php echo $monitor['Enabled'] ? '' : 'offline'; ?>">
                            <h4><?php echo htmlspecialchars($monitor['Name']); ?></h4>
                            <div class="info">Type: <?php echo htmlspecialchars($monitor['Type']); ?></div>
                            <div class="info">Device: <?php echo htmlspecialchars($monitor['Device'] ?? '-'); ?></div>
                            <div class="info">Status: <span class="status <?php echo $monitor['Enabled'] ? 'online' : 'offline'; ?>">
                                <?php echo $monitor['Enabled'] ? '🟢 Online' : '🔴 Offline'; ?>
                            </span></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📷</div>
                    <h3>No Monitors Configured</h3>
                    <p>Click "Add Monitor" below to add your first camera.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>⚡ Quick Actions</h3>
            <a href="/zm/index.php?view=monitors" class="btn btn-green">📷 Add Monitor</a>
            <a href="/zm/index.php?view=events" class="btn">📹 View Events</a>
            <a href="/zm/index.php?view=options" class="btn">⚙️ Settings</a>
        </div>
    </div>
</body>
</html>
EOF

sudo chown www-data:www-data /usr/share/zoneminder/www/views/console.php

sudo systemctl restart apache2




