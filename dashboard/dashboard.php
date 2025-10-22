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

$totalStreams = $pdo->prepare("SELECT COUNT(*) FROM streams s JOIN music m ON s.music_id = m.id WHERE m.artist_id = ?");
$totalStreams->execute([$user['id']]);
$streamCount = $totalStreams->fetchColumn();

$unreadCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadCount->execute([$user['id']]);
$unreadNotifications = $unreadCount->fetchColumn();

// Fetch latest songs
$songs = $pdo->prepare("SELECT id, title, genre, views, created_at FROM music WHERE artist_id = ? ORDER BY created_at DESC LIMIT 5");
$songs->execute([$user['id']]);
$music_list = $songs->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest notifications
$notes = $pdo->prepare("SELECT message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notes->execute([$user['id']]);
$notifications = $notes->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container">
  <div class="dashboard-header">
    <h2>🎶 Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
    <span class="role-badge"><?= htmlspecialchars($role_name) ?></span>
  </div>

  <!-- STATISTICS GRID -->
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

  <div class="row g-4">
    <!-- Notifications -->
    <div class="col-lg-6">
      <div class="content-card">
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
    </div>

    <!-- Reports -->
    <div class="col-lg-6">
      <div class="content-card">
        <h5>📊 Reports & Analytics</h5>
        <div class="chart-container text-center">
          <canvas id="reportChart"></canvas>
        </div>
        <div class="btn-action-group">
          <a href="/harmony/reports/view.php" class="btn btn-sm btn-success">View Full Reports</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Music Table (Library Shortcut) -->
  <div class="content-card mt-4">
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
            <tr onclick="window.location='/harmony/music/list.php';" style="cursor:pointer;">
              <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
              <td><?= htmlspecialchars($m['genre'] ?: '-') ?></td>
              <td><?= number_format((int)$m['views']) ?></td>
              <td><?= date('M d', strtotime($m['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
<script src="../assets/chart.min.js"></script>
<script>
new Chart(document.getElementById('reportChart'), {
  type: 'bar',
  data: {
    labels: ['Songs', 'Views', 'Streams', 'Notifications'],
    datasets: [{
      label: 'Artist Stats',
      data: [<?= $songCount ?>, <?= $viewCount ?>, <?= $streamCount ?>, <?= $unreadNotifications ?>],
      backgroundColor: ['#667eea', '#764ba2', '#28a745', '#ffc107']
    }]
  }
});
</script>
</body>
</html>
