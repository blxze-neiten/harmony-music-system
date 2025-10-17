<?php
require "../config/bootstrap.php";
require_roles(['User','Producer']);
$user = current_user();
$msg = "";

// fetch songs list for selection
$songs = $pdo->query("SELECT m.id, m.title, u.name AS artist FROM music m JOIN users u ON m.artist_id=u.id ORDER BY m.created_at DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $music_id = (int)($_POST['music_id'] ?? 0);
    $usage = trim($_POST['usage_description'] ?? '');
    $fee = (float)($_POST['fee_offered'] ?? 0);

    if ($music_id && $usage) {
        $stmt = $pdo->prepare("INSERT INTO licensing_requests (music_id, requester_id, usage_description, fee_offered) VALUES (?, ?, ?, ?)");
        $stmt->execute([$music_id, $user['id'], $usage, $fee]);

        // notify the artist
        $artistRow = $pdo->prepare("SELECT artist_id, title FROM music WHERE id=?");
        $artistRow->execute([$music_id]);
        $a = $artistRow->fetch();
        if ($a) {
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$a['artist_id'], "📜 New licensing request for '{$a['title']}' from {$user['name']}"]);
        }

        $msg = "✅ License request submitted successfully.";
    } else {
        $msg = "❌ Please select a song and describe your intended usage.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Request Licensing — Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card p-4 shadow-sm">
    <h3>📜 Request a License</h3>
    <?php if ($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Select Song</label>
        <select name="music_id" class="form-select" required>
          <option value="">-- Choose a song --</option>
          <?php foreach ($songs as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?> — <?= htmlspecialchars($s['artist']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Usage Description</label>
        <textarea name="usage_description" class="form-control" rows="4" required placeholder="Describe how you will use the track..."></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Offered Fee (KES)</label>
        <input type="number" step="0.01" name="fee_offered" class="form-control" value="0">
      </div>

      <button class="btn btn-primary">Send Request</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
