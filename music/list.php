<?php
require "../config/bootstrap.php";
require_login();

$music = $pdo->query("
  SELECT m.*, u.name AS artist_name
  FROM music m
  JOIN users u ON m.artist_id = u.id
  ORDER BY m.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>🎧 Music Library</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<div class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
  <h3>🎵 Music Library</h3>
  <?php if($user['role_id'] == 2): // artist ?>
    <a href="/harmony/music/upload.php" class="btn btn-primary">+ Upload New Song</a>
  <?php endif; ?>
</div>

  <div class="row">
    <?php foreach($music as $m): ?>
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm p-3">
        <h5><?= htmlspecialchars($m['title']) ?></h5>
        <p class="text-muted">By <?= htmlspecialchars($m['artist_name']) ?></p>
        <audio controls class="w-100">
          <source src="../uploads/<?= htmlspecialchars($m['file_path']) ?>" type="audio/mpeg">
        </audio>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
