<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Producer']);
$user = current_user();

$artists = $pdo->query("SELECT id,name FROM users WHERE role_id = (SELECT id FROM roles WHERE name='Artist') ORDER BY name")->fetchAll();
$songs = $pdo->query("SELECT m.id, m.title, u.name AS artist FROM music m JOIN users u ON m.artist_id=u.id ORDER BY m.created_at DESC")->fetchAll();

$success = $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artist_id = (int)($_POST['artist_id'] ?? 0);
    $music_id = (int)($_POST['music_id'] ?: 0);
    $message = trim($_POST['message'] ?? '');
    if (!$artist_id || !$message) $error = "Select artist and write a message.";
    else {
        $pdo->prepare("INSERT INTO producer_requests (producer_id, artist_id, music_id, message) VALUES (?,?,?,?)")
            ->execute([$user['id'], $artist_id, $music_id ?: null, $message]);
        $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
            ->execute([$artist_id, "🎧 New collaboration request from {$user['name']}"]);
        $success = "Request sent.";
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Send Collaboration Request</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css"></head>
<body><?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container mt-4">
  <h3>Send Collaboration Request</h3>
  <?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="card p-3">
    <label>Artist</label>
    <select name="artist_id" class="form-select mb-2" required>
      <option value="">Select artist</option>
      <?php foreach($artists as $a): ?>
        <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Song (optional)</label>
    <select name="music_id" class="form-select mb-2">
      <option value="">None</option>
      <?php foreach($songs as $s): ?>
        <option value="<?= $s['id'] ?>"><?= e($s['title']) ?> (<?= e($s['artist']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <label>Message</label>
    <textarea name="message" class="form-control mb-2" required></textarea>
    <button class="btn btn-primary">Send Request</button>
  </form>
</div></body></html>
