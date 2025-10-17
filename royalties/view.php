<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

$stmt = $pdo->prepare(query: "
  SELECT r.*, m.title, m.genre 
  FROM royalties r 
  JOIN music m ON r.music_id = m.id 
  WHERE m.artist_id = ? 
  ORDER BY r.created_at DESC
");
$stmt->execute(params: [$user['id']]);
$royalties = $stmt->fetchAll();

// Totals
$total_gross = array_sum(array_column($royalties, 'gross_amount'));
$total_streams = array_sum(array_column($royalties, 'streams_count'));
$total_paid = array_sum(array_map(fn($r) => $r['status'] === 'paid' ? $r['artist_share'] : 0, $royalties));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>💰 Royalties - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h2 class="text-gradient text-center mb-4">💰 Royalty Overview</h2>

  <div class="row text-center mb-4">
    <div class="col-md-4 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#89f7fe,#66a6ff);">
        <h4>Total Streams</h4>
        <h2><?= number_format($total_streams) ?></h2>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#f6d365,#fda085);">
        <h4>Total Earnings (KES)</h4>
        <h2><?= number_format($total_gross,2) ?></h2>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow border-0 p-3" style="background: linear-gradient(135deg,#84fab0,#8fd3f4);">
        <h4>Paid Out</h4>
        <h2><?= number_format($total_paid,2) ?></h2>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="card shadow border-0">
    <div class="card-body">
      <h4 class="mb-3 text-gradient">📊 Detailed Royalties</h4>
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Song Title</th>
            <th>Genre</th>
            <th>Streams</th>
            <th>Gross (KES)</th>
            <th>Artist Share (KES)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($royalties as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['title']) ?></td>
              <td><?= htmlspecialchars($r['genre']) ?></td>
              <td><?= number_format($r['streams_count']) ?></td>
              <td><?= number_format($r['gross_amount'], 2) ?></td>
              <td><?= number_format($r['artist_share'], 2) ?></td>
              <td>
                <span class="badge bg-<?= $r['status'] === 'paid' ? 'success' : 'warning' ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="mt-5">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
