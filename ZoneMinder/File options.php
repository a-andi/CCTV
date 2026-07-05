sudo tee /usr/share/zoneminder/www/options.php <<'EOF'
<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /zm/login.php');
    exit;
}

require_once dirname(__FILE__).'/includes/config.php';

$username = $_SESSION['username'] ?? 'User';

// Cek admin
$isAdmin = false;
try {
    $dbh = new PDO('mysql:host=localhost;dbname=zm', 'zmuser', 'password123');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $dbh->prepare("SELECT System FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['System'] == 'Edit');
} catch (Exception $e) {
    $isAdmin = false;
}

if (!$isAdmin) {
    header('Location: /zm/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Options - ZoneMinder</title>
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
        
        .info-msg { background: #e8f0fe; color: #3498db; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ Zone<span>Minder</span> - Options</h1>
        <a href="/zm/index.php" class="back">← Back</a>
        <div class="clearfix"></div>
    </div>

    <div class="panel">
        <h2>⚙️ System Options</h2>
        <div class="info-msg">
            ℹ️ Options page is under construction. 
            <br>For full ZoneMinder options, please use the original ZoneMinder GUI.
            <br><br>
            <strong>Tip:</strong> You can add monitors and configure settings through the database or use the original ZoneMinder interface.
        </div>
        <p style="color:#7f8c8d;margin-top:10px;">
            <strong>Current User:</strong> <?php echo htmlspecialchars($username); ?> (Admin)
        </p>
    </div>
</body>
</html>
EOF

sudo chown www-data:www-data /usr/share/zoneminder/www/options.php


sudo systemctl restart apache2



