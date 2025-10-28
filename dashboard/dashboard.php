<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

// Fetch role name
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$user['role_id']]);
$role_name = $stmt->fetchColumn();

// Fetch statistics
$totalSongs = $pdo->prepare("SELECT COUNT(*) FROM music WHERE artist_id = ?");
$totalSongs->execute([$user['id']]);
$songCount = $totalSongs->fetchColumn();

$totalViews = $pdo->prepare("SELECT SUM(views) FROM music WHERE artist_id = ?");
$totalViews->execute([$user['id']]);
$viewCount = $totalViews->fetchColumn() ?: 0;

$totalStreams = $pdo->prepare("
    SELECT COUNT(*) 
    FROM streams s 
    JOIN music m ON s.music_id = m.id 
    WHERE m.artist_id = ?
");
$totalStreams->execute([$user['id']]);
$streamCount = $totalStreams->fetchColumn();

$unreadCount = $pdo->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$unreadCount->execute([$user['id']]);
$unreadNotifications = $unreadCount->fetchColumn();

// Fetch latest songs
$songs = $pdo->prepare("
    SELECT id, title, genre, views, created_at 
    FROM music 
    WHERE artist_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$songs->execute([$user['id']]);
$music_list = $songs->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest notifications
$notes = $pdo->prepare("
    SELECT message, created_at, is_read 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$notes->execute([$user['id']]);
$notifications = $notes->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🎵 Dashboard - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      font-family: 'Segoe UI', sans-serif;
      color: #333;
      padding-top: 80px;
    }

    .container {
      max-width: 1200px;
    }

    .dashboard-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .dashboard-header h2 {
      font-weight: 700;
      color: #fff;
    }

    .role-badge {
      background: #fff;
      color: #5b4bcc;
      padding: 5px 15px;
      border-radius: 50px;
      font-weight: 600;
      box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }

    /* Stats grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: #fff;
      border-radius: 15px;
      text-align: center;
      padding: 25px 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon { font-size: 2rem; margin-bottom: 10px; color: #5b4bcc; }
    .stat-label { font-weight: 600; color: #666; }
    .stat-value { font-size: 1.6rem; font-weight: 700; color: #333; }

    /* Content cards */
    .content-card {
      background: #fff;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .empty-state {
      text-align: center;
      padding: 20px;
      color: #777;
    }
    .empty-state-icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    .notification-item {
      border-bottom: 1px solid #eee;
      padding: 10px 0;
      text-align: left;
    }
    .notification-item.unread {
      background: #f3f0ff;
      border-left: 4px solid #5b4bcc;
      padding-left: 10px;
    }
    .notification-time {
      font-size: 0.9rem;
      color: #888;
    }

    .btn-action-group {
      text-align: center;
      margin-top: 10px;
    }

    .btn {
      border-radius: 8px;
      padding: 8px 15px;
      font-weight: 600;
    }

    footer {
      margin-top: 40px;
      text-align: center;
      color: #eee;
    }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-4">
  <div class="dashboard-header">
    <h2>🎶 Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
    <span class="role-badge"><?= htmlspecialchars($role_name) ?></span>
  </div>

  <!-- STATS GRID -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🎵</div>
      <div class="stat-label">Total Songs</div>
      <div class="stat-value"><?= number_format($songCount) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👁️</div>
      <div class="stat-label">Total Views</div>
      <div class="stat-value"><?= number_format($viewCount) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">▶️</div>
      <div class="stat-label">Total Streams</div>
      <div class="stat-value"><?= number_format($streamCount) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔔</div>
      <div class="stat-label">Notifications</div>
      <div class="stat-value"><?= number_format($unreadNotifications) ?></div>
    </div>
  </div>

  <!-- Notifications -->
  <div class="content-card mb-4">
    <h5>🔔 Recent Notifications</h5>
    <?php if(empty($notifications)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <p>No notifications yet.</p>
      </div>
    <?php else: ?>
      <?php foreach($notifications as $n): ?>
        <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
          <div class="notification-message"><?= htmlspecialchars($n['message']) ?></div>
          <div class="notification-time"><?= date('M d, Y • g:i A', strtotime($n['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="btn-action-group">
        <a href="/harmony/notifications/list.php" class="btn btn-sm btn-primary">View All Notifications</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Music Table -->
  <div class="content-card">
    <h5>🎧 Latest Songs Uploaded</h5>
    <?php if(empty($music_list)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">🎼</div>
        <p>No music uploaded yet.</p>
      </div>
    <?php else: ?>
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Title</th>
            <th>Genre</th>
            <th>Views</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($music_list as $m): ?>
            <tr onclick="window.location='/harmony/music/music_details.php?id=<?= $m['id'] ?>';" style="cursor:pointer;">
              <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
              <td><?= htmlspecialchars($m['genre'] ?: '-') ?></td>
              <td><?= number_format((int)$m['views']) ?></td>
              <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<footer class="mt-5 text-center text-light">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
