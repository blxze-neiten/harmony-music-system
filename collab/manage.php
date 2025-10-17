<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

// Fetch artist’s collaboration requests
$stmt = $pdo->prepare("
  SELECT pr.*, u.name AS producer_name, m.title AS song_title
  FROM producer_requests pr
  JOIN users u ON pr.producer_id=u.id
  LEFT JOIN music m ON pr.music_id=m.id
  WHERE pr.artist_id=?
  ORDER BY pr.created_at DESC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎧 Manage Collaborations - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    body { background: #f8f9fc; }
    h2 { color: #6C63FF; font-weight: bold; }
    .card { border-radius: 12px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); }
    .badge { font-size: 0.9em; border-radius: 8px; }
    .btn-sm { border-radius: 8px; padding: 4px 12px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🎵 Manage Collaboration Requests</h2>
  <p class="text-muted">Approve or reject requests from producers who want to work with you.</p>

  <?php if (empty($requests)): ?>
    <div class="alert alert-info">No collaboration requests yet.</div>
  <?php else: ?>
    <?php foreach ($requests as $r): ?>
      <div class="card p-3 mb-3">
        <h5>Producer: <?= htmlspecialchars($r['producer_name']) ?></h5>
        <p><strong>Song:</strong> <?= $r['song_title'] ? htmlspecialchars($r['song_title']) : "No specific song" ?></p>
        <p><strong>Message:</strong><br><?= nl2br(htmlspecialchars($r['message'])) ?></p>
        <p><strong>Status:</strong>
          <?php if($r['status']=='pending'): ?>
            <span class="badge bg-warning text-dark">Pending</span>
          <?php elseif($r['status']=='accepted'): ?>
            <span class="badge bg-success">Accepted</span>
          <?php else: ?>
            <span class="badge bg-danger">Rejected</span>
          <?php endif; ?>
        </p>

        <?php if($r['status']=='pending'): ?>
          <form method="post" action="manage_action.php" class="mt-2 d-flex gap-2">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
            <button name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
