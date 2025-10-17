<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Artist']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'reject';
    $status = $action === 'accept' ? 'accepted' : 'rejected';
    $pdo->prepare("UPDATE producer_requests SET status=? WHERE id=? AND artist_id=?")->execute([$status,$id,$_SESSION['user']['id']]);
    $r = $pdo->prepare("SELECT producer_id FROM producer_requests WHERE id=?"); $r->execute([$id]); $row = $r->fetch();
    if ($row) $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")->execute([$row['producer_id'], "Your producer request was $status"]);
    header("Location: manage.php"); exit;
}

$stmt = $pdo->prepare("SELECT pr.*, u.name as producer_name, m.title as music_title FROM producer_requests pr JOIN users u ON pr.producer_id=u.id LEFT JOIN music m ON m.id=pr.music_id WHERE pr.artist_id = ? ORDER BY pr.created_at DESC");
$stmt->execute([$_SESSION['user']['id']]); $rows = $stmt->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Producer Requests</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container-pad">
<div class="container">
  <h3>Producer Requests</h3>
  <?php foreach($rows as $r): ?>
    <div class="card p-3 mb-2">
      From <?= htmlspecialchars($r['producer_name']) ?> — <?= htmlspecialchars($r['music_title'] ?? 'N/A') ?><br>
      <?= nl2br(htmlspecialchars($r['message'])) ?><br>
      Status: <?= htmlspecialchars($r['status']) ?>
      <?php if($r['status']=='pending'): ?>
        <form method="post" class="mt-2">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button name="action" value="accept" class="btn btn-success btn-sm">Accept</button>
          <button name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
</body></html>
