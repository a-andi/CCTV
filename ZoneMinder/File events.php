sudo tee /usr/share/zoneminder/www/events.php <<'EOF'
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

// Get events
try {
    $eventsList = $dbh->query("SELECT Id, MonitorId, Name, StartDateTime, Length, Frames FROM Events ORDER BY Id DESC LIMIT 50");
} catch (Exception $e) {
    $eventsList = null;
    $error = $e->getMessage();
}

// Get monitor names
$monitorNames = [];
try {
    $stmt = $dbh->query("SELECT Id, Name FROM Monitors");
    while ($row = $stmt->fetch()) {
        $monitorNames[$row['Id']] = $row['Name'];
    }
} catch (Exception $e) {}

// Get total events
try {
    $stmt = $dbh->query("SELECT COUNT(*) FROM Events");
    $totalEvents = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalEvents = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Events - ZoneMinder</title>
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
        
        .event-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .event-table th { background: #34495e; color: white; padding: 10px; text-align: left; font-size: 13px; }
        .event-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        .event-table tr:hover { background: #f8f9fa; }
        .event-table .event-id { color: #7f8c8d; font-weight: bold; }
        
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .empty-state .icon { font-size: 48px; }
        .error-msg { background: #fde8e8; color: #e74c3c; padding: 15px; border-radius: 5px; border-left: 4px solid #e74c3c; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📹 Zone<span>Minder</span> - Events</h1>
        <a href="/zm/index.php" class="back">← Back</a>
        <div class="clearfix"></div>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>📋 Events <span class="badge"><?php echo $totalEvents; ?></span></h2>
        <?php if ($eventsList && $eventsList->rowCount() > 0): ?>
            <table class="event-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Monitor</th>
                        <th>Name</th>
                        <th>Start Time</th>
                        <th>Duration</th>
                        <th>Frames</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $eventsList->fetch()): 
                        $monName = $monitorNames[$event['MonitorId']] ?? 'Monitor ' . $event['MonitorId'];
                    ?>
                        <tr>
                            <td class="event-id">#<?php echo $event['Id']; ?></td>
                            <td><?php echo htmlspecialchars($monName); ?></td>
                            <td><?php echo htmlspecialchars($event['Name'] ?? 'Event'); ?></td>
                            <td><?php echo $event['StartDateTime'] ?? 'N/A'; ?></td>
                            <td><?php echo $event['Length']; ?>s</td>
                            <td><?php echo $event['Frames']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📹</div>
                <h3>No Events Recorded</h3>
                <p>Events will appear here when your cameras record.</p>
                <p style="font-size:12px;color:#95a5a6;margin-top:10px;">
                    Make sure your monitors are set to Record or Modect mode.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
EOF

sudo chown www-data:www-data /usr/share/zoneminder/www/events.php


sudo systemctl restart apache2


