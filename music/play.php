<?php
require __DIR__ . '/../config/bootstrap.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT m.*, u.name AS artist FROM music m JOIN users u ON m.artist_id=u.id WHERE m.id = ?");
$s->execute([$id]);
$song = $s->fetch();
if (!$song) { echo "Song not found"; exit; }

// Record a stream (simple: one record per play)
$pdo->prepare("INSERT INTO streams (music_id,user_id) VALUES (?,?)")->execute([$id, $user['id']]);
$pdo->prepare("UPDATE music SET views = views + 1 WHERE id = ?")->execute([$id]);

// fetch comments
$comments = $pdo->prepare("SELECT c.*, u.name FROM comments c JOIN users u ON c.user_id=u.id WHERE c.music_id = ? ORDER BY c.created_at DESC");
$comments->execute([$id]);
$rows = $comments->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title><?= e($song['title']) ?> — Harmony</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css"></head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container mt-4">
  <div class="row">
    <div class="col-md-8">
      <h3><?= e($song['title']) ?></h3>
      <p class="text-muted">By <?= e($song['artist']) ?> • <?= e($song['genre']) ?></p>
      <audio controls style="width:100%;">
        <source src="/harmony/<?= e($song['file_path']) ?>">
        Your browser doesn't support audio.
      </audio>

      <hr>
      <h5>Comments</h5>
      <form method="post" action="/harmony/music/comment_post.php">
        <input type="hidden" name="music_id" value="<?= $id ?>">
        <textarea name="comment" class="form-control mb-2" required placeholder="Write a comment..."></textarea>
        <button class="btn btn-primary">Post Comment</button>
      </form>

      <div class="mt-4">
        <?php foreach($rows as $c): ?>
          <div class="card mb-2 p-2">
            <strong><?= e($c['name']) ?></strong> <small class="text-muted"><?= e($c['created_at']) ?></small>
            <p><?= e($c['comment']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
    <div class="col-md-4">
      <div class="card p-3">
        <h6>Stats</h6>
        <p>Views: <?= (int)$song['views'] ?></p>
        <p>Uploaded: <?= e($song['created_at']) ?></p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
