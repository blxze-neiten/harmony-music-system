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

// Fetch royalties with live stream data fallback
try {
    if ($is_artist) {
        $stmt = $pdo->prepare("
            SELECT 
                m.id AS music_id,
                m.title, 
                m.genre,
                COALESCE(r.streams_count, COUNT(s.id)) AS streams_count,
                COALESCE(r.gross_amount, COUNT(s.id) * 0.5) AS gross_amount,  -- e.g. 0.5 KES per stream
                COALESCE(r.artist_share, (COUNT(s.id) * 0.5) * 0.7) AS artist_share, -- 70% artist share
                COALESCE(r.status, 'pending') AS status
            FROM music m
            LEFT JOIN royalties r ON r.music_id = m.id
            LEFT JOIN streams s ON s.music_id = m.id
            WHERE m.artist_id = ?
            GROUP BY m.id, m.title, m.genre, r.streams_count, r.gross_amount, r.artist_share, r.status
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user['id']]);
    } elseif ($is_producer) {
        $stmt = $pdo->prepare("
            SELECT 
                m.id AS music_id,
                m.title, 
                m.genre,
                COALESCE(r.streams_count, COUNT(s.id)) AS streams_count,
                COALESCE(r.gross_amount, COUNT(s.id) * 0.5) AS gross_amount,
                pc.revenue_share,
                ROUND((COALESCE(r.gross_amount, COUNT(s.id) * 0.5) * (pc.revenue_share / 100)), 2) AS producer_earnings,
                COALESCE(r.status, 'pending') AS status
            FROM music m
            LEFT JOIN royalties r ON r.music_id = m.id
            LEFT JOIN streams s ON s.music_id = m.id
            JOIN producer_collabs pc ON m.id = pc.music_id
            WHERE pc.producer_id = ?
            GROUP BY m.id, m.title, m.genre, pc.revenue_share, r.streams_count, r.gross_amount, r.status
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

// Calculate totals safely
$total_streams = array_sum(array_map(fn($r) => (float)$r['streams_count'], $royalties));
$total_gross   = array_sum(array_map(fn($r) => (float)$r['gross_amount'], $royalties));
$total_paid    = 0;
$total_pending = 0;

if ($is_artist) {
    foreach ($royalties as $r) {
        $share = (float)$r['artist_share'];
        if ($r['status'] === 'paid') $total_paid += $share;
        else $total_pending += $share;
    }
} elseif ($is_producer) {
    foreach ($royalties as $r) {
        $earn = (float)($r['producer_earnings'] ?? 0);
        if ($r['status'] === 'paid') $total_paid += $earn;
        else $total_pending += $earn;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>💰 Royalties - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      font-family: 'Segoe UI', sans-serif;
      color: #333;
      padding-top: 80px;
    }
    .text-gradient {
      background: linear-gradient(135deg, #667eea, #d5c7e2ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .btn-primary {
      background: linear-gradient(135deg,#667eea,#764ba2);
      border: none;
      border-radius: 10px;
      padding: 12px 20px;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102,126,234,0.4);
    }
    footer {
      margin-top: 40px;
      color: #eee;
    }
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
    <div class="col-md-3 mb-3">
      <div class="card p-3" style="background: linear-gradient(135deg,#89f7fe,#66a6ff);">
        <h5>Total Streams</h5>
        <h2><?= number_format($total_streams) ?></h2>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3" style="background: linear-gradient(135deg,#f6d365,#fda085);">
        <h5>Total Gross (KES)</h5>
        <h2><?= number_format($total_gross, 2) ?></h2>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3" style="background: linear-gradient(135deg,#84fab0,#8fd3f4);">
        <h5>Total Paid</h5>
        <h2><?= number_format($total_paid, 2) ?></h2>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3" style="background: linear-gradient(135deg,#fccb90,#d57eeb);">
        <h5>Pending</h5>
        <h2><?= number_format($total_pending, 2) ?></h2>
      </div>
    </div>
  </div>

  <!-- Detailed Table -->
  <div class="card shadow border-0">
    <div class="card-body">
      <h4 class="mb-3 text-gradient">📊 Detailed Royalties</h4>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Song Title</th>
              <th>Genre</th>
              <th>Streams</th>
              <th>Gross (KES)</th>
              <?php if ($is_artist): ?>
                <th>Artist Share (KES)</th>
              <?php else: ?>
                <th>Producer Earnings (KES)</th>
                <th>Revenue Share (%)</th>
              <?php endif; ?>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($royalties)): ?>
              <tr><td colspan="7" class="text-center text-muted">No royalty data available yet.</td></tr>
            <?php else: ?>
              <?php foreach ($royalties as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= htmlspecialchars($r['genre'] ?? '-') ?></td>
                  <td><?= number_format($r['streams_count']) ?></td>
                  <td><?= number_format($r['gross_amount'], 2) ?></td>
                  <?php if ($is_artist): ?>
                    <td><?= number_format($r['artist_share'], 2) ?></td>
                  <?php else: ?>
                    <td><?= number_format($r['producer_earnings'] ?? 0, 2) ?></td>
                    <td><?= htmlspecialchars($r['revenue_share']) ?>%</td>
                  <?php endif; ?>
                  <td>
                    <span class="badge bg-<?= $r['status'] === 'paid' ? 'success' : 'warning' ?>">
                      <?= ucfirst($r['status']) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Mpesa Request -->
  <?php if ($total_pending > 0): ?>
    <div class="text-center mt-4">
      <a href="/harmony/payouts/mpesa_demo.php" class="btn btn-primary btn-lg">
        💸 Request M-Pesa Payout (<?= number_format($total_pending, 2) ?> KES)
      </a>
    </div>
  <?php else: ?>
    <div class="alert alert-success text-center mt-4">
      ✅ All your royalties have been paid out.
    </div>
  <?php endif; ?>
</div>

<footer class="mt-5 text-center text-light">
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
