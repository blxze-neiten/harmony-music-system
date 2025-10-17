<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] ?? 'reject';
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // update request status
    $pdo->prepare("UPDATE licensing_requests SET status=? WHERE id=?")->execute([$status, $id]);

    // get requester id and music title
    $r = $pdo->prepare("SELECT lr.requester_id, m.title FROM licensing_requests lr JOIN music m ON lr.music_id=m.id WHERE lr.id=?");
    $r->execute([$id]); $row = $r->fetch();
    if ($row) {
        // notify requester
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$row['requester_id'], "📜 Your licensing request for '{$row['title']}' has been {$status}."]);
    }

    header("Location: manage.php");
    exit;
}

// Fetch licensing requests for songs that belong to this artist
$sql = "
  SELECT lr.*, m.title, u.name AS requester_name
  FROM licensing_requests lr
  JOIN music m ON lr.music_id=m.id
  JOIN users u ON lr.requester_id=u.id
  WHERE m.artist_id = ?
  ORDER BY lr.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Licensing — Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h3>📜 Licensing Requests</h3>

  <?php if (count($rows) === 0): ?>
    <div class="alert alert-secondary">No licensing requests at the moment.</div>
  <?php else: ?>
    <?php foreach ($rows as $r): ?>
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between">
          <div>
            <strong><?= htmlspecialchars($r['requester_name']) ?></strong> requested a license for <em><?= htmlspecialchars($r['title']) ?></em>
            <div class="text-muted mt-1">Usage: <?= nl2br(htmlspecialchars($r['usage_description'])) ?></div>
            <div class="mt-1"><strong>Fee offered:</strong> Ksh <?= number_format($r['fee_offered'],2) ?></div>
          </div>
          <div class="text-end">
            <div>Status: 
              <?php if ($r['status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">Pending</span>
              <?php elseif ($r['status'] === 'approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php else: ?>
                <span class="badge bg-danger">Rejected</span>
              <?php endif; ?>
            </div>

            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" class="mt-2">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
