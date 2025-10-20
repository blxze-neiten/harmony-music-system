<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Artist']);
$user = current_user();

// Approve or reject a collaboration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] === 'approve' ? 'accepted' : 'rejected';

    // validate request
    $stmt = $pdo->prepare("SELECT * FROM producer_collabs WHERE id = ? AND artist_id = ?");
    $stmt->execute([$id, $user['id']]);
    $collab = $stmt->fetch();

    if (!$collab) {
        flash('error', 'Invalid collaboration request.');
        header("Location: manage.php");
        exit;
    }

    $pdo->prepare("UPDATE producer_collabs SET status = ? WHERE id = ?")
        ->execute([$action, $id]);

    // Notify producer
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
        ->execute([$collab['producer_id'], "🎶 Your collaboration request was {$action} by {$user['name']}"]);

    flash('success', "Collaboration {$action} successfully!");
    header("Location: manage.php");
    exit;
}

// Fetch collaborations for this artist
$stmt = $pdo->prepare("
  SELECT pc.*, 
         u.name AS producer_name, 
         m.title AS song_title
  FROM producer_collabs pc
  JOIN users u ON pc.producer_id = u.id
  JOIN music m ON pc.music_id = m.id
  WHERE pc.artist_id = ?
  ORDER BY pc.created_at DESC
");
$stmt->execute([$user['id']]);
$collabs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎶 Manage Collaborations - Harmony</title>
  <link href="/harmony/assets/bootstrap.min.css" rel="stylesheet">
  <link href="/harmony/assets/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container mt-5">
  <h3 class="mb-4 text-primary">🤝 Collaboration Requests</h3>

  <?php if (empty($collabs)): ?>
    <div class="alert alert-info">No collaboration requests yet.</div>
  <?php else: ?>
    <?php foreach ($collabs as $r): ?>
      <div class="card p-4 mb-3 shadow-sm">
        <h5 class="fw-bold text-success"><?= htmlspecialchars($r['producer_name']) ?></h5>
        <p><strong>Song:</strong> <?= htmlspecialchars($r['song_title']) ?></p>
        <p><strong>Revenue Share:</strong> <?= htmlspecialchars($r['revenue_share']) ?>%</p>
        <p><strong>Status:</strong> 
          <span class="badge bg-<?= $r['status'] === 'accepted' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning') ?>">
            <?= htmlspecialchars($r['status']) ?>
          </span>
        </p>
        <?php if ($r['status'] === 'pending'): ?>
          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
            <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
