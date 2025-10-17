<?php
require "../config/bootstrap.php";
require_roles(['Producer']);
$user = current_user();

// Fetch collaboration requests for this producer
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.message, u.name AS artist, r.status, r.created_at
        FROM producer_requests r
        JOIN users u ON r.artist_id = u.id
        WHERE r.producer_id = :producer_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute(['producer_id' => $user['id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching requests: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Harmony 🎶 | My Collaboration Requests</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f0f2f5; }
    h2 { font-weight: 700; margin-bottom: 20px; }
    .card { border-radius: 12px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🎵 My Collaboration Requests</h2>

  <?php if (empty($requests)): ?>
    <div class="alert alert-info">You have no collaboration requests yet.</div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($requests as $r): ?>
        <div class="col-md-6">
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($r['artist']) ?></h5>
              <p class="card-text"><?= nl2br(htmlspecialchars($r['message'])) ?></p>
              <p class="card-text">Status: <?= htmlspecialchars($r['status']) ?></p>
              <small class="text-muted">Sent on: <?= htmlspecialchars($r['created_at']) ?></small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
