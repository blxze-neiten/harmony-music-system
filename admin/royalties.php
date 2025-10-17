<?php 
require "../config/bootstrap.php"; require_roles(['Admin']);

// Mark as paid
if(isset($_GET['pay'])){
  $id=$_GET['pay'];
  $pdo->prepare("UPDATE royalties SET status='paid' WHERE id=?")->execute([$id]);
}

// Fetch all royalties
$rows=$pdo->query("
  SELECT r.*, m.title, u.name as artist
  FROM royalties r
  JOIN music m ON r.music_id=m.id
  JOIN users u ON m.artist_id=u.id
  ORDER BY r.period_start DESC
")->fetchAll();
?>
<!doctype html><html><head>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="container mt-5">
  <h2>🛡️ Manage Royalties</h2>
  <table class="table table-striped">
    <tr><th>Song</th><th>Artist</th><th>Period</th><th>Streams</th><th>Amount</th><th>Status</th><th>Action</th></tr>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['artist']) ?></td>
        <td><?= $r['period_start'] ?> → <?= $r['period_end'] ?></td>
        <td><?= $r['streams_count'] ?></td>
        <td><?= number_format($r['artist_share'],2) ?></td>
        <td><?= $r['status'] ?></td>
        <td>
          <?php if($r['status']=='pending'): ?>
            <a href="?pay=<?= $r['id'] ?>" class="btn btn-success btn-sm">Mark Paid</a>
          <?php else: ?>
            <span class="badge bg-success">Paid</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body></html>
