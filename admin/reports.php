<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);

// top artists by streams
$topArtists = $pdo->query("
  SELECT u.name AS artist, COUNT(s.id) AS total_streams
  FROM streams s
  JOIN music m ON s.music_id = m.id
  JOIN users u ON m.artist_id = u.id
  GROUP BY u.id, u.name
  ORDER BY total_streams DESC
  LIMIT 10
")->fetchAll();

// top songs
$topSongs = $pdo->query("
  SELECT m.title, COUNT(s.id) AS streams
  FROM streams s
  JOIN music m ON s.music_id = m.id
  GROUP BY m.id, m.title
  ORDER BY streams DESC
  LIMIT 10
")->fetchAll();

// stream trends last 6 months
$streamTrends = $pdo->query("
  SELECT DATE_FORMAT(played_at,'%Y-%m') as month, COUNT(*) as cnt
  FROM streams
  WHERE played_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY month
  ORDER BY month ASC
")->fetchAll();

// Calculate summary stats
$totalStreams = $pdo->query("SELECT COUNT(*) FROM streams")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM music")->fetchColumn();
$totalArtists = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id IN (SELECT id FROM roles WHERE name='Artist')")->fetchColumn();
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
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
  }

  .container {
    max-width: 1400px;
    margin: 0 auto;
  }

  .page-header {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.5s ease-out;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    animation: fadeInUp 0.5s ease-out backwards;
  }

  .stat-card:nth-child(1) { animation-delay: 0.1s; }
  .stat-card:nth-child(2) { animation-delay: 0.2s; }
  .stat-card:nth-child(3) { animation-delay: 0.3s; }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
  }

  .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
  }

  .stat-label {
    font-size: 0.9rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-top: 5px;
  }

  .charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
  }

  .chart-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    animation: fadeInUp 0.6s ease-out backwards;
    transition: all 0.3s ease;
  }

  .chart-card:nth-child(1) { animation-delay: 0.4s; }
  .chart-card:nth-child(2) { animation-delay: 0.5s; }

  .chart-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
  }

  .chart-card h5 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .chart-icon {
    font-size: 1.5rem;
  }

  .trend-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    animation: fadeInUp 0.6s ease-out 0.6s backwards;
    transition: all 0.3s ease;
  }

  .trend-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
  }

  .trend-card h5 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  canvas {
    max-height: 350px;
  }

  @media (max-width: 768px) {
    .page-header h3 {
      font-size: 1.5rem;
    }

    .charts-grid {
      grid-template-columns: 1fr;
    }

    .stat-value {
      font-size: 1.5rem;
    }
  }
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
      <div class="stat-label">Total Streams</div>
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
      <h5><span class="chart-icon">👑</span> Top Artists by Streams</h5>
      <canvas id="artistChart"></canvas>
    </div>
    <div class="chart-card">
      <h5><span class="chart-icon">🔥</span> Top Songs</h5>
      <canvas id="songChart"></canvas>
    </div>
  </div>

  <div class="trend-card">
    <h5><span class="chart-icon">📈</span> Stream Trends (Last 6 Months)</h5>
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

// Gradient colors
const ctx1 = document.getElementById('artistChart').getContext('2d');
const gradient1 = ctx1.createLinearGradient(0, 0, 0, 400);
gradient1.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
gradient1.addColorStop(1, 'rgba(118, 75, 162, 0.8)');

const ctx2 = document.getElementById('songChart').getContext('2d');
const gradient2 = ctx2.createLinearGradient(0, 0, 0, 400);
gradient2.addColorStop(0, 'rgba(102, 126, 234, 0.8)');
gradient2.addColorStop(1, 'rgba(118, 75, 162, 0.8)');

const ctx3 = document.getElementById('trendChart').getContext('2d');
const gradient3 = ctx3.createLinearGradient(0, 0, 0, 400);
gradient3.addColorStop(0, 'rgba(102, 126, 234, 0.4)');
gradient3.addColorStop(1, 'rgba(118, 75, 162, 0.1)');

new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: artistLabels,
    datasets: [{
      label: 'Total Streams',
      data: artistData,
      backgroundColor: gradient1,
      borderColor: 'rgba(102, 126, 234, 1)',
      borderWidth: 2,
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleFont: { size: 14, weight: 'bold' },
        bodyFont: { size: 13 }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(0, 0, 0, 0.05)' },
        ticks: { font: { size: 12 } }
      },
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 } }
      }
    }
  }
});

new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: songLabels,
    datasets: [{
      label: 'Total Streams',
      data: songData,
      backgroundColor: gradient2,
      borderColor: 'rgba(102, 126, 234, 1)',
      borderWidth: 2,
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleFont: { size: 14, weight: 'bold' },
        bodyFont: { size: 13 }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(0, 0, 0, 0.05)' },
        ticks: { font: { size: 12 } }
      },
      x: {
        grid: { display: false },
        ticks: { font: { size: 11 } }
      }
    }
  }
});

new Chart(ctx3, {
  type: 'line',
  data: {
    labels: trendLabels,
    datasets: [{
      label: 'Streams',
      data: trendData,
      fill: true,
      backgroundColor: gradient3,
      borderColor: 'rgba(102, 126, 234, 1)',
      borderWidth: 3,
      tension: 0.4,
      pointBackgroundColor: 'rgba(102, 126, 234, 1)',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 5,
      pointHoverRadius: 7
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleFont: { size: 14, weight: 'bold' },
        bodyFont: { size: 13 }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(0, 0, 0, 0.05)' },
        ticks: { font: { size: 12 } }
      },
      x: {
        grid: { display: false },
        ticks: { font: { size: 12 } }
      }
    }
  }
});
</script>
</body>
</html>