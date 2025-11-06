<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']); // Only admin can access this page
$user = current_user();

$success = $error = "";

// ✅ Handle status updates (Mark as Paid or Pending)
if (isset($_GET['update_status'])) {
    $id = (int) $_GET['update_status'];
    $new_status = $_GET['status'] === 'paid' ? 'paid' : 'pending';
    $stmt = $pdo->prepare("UPDATE royalties SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $id])) {
        $success = "✅ Royalty marked as " . ucfirst($new_status);
    } else {
        $error = "❌ Failed to update royalty status.";
    }
}

// ✅ Fetch all royalties (including related song, artist, and producer)
$query = $pdo->query("
    SELECT 
        r.id AS royalty_id,
        m.title AS song_title,
        m.genre,
        u.name AS artist_name,
        r.streams_count,
        r.gross_amount,
        r.artist_share,
        r.producer_share,
        r.status
    FROM royalties r
    JOIN music m ON r.music_id = m.id
    JOIN users u ON m.artist_id = u.id
    ORDER BY r.id DESC
");
$royalties = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>💰 Manage Royalties - Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    background: linear-gradient(135deg, #667eea, #764ba2);
    font-family: 'Segoe UI', sans-serif;
    color: #333;
    padding: 50px 20px;
  }
  .container {
    max-width: 1100px;
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  }
  h2 {
    font-weight: 700;
    text-align: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 25px;
  }
  .badge {
    font-size: 0.9rem;
    padding: 6px 10px;
    border-radius: 8px;
  }
  .btn-action {
    border: none;
    border-radius: 10px;
    padding: 6px 12px;
    font-weight: 600;
    transition: all 0.3s;
  }
  .btn-paid {
    background: linear-gradient(135deg, #84fab0, #8fd3f4);
  }
  .btn-paid:hover {
    box-shadow: 0 4px 12px rgba(132,250,176,0.4);
  }
  .btn-pending {
    background: linear-gradient(135deg, #f6d365, #fda085);
  }
  .btn-pending:hover {
    box-shadow: 0 4px 12px rgba(253,160,133,0.4);
  }
  footer {
    text-align: center;
    color: #eee;
    margin-top: 40px;
  }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container mt-4">
  <h2>💼 Admin - Manage Royalties</h2>

  <?php if($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>#</th>
          <th>Song</th>
          <th>Artist</th>
          <th>Genre</th>
          <th>Streams</th>
          <th>Gross (KES)</th>
          <th>Artist Share</th>
          <th>Producer Share</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($royalties)): ?>
          <tr><td colspan="10" class="text-center text-muted">No royalty data available.</td></tr>
        <?php else: ?>
          <?php foreach($royalties as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['song_title']) ?></td>
              <td><?= htmlspecialchars($r['artist_name']) ?></td>
              <td><?= htmlspecialchars($r['genre'] ?? '-') ?></td>
              <td><?= number_format($r['streams_count'] ?? 0) ?></td>
              <td><?= number_format($r['gross_amount'] ?? 0, 2) ?></td>
              <td><?= number_format($r['artist_share'] ?? 0, 2) ?></td>
              <td><?= number_format($r['producer_share'] ?? 0, 2) ?></td>
              <td>
                <span class="badge bg-<?= $r['status'] === 'paid' ? 'success' : 'warning' ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
              <td>
                <?php if($r['status'] !== 'paid'): ?>
                  <a href="?update_status=<?= $r['royalty_id'] ?>&status=paid" 
                     class="btn-action btn-paid text-dark">✅ Mark Paid</a>
                <?php else: ?>
                  <a href="?update_status=<?= $r['royalty_id'] ?>&status=pending" 
                     class="btn-action btn-pending text-dark">↩️ Revert Pending</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
