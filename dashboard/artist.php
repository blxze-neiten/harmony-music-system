<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

// 🎵 Fetch artist songs
$stmt = $pdo->prepare("
  SELECT m.*, 
    (SELECT COUNT(*) FROM streams s WHERE s.music_id = m.id) AS total_streams,
    (SELECT COUNT(*) FROM comments c WHERE c.music_id = m.id) AS total_comments
  FROM music m
  WHERE m.artist_id = ?
  ORDER BY m.created_at DESC
");
$stmt->execute([$user['id']]);
$songs = $stmt->fetchAll();

// 💰 Calculate total streams & earnings
$total_streams = 0;
foreach ($songs as $s) { $total_streams += $s['total_streams']; }
$earning_per_stream = 0.50; // Ksh per stream
$total_earnings = $total_streams * $earning_per_stream;

// 🔔 Get recent notifications
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute([$user['id']]);
$notifications = $notif_stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎵 Artist Dashboard - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">

  <!-- Welcome Section -->
  <div class="text-center mb-5">
    <h2 class="fw-bold text-gradient">🎶 Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
    <p class="lead">Here’s your Harmony Music dashboard — track your performance, earnings, and audience feedback 🌟</p>
  </div>

  <!-- Stats Overview -->
  <div class="row text-center mb-4">
    <div class="col-md-3 mb-3">
      <div class="card shadow border-0 p-3 bg-gradient" style="background: linear-gradient(135deg,#a18cd1,#fbc2eb);">
        <h4>Total Songs</h4>
        <h2><?= count($songs) ?></h2>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#f6d365,#fda085);">
        <h4>Total Streams</h4>
        <h2><?= number_format($total_streams) ?></h2>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#89f7fe,#66a6ff);">
        <h4>Total Comments</h4>
        <h2>
          <?= array_sum(array_column($songs, 'total_comments')) ?>
        </h2>
      </div>
    </div>

    <div class="col-md-3 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#84fab0,#8fd3f4);">
        <h4>Total Earnings (Ksh)</h4>
        <h2><?= number_format($total_earnings, 2) ?></h2>
      </div>
    </div>
  </div>

  <!-- Uploaded Songs Section -->
  <h3 class="text-gradient mt-5 mb-3">🎵 Your Uploaded Songs</h3>
  <?php if ($songs): ?>
    <div class="row">
      <?php foreach ($songs as $song): ?>
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
              <h5 class="fw-bold"><?= htmlspecialchars($song['title']) ?></h5>
              <p class="text-muted"><em>Genre: <?= htmlspecialchars($song['genre']) ?></em></p>
              <audio controls class="w-100 mb-2">
                <source src="../uploads/<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
              </audio>
              <p>👀 <?= $song['total_streams'] ?> streams | 💬 <?= $song['total_comments'] ?> comments</p>
              <a href="../music/play.php?id=<?= $song['id'] ?>" class="btn btn-primary btn-sm">View & Manage</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-muted text-center">You haven’t uploaded any songs yet. <a href="../music/upload.php" class="text-gradient fw-bold">Upload one now!</a></p>
  <?php endif; ?>

  <!-- Notifications Section -->
  <h3 class="text-gradient mt-5 mb-3">🔔 Recent Notifications</h3>
  <?php if ($notifications): ?>
    <ul class="list-group">
      <?php foreach ($notifications as $n): ?>
        <li class="list-group-item"><?= htmlspecialchars($n['message']) ?> <small class="text-muted float-end"><?= $n['created_at'] ?></small></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-muted">No notifications yet.</p>
  <?php endif; ?>

</div>

<footer class="mt-5">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
