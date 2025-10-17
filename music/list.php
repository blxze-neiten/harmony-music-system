<?php
require "../config/bootstrap.php";
require_login();

$user = current_user();

// Fetch songs
if ($user['role'] === 'Artist') {
    // Artist sees only their own songs
    $stmt = $pdo->prepare("SELECT m.*, (SELECT COUNT(*) FROM streams s WHERE s.music_id=m.id) AS stream_count FROM music m WHERE artist_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
} else {
    // Users, Producers, Admins see all songs
    $stmt = $pdo->query("SELECT m.*, u.name AS artist_name, (SELECT COUNT(*) FROM streams s WHERE s.music_id=m.id) AS stream_count 
                         FROM music m 
                         JOIN users u ON m.artist_id = u.id 
                         ORDER BY m.created_at DESC");
}
$music_list = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎵 Music Library - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>🎶 Music Library</h2>
    <?php if ($user['role'] === 'Artist'): ?>
      <a href="upload.php" class="btn btn-primary">➕ Upload New Song</a>
    <?php endif; ?>
  </div>

  <div class="card shadow-sm p-4">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-primary">
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Genre</th>
          <th>Artist</th>
          <th>Streams</th>
          <th>Uploaded</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($music_list) > 0): ?>
          <?php $i = 1; foreach ($music_list as $m): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($m['title']) ?></td>
              <td><span class="badge bg-info text-dark"><?= htmlspecialchars($m['genre']) ?></span></td>
              <td><?= isset($m['artist_name']) ? htmlspecialchars($m['artist_name']) : htmlspecialchars($user['name']) ?></td>
              <td><?= $m['stream_count'] ?></td>
              <td><?= date("M d, Y", strtotime($m['created_at'])) ?></td>
              <td>
                <a href="play.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-success">▶ Play</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted">No songs found yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-5">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
