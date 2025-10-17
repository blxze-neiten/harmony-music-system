<?php
require "../config/bootstrap.php";
require_login();
$user = current_user();

$requests = [];

// ✅ If Producer — show requests they SENT
if ($user['role'] === 'Producer') {
    $stmt = $pdo->prepare("
        SELECT r.id, u.name AS artist, m.title AS song, r.message, r.status, r.created_at
        FROM producer_requests r
        JOIN users u ON r.artist_id = u.id
        LEFT JOIN music m ON r.music_id = m.id
        WHERE r.producer_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ If Artist — show requests they RECEIVED
} elseif ($user['role'] === 'Artist') {
    $stmt = $pdo->prepare("
        SELECT r.id, u.name AS producer, m.title AS song, r.message, r.status, r.created_at
        FROM producer_requests r
        JOIN users u ON r.producer_id = u.id
        LEFT JOIN music m ON r.music_id = m.id
        WHERE r.artist_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "<div class='alert alert-danger text-center mt-5'>Access Denied ❌<br>You are not allowed to view this page.</div>";
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Harmony 🎶 | Collaboration Requests</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    body { background-color: #f0f2f5; }
    .card { border-radius: 12px; }
    .alert { border-radius: 10px; }
    .btn-success, .btn-danger { border-radius: 8px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🎵 Collaboration Requests</h2>

  <?php if (empty($requests)): ?>
    <div class="alert alert-info mt-4">No collaboration requests found.</div>
  <?php else: ?>
    <?php foreach ($requests as $r): ?>
      <div class="card mb-3 shadow-sm">
        <div class="card-body">
          <?php if ($user['role'] === 'Producer'): ?>
            <h5 class="card-title">🎤 Artist: <?= htmlspecialchars($r['artist']) ?></h5>
          <?php else: ?>
            <h5 class="card-title">🎧 Producer: <?= htmlspecialchars($r['producer']) ?></h5>
          <?php endif; ?>

          <?php if (!empty($r['song'])): ?>
            <p class="text-muted mb-1">🎵 Song: <?= htmlspecialchars($r['song']) ?></p>
          <?php endif; ?>

          <p class="card-text"><?= nl2br(htmlspecialchars($r['message'])) ?></p>
          <p>Status: 
            <span class="badge bg-<?=
              $r['status'] === 'pending' ? 'warning' : 
              ($r['status'] === 'accepted' ? 'success' : 'danger')
            ?>">
              <?= htmlspecialchars(ucfirst($r['status'])) ?>
            </span>
          </p>
          <small class="text-muted">Sent on <?= htmlspecialchars($r['created_at']) ?></small>

          <!-- If Artist, show Approve/Reject buttons -->
          <?php if ($user['role'] === 'Artist' && $r['status'] === 'pending'): ?>
            <form method="post" action="manage_action.php" class="mt-2">
              <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
              <button type="submit" name="action" value="accept" class="btn btn-success btn-sm">✅ Accept</button>
              <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
