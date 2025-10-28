<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);

$rate_per_stream = 0.05;
$artist_share_percentage = 0.7;
$platform_share_percentage = 0.3;

$totalStreams = $pdo->query("SELECT SUM(views) FROM music")->fetchColumn() ?? 0;
$totalGross = $totalStreams * $rate_per_stream;
$artistEarnings = $totalGross * $artist_share_percentage;
$platformEarnings = $totalGross * $platform_share_percentage;

$earningArtists = $pdo->query("
    SELECT COUNT(DISTINCT artist_id)
    FROM music
    WHERE views > 0
")->fetchColumn() ?? 0;

$artistBreakdown = $pdo->query("
    SELECT u.name AS artist, SUM(m.views) AS total_views,
           (SUM(m.views) * $rate_per_stream * $artist_share_percentage) AS earnings
    FROM music m
    JOIN users u ON m.artist_id = u.id
    WHERE m.views > 0
    GROUP BY u.id, u.name
    ORDER BY earnings DESC
    LIMIT 10
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>💰 Royalties Summary - Harmony Admin</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
<style>
body {
  background: linear-gradient(135deg, #667eea, #764ba2);
  min-height: 100vh;
  padding: 40px 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.container { max-width: 1250px; margin: 0 auto; }
.page-header {
  background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.page-header h3 {
  font-size: 2rem; font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.stats-grid {
  display: grid; grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap: 20px; margin-bottom: 40px;
}
.stat-card {
  background: white; border-radius: 15px; padding: 25px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15); text-align: center;
  transition: transform 0.3s;
}
.stat-card:hover { transform: translateY(-5px); }
.stat-icon { font-size: 2rem; margin-bottom: 10px; }
.stat-label { color: #666; font-weight: 600; text-transform: uppercase; }
.stat-value { font-size: 1.8rem; font-weight: 700; color: #333; }

/* 💎 Improved Breakdown Table Section */
.table-card {
  background: white;
  border-radius: 25px;
  padding: 50px 40px;
  margin-top: 40px;
  box-shadow: 0 12px 35px rgba(0,0,0,0.15);
  animation: fadeIn 0.8s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
.table-card h5 {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 25px;
  color: #333;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: flex;
  align-items: center;
  gap: 10px;
}

.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 12px;
}
.table thead tr {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 15px;
}
.table thead th {
  padding: 14px 20px;
  border: none;
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.table tbody tr {
  background: #f9f9ff;
  transition: 0.3s;
  border-radius: 15px;
}
.table tbody tr:hover {
  background: #ece8ff;
  transform: scale(1.01);
}
.table tbody td {
  padding: 16px 20px;
  vertical-align: middle;
  font-size: 1rem;
  border: none;
}
.table tbody tr td:first-child {
  font-weight: 600;
  color: #764ba2;
}
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: #666;
}
.empty-state div {
  font-size: 3rem;
  margin-bottom: 10px;
}
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>💰 Royalties Summary</h3>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">💵</div>
      <div class="stat-label">Total Gross Revenue</div>
      <div class="stat-value">$<?= number_format($totalGross, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🎤</div>
      <div class="stat-label">Artist Earnings</div>
      <div class="stat-value">$<?= number_format($artistEarnings, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏢</div>
      <div class="stat-label">Platform Share</div>
      <div class="stat-value">$<?= number_format($platformEarnings, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-label">Earning Artists</div>
      <div class="stat-value"><?= number_format($earningArtists) ?></div>
    </div>
  </div>

  <div class="table-card">
    <h5>📊 Artist Earnings Breakdown</h5>
    <?php if (empty($artistBreakdown)): ?>
      <div class="empty-state">
        <div>💸</div>
        <p>No artist earnings yet. Once your songs get views, data will appear here.</p>
      </div>
    <?php else: ?>
      <table class="table table-borderless align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Artist</th>
            <th>Total Views</th>
            <th>Earnings (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($artistBreakdown as $row): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['artist']) ?></td>
            <td><?= number_format($row['total_views']) ?></td>
            <td><strong>$<?= number_format($row['earnings'], 2) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
