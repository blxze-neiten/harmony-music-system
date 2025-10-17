<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

$artist_id = $user['id'];

// --- Handle Approve / Reject actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $status = $action === 'approve' ? 'accepted' : 'rejected';

    // Fetch the request to validate ownership
    $stmt = $pdo->prepare("SELECT * FROM producer_requests WHERE id=? AND artist_id=?");
    $stmt->execute([$id, $artist_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        // Update status
        $pdo->prepare("UPDATE producer_requests SET status=? WHERE id=?")->execute([$status, $id]);

        // Notify producer
        $msg = "🎧 Your collaboration request for artist {$user['name']} was {$status}.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$req['producer_id'], $msg]);

        // Add to producer_collabs if accepted
        if ($status === 'accepted') {
            $pdo->prepare("
                INSERT INTO producer_collabs (music_id, artist_id, producer_id)
                VALUES (?, ?, ?)
            ")->execute([$req['music_id'], $req['artist_id'], $req['producer_id']]);
        }

        $alert = "✅ Request {$status} successfully!";
    } else {
        $alert = "⚠️ Invalid or unauthorized action.";
    }
}

// --- Fetch Summary Stats ---
function count_status($pdo, $artist_id, $status = null) {
    $sql = "SELECT COUNT(*) FROM producer_requests WHERE artist_id=?";
    $params = [$artist_id];
    if ($status) {
        $sql .= " AND status=?";
        $params[] = $status;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$total = count_status($pdo, $artist_id);
$pending_count = count_status($pdo, $artist_id, 'pending');
$accepted_count = count_status($pdo, $artist_id, 'accepted');
$rejected_count = count_status($pdo, $artist_id, 'rejected');

// --- Fetch All Requests ---
$stmt = $pdo->prepare("
  SELECT pr.*, u.name AS producer_name, m.title AS song_title
  FROM producer_requests pr
  JOIN users u ON pr.producer_id = u.id
  LEFT JOIN music m ON pr.music_id = m.id
  WHERE pr.artist_id = ?
  ORDER BY pr.created_at DESC
");
$stmt->execute([$artist_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>🎶 Collaboration Summary - Harmony</title>
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    body { background: #f7f8ff; font-family: 'Segoe UI', Roboto, sans-serif; }
    h2 { color: #6C63FF; font-weight: 700; }
    .card { border-radius: 14px; transition: .3s; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
    .table { background: white; border-radius: 10px; overflow: hidden; }
    th { background-color: #6C63FF; color: white; }
    td, th { vertical-align: middle !important; }
    .badge { font-size: 0.9em; padding: 6px 10px; border-radius: 6px; }
    .btn-sm { border-radius: 8px; padding: 4px 10px; }
    .alert-custom { border-radius: 10px; margin-bottom: 15px; }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🤝 Collaboration Summary</h2>
  <p class="text-muted">Manage and track your collaboration requests with producers.</p>

  <?php if (isset($alert)): ?>
    <div class="alert alert-info alert-custom"><?= htmlspecialchars($alert) ?></div>
  <?php endif; ?>

  <!-- Summary Cards -->
  <div class="row mt-4 g-3">
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5 class="text-muted">Total Requests</h5>
        <h2 class="text-primary"><?= $total ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5 class="text-muted">Pending</h5>
        <h2 class="text-warning"><?= $pending_count ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5 class="text-muted">Accepted</h5>
        <h2 class="text-success"><?= $accepted_count ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5 class="text-muted">Rejected</h5>
        <h2 class="text-danger"><?= $rejected_count ?></h2>
      </div>
    </div>
  </div>

  <!-- Collaboration Requests -->
  <div class="mt-5">
    <h4 class="mb-3">📋 Collaboration Requests</h4>
    <?php if (empty($requests)): ?>
      <div class="alert alert-info">You have no collaboration requests yet.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Producer</th>
              <th>Song</th>
              <th>Message</th>
              <th>Status</th>
              <th>Action</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['producer_name']) ?></td>
                <td><?= $r['song_title'] ? htmlspecialchars($r['song_title']) : '<em>Not specified</em>' ?></td>
                <td><?= nl2br(htmlspecialchars($r['message'])) ?></td>
                <td>
                  <?php if ($r['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending</span>
                  <?php elseif ($r['status'] === 'accepted'): ?>
                    <span class="badge bg-success">Accepted</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Rejected</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($r['status'] === 'pending'): ?>
                    <form method="post" class="d-flex gap-1">
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
                      <button name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?= date('M d, Y H:i', strtotime($r['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>
