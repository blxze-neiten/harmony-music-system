<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);

// top artists by streams (using views instead of streams)
$topArtists = $pdo->query("
  SELECT u.name AS artist, SUM(m.views) AS total_streams
  FROM music m
  JOIN users u ON m.artist_id = u.id
  GROUP BY u.id, u.name
  ORDER BY total_streams DESC
  LIMIT 10
")->fetchAll();

// top songs
$topSongs = $pdo->query("
  SELECT m.title, m.views AS streams
  FROM music m
  ORDER BY m.views DESC
  LIMIT 10
")->fetchAll();

// stream trends (approximate based on created_at of songs)
$streamTrends = $pdo->query("
  SELECT DATE_FORMAT(created_at,'%Y-%m') as month, SUM(views) as cnt
  FROM music
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY month
  ORDER BY month ASC
")->fetchAll();

// summary stats
$totalStreams = $pdo->query("SELECT SUM(views) FROM music")->fetchColumn() ?? 0;
$totalSongs = $pdo->query("SELECT COUNT(*) FROM music")->fetchColumn();
$totalArtists = $pdo->query("
  SELECT COUNT(*) FROM users 
  WHERE role_id IN (SELECT id FROM roles WHERE name='Artist')
")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports & Analytics - Harmony Admin</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
  }
  .container { max-width: 1400px; margin: 0 auto; }
  .page-header {
    background: white; border-radius: 20px; padding: 30px; margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  }
  .page-header h3 {
    font-size: 2rem; font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  }
  .stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
    gap: 20px; margin-bottom: 30px;
  }
  .stat-card {
    background: white; border-radius: 15px; padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15); text-align: center;
  }
  .stat-icon { font-size: 2.5rem; margin-bottom: 10px; }
  .stat-label { font-size: 0.9rem; color: #666; text-transform: uppercase; }
  .stat-value { font-size: 2rem; font-weight: 700; color: #333; }
  .charts-grid {
    display: grid; grid-template-columns: repeat(auto-fit,minmax(450px,1fr));
    gap: 25px; margin-bottom: 30px;
  }
  .chart-card, .trend-card {
    background: white; border-radius: 20px; padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  }
  .chart-card h5, .trend-card h5 {
    font-size: 1.3rem; font-weight: 700; color: #333; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  canvas { max-height: 350px; }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>📊 Reports & Analytics</h3>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🎵</div>
      <div class="stat-label">Total Streams (Views)</div>
      <div class="stat-value"><?= number_format($totalStreams) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🎤</div>
      <div class="stat-label">Total Artists</div>
      <div class="stat-value"><?= number_format($totalArtists) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🎧</div>
      <div class="stat-label">Total Songs</div>
      <div class="stat-value"><?= number_format($totalSongs) ?></div>
    </div>
  </div>

  <div class="charts-grid">
    <div class="chart-card">
      <h5><span>👑</span> Top Artists by Streams</h5>
      <canvas id="artistChart"></canvas>
    </div>
    <div class="chart-card">
      <h5><span>🔥</span> Top Songs</h5>
      <canvas id="songChart"></canvas>
    </div>
  </div>

  <div class="trend-card">
    <h5><span>📈</span> Stream Trends (Last 6 Months)</h5>
    <canvas id="trendChart"></canvas>
  </div>
</div>

<script>
const artistLabels = <?= json_encode(array_column($topArtists,'artist')) ?>;
const artistData = <?= json_encode(array_map('intval', array_column($topArtists,'total_streams'))) ?>;
const songLabels = <?= json_encode(array_column($topSongs,'title')) ?>;
const songData = <?= json_encode(array_map('intval', array_column($topSongs,'streams'))) ?>;
const trendLabels = <?= json_encode(array_column($streamTrends,'month')) ?>;
const trendData = <?= json_encode(array_map('intval', array_column($streamTrends,'cnt'))) ?>;

const ctx1 = document.getElementById('artistChart').getContext('2d');
const ctx2 = document.getElementById('songChart').getContext('2d');
const ctx3 = document.getElementById('trendChart').getContext('2d');

new Chart(ctx1, {
  type: 'bar',
  data: { labels: artistLabels, datasets: [{ label: 'Total Streams', data: artistData, backgroundColor: '#667eea' }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(ctx2, {
  type: 'bar',
  data: { labels: songLabels, datasets: [{ label: 'Total Streams', data: songData, backgroundColor: '#764ba2' }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(ctx3, {
  type: 'line',
  data: { labels: trendLabels, datasets: [{ label: 'Streams', data: trendData, fill: true, backgroundColor: 'rgba(118,75,162,0.2)', borderColor: '#764ba2', borderWidth: 3 }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>
