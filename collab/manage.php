<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action'];
    $status = $action === 'approve' ? 'accepted' : 'rejected';

    $pdo->prepare("UPDATE producer_requests SET status=? WHERE id=?")->execute([$status, $id]);

    // Fetch producer info
    $row = $pdo->prepare("SELECT producer_id FROM producer_requests WHERE id=?");
    $row->execute([$id]);
    $producer = $row->fetch();

    if ($producer) {
        $msg = "Your collaboration request was $status by {$user['name']}.";
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$producer['producer_id'], $msg]);
    }

    // If approved, insert into producer_collabs
    if ($status === 'accepted') {
        $data = $pdo->prepare("SELECT * FROM producer_requests WHERE id=?");
        $data->execute([$id]);
        $req = $data->fetch();
        $pdo->prepare("INSERT INTO producer_collabs (music_id, artist_id, producer_id) VALUES (?,?,?)")
            ->execute([$req['music_id'], $req['artist_id'], $req['producer_id']]);
    }

    header("Location: manage.php");
    exit;
}

// Fetch artist’s collab requests
$stmt = $pdo->prepare("
  SELECT pr.*, u.name AS producer_name, m.title AS song_title
  FROM producer_requests pr
  JOIN users u ON pr.producer_id=u.id
  LEFT JOIN music m ON pr.music_id=m.id
  WHERE pr.artist_id=?
  ORDER BY pr.created_at DESC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Collaborations - Harmony</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2>🎶 Collaboration Requests</h2>
  <p class="text-muted">Review and respond to collaboration requests from producers.</p>

  <?php foreach($requests as $r): ?>
    <div class="card p-3 mb-3">
      <h5>Producer: <?= htmlspecialchars($r['producer_name']) ?></h5>
      <p><?= nl2br(htmlspecialchars($r['message'])) ?></p>
      <p><strong>Song:</strong> <?= $r['song_title'] ? htmlspecialchars($r['song_title']) : "No specific song" ?></p>
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
        <form method="post" class="mt-2">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
          <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if(count($requests)==0): ?>
    <p class="text-muted text-center mt-4">No collaboration requests yet.</p>
  <?php endif; ?>
</div>
</body>
</html>
