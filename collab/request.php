<?php
require "../config/bootstrap.php";
require_roles(['Producer']);
$user = current_user();

$success = $error = "";

// Fetch artists & songs
$artists = $pdo->query("
  SELECT id, name FROM users 
  WHERE role_id = (SELECT id FROM roles WHERE name='Artist')
  ORDER BY name
")->fetchAll();

$songs = $pdo->query("
  SELECT m.id, m.title, u.name AS artist 
  FROM music m 
  JOIN users u ON m.artist_id=u.id 
  ORDER BY m.created_at DESC
")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $artist_id = (int)($_POST['artist_id'] ?? 0);
  $music_id = (int)($_POST['music_id'] ?? 0);
  $message = trim($_POST['message'] ?? '');

  if (!$artist_id || !$message) {
    $error = "❌ Please select an artist and write a collaboration message.";
  } else {
    // ✅ 1. Create new collaboration
    $stmt = $pdo->prepare("
      INSERT INTO producer_collabs (producer_id, artist_id, music_id)
      VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $artist_id, $music_id ?: 0]);
    $collab_id = $pdo->lastInsertId();

    // ✅ 2. Store message in collab_messages
    $stmt2 = $pdo->prepare("
      INSERT INTO collab_messages (collab_id, sender_id, message)
      VALUES (?, ?, ?)
    ");
    $stmt2->execute([$collab_id, $user['id'], $message]);

    // ✅ 3. Notify artist
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notify->execute([$artist_id, "🎶 New collaboration request from {$user['name']}"]);

    $success = "✅ Collaboration request sent successfully!";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🤝 Collaboration Request — Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card p-4 shadow-lg border-0" style="border-radius: 15px;">
    <h3 class="mb-2" style="color:#764ba2;">🤝 Send Collaboration Request</h3>
    <p class="text-muted mb-3">Work with artists and bring your creative ideas to life.</p>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Select Artist</label>
        <select name="artist_id" class="form-select" required>
          <option value="">-- Choose an Artist --</option>
          <?php foreach ($artists as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Select Song (optional)</label>
        <select name="music_id" class="form-select">
          <option value="">-- None --</option>
          <?php foreach ($songs as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?> — <?= htmlspecialchars($s['artist']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Message to Artist</label>
        <textarea name="message" class="form-control" rows="4" required placeholder="Describe your collaboration idea..."></textarea>
      </div>

      <button class="btn btn-primary w-100" style="background: linear-gradient(135deg,#667eea,#764ba2); border:none;">
        🚀 Send Request
      </button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
