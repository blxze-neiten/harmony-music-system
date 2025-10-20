<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

// Fetch role name
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$user['role_id']]);
$role_name = $stmt->fetchColumn();

// Fetch latest songs
$songs = $pdo->prepare("SELECT title, genre, views, created_at FROM music WHERE artist_id = ? ORDER BY created_at DESC LIMIT 5");
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
  <title>Dashboard - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2 class="text-primary mb-3">🎶 Welcome, <?= htmlspecialchars($user['name']) ?></h2>
  <p class="text-muted">Role: <strong><?= htmlspecialchars($role_name) ?></strong></p>

  <div class="row g-4">
    <!-- Notifications -->
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <h5>🔔 Recent Notifications</h5>
        <?php if(empty($notifications)): ?>
          <p class="text-muted">No notifications yet.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach($notifications as $n): ?>
              <li class="list-group-item d-flex justify-content-between">
                <span><?= htmlspecialchars($n['message']) ?></span>
                <small class="text-muted"><?= date('M d, H:i', strtotime($n['created_at'])) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="mt-2 text-end">
            <a href="/harmony/notifications/list.php" class="btn btn-sm btn-outline-primary">View all</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Music -->
    <div class="col-md-6">
      <div class="card p-3 shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
          <h5>🎧 Your Latest Songs</h5>
          <a href="/harmony/music/upload.php" class="btn btn-sm btn-primary">+ Upload</a>
        </div>
        <?php if(empty($music_list)): ?>
          <p class="text-muted">No music uploaded yet.</p>
        <?php else: ?>
          <table class="table table-sm mt-2">
            <thead><tr><th>Title</th><th>Genre</th><th>Views</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach($music_list as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['title']) ?></td>
                  <td><?= htmlspecialchars($m['genre'] ?: '-') ?></td>
                  <td><?= (int)$m['views'] ?></td>
                  <td><?= date('M d', strtotime($m['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="text-end">
            <a href="/harmony/music/list.php" class="btn btn-sm btn-outline-success">View Library</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
