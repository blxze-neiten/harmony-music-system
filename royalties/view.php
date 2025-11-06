<?php
require "../config/bootstrap.php";
require_roles(['Artist', 'Producer']);
$user = current_user();

// Identify user role properly
try {
    $role_stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $role_stmt->execute([$user['role_id']]);
    $role_name = $role_stmt->fetchColumn();
    $is_artist = $role_name === 'Artist';
    $is_producer = $role_name === 'Producer';
} catch (PDOException $e) {
    error_log("Role check error: " . $e->getMessage());
    die("Error identifying user role.");
}

// ✅ FETCH ROYALTIES (Fully corrected – No streams table dependency)
try {
    if ($is_artist) {
        $stmt = $pdo->prepare("
            SELECT 
                m.id AS music_id,
                m.title,
                m.genre,
                COALESCE(r.streams_count, 0) AS streams_count,
                COALESCE(r.gross_amount, 0) AS gross_amount,
                COALESCE(r.artist_share, 0) AS artist_share,
                COALESCE(r.status, 'pending') AS status
            FROM music m
            LEFT JOIN royalties r ON r.music_id = m.id
            WHERE m.artist_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user['id']]);

    } elseif ($is_producer) {
        $stmt = $pdo->prepare("
            SELECT 
                m.id AS music_id,
                m.title,
                m.genre,
                COALESCE(r.streams_count, 0) AS streams_count,
                COALESCE(r.gross_amount, 0) AS gross_amount,
                COALESCE(pc.revenue_share, 30) AS revenue_share,
                ROUND((COALESCE(r.gross_amount, 0) * (COALESCE(pc.revenue_share, 30) / 100)), 2) AS producer_earnings,
                COALESCE(r.status, 'pending') AS status
            FROM music m
            JOIN producer_collabs pc ON m.id = pc.music_id
            LEFT JOIN royalties r ON r.music_id = m.id
            WHERE pc.producer_id = ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user['id']]);
    } else {
        die("Access denied.");
    }

    $royalties = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Royalties fetch error: " . $e->getMessage());
    $royalties = [];
}

// ✅ Calculate Totals
$total_streams = array_sum(array_column($royalties, 'streams_count'));
$total_gross   = array_sum(array_column($royalties, 'gross_amount'));
$total_paid    = 0;
$total_pending = 0;

foreach ($royalties as $r) {
    $value = $is_artist ? (float)$r['artist_share'] : (float)($r['producer_earnings'] ?? 0);
    ($r['status'] === 'paid') ? $total_paid += $value : $total_pending += $value;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Royalties Overview - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #667eea, #764ba2); font-family: 'Segoe UI', sans-serif; padding-top: 80px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .text-gradient { background: linear-gradient(135deg, #fff, #f0e6ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  </style>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">

  <h2 class="text-gradient text-center mb-4">
    <?= $is_artist ? '🎤 Artist' : '🎧 Producer' ?> Royalties Overview
  </h2>

  <!-- Summary Cards -->
  <div class="row text-center mb-4">
    <div class="col-md-3"><div class="card p-3" style="background:#8FD3F4;"><h5>Total Streams</h5><h2><?= number_format($total_streams) ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3" style="background:#F6D365;"><h5>Total Gross (KES)</h5><h2><?= number_format($total_gross,2) ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3" style="background:#84FAB0;"><h5>Total Paid</h5><h2><?= number_format($total_paid,2) ?></h2></div></div>
    <div class="col-md-3"><div class="card p-3" style="background:#FCCB90;"><h5>Pending</h5><h2><?= number_format($total_pending,2) ?></h2></div></div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-body">
      <h4 class="text-gradient mb-3">📊 Detailed Royalties</h4>
      <table class="table table-striped table-hover align-middle">
        <thead><tr>
          <th>Song Title</th><th>Genre</th><th>Streams</th><th>Gross (KES)</th>
          <?php if ($is_artist): ?><th>Artist Share (KES)</th><?php else: ?><th>Producer Earnings (KES)</th><th>Revenue Share (%)</th><?php endif; ?>
          <th>Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$royalties): ?>
          <tr><td colspan="7" class="text-center text-muted">No royalty data yet.</td></tr>
        <?php else: foreach ($royalties as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['genre'] ?? '-') ?></td>
            <td><?= number_format($r['streams_count']) ?></td>
            <td><?= number_format($r['gross_amount'],2) ?></td>
            <?php if ($is_artist): ?>
              <td><?= number_format($r['artist_share'],2) ?></td>
            <?php else: ?>
              <td><?= number_format($r['producer_earnings'],2) ?></td>
              <td><?= htmlspecialchars($r['revenue_share']) ?>%</td>
            <?php endif; ?>
            <td><span class="badge bg-<?= $r['status']==='paid'?'success':'warning' ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>
