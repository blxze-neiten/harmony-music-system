<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);

// fetch royalties summary per artist
$rows = $pdo->query("
  SELECT u.id AS artist_id, u.name AS artist, SUM(r.artist_share) AS total_artist_share, SUM(r.gross_amount) AS total_gross
  FROM royalties r
  JOIN music m ON r.music_id = m.id
  JOIN users u ON m.artist_id = u.id
  GROUP BY u.id, u.name
  ORDER BY total_gross DESC
")->fetchAll();

// Calculate summary statistics
$totalGross = 0;
$totalArtistShare = 0;
$artistCount = count($rows);
foreach($rows as $r) {
    $totalGross += $r['total_gross'] ?? 0;
    $totalArtistShare += $r['total_artist_share'] ?? 0;
}
$platformShare = $totalGross - $totalArtistShare;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Royalties Summary - Harmony Admin</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
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
    max-width: 1200px;
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
  .stat-card:nth-child(4) { animation-delay: 0.4s; }

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
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin-top: 5px;
  }

  .table-container {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    overflow-x: auto;
    animation: fadeInUp 0.5s ease-out 0.5s backwards;
  }

  .table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .table-header h4 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .table {
    margin: 0;
    width: 100%;
  }

  .table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .table thead th {
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border: none;
  }

  .table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
  }

  .table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
  }

  .table tbody td {
    padding: 15px;
    vertical-align: middle;
    color: #333;
  }

  .artist-name {
    font-weight: 600;
    color: #667eea;
  }

  .amount {
    font-weight: 700;
    font-size: 1.05rem;
  }

  .amount-gross {
    color: #28a745;
  }

  .amount-share {
    color: #667eea;
  }

  .rank-badge {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 700;
    font-size: 0.85rem;
    line-height: 30px;
    text-align: center;
    margin-right: 10px;
  }

  .top-3 {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #333;
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
  }

  .empty-state-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
  }

  @media (max-width: 768px) {
    .page-header h3 {
      font-size: 1.5rem;
    }

    .stat-value {
      font-size: 1.5rem;
    }

    .table-container {
      padding: 20px;
    }
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
      <div class="stat-value">$<?= number_format($totalArtistShare, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏢</div>
      <div class="stat-label">Platform Share</div>
      <div class="stat-value">$<?= number_format($platformShare, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-label">Earning Artists</div>
      <div class="stat-value"><?= number_format($artistCount) ?></div>
    </div>
  </div>

  <div class="table-container">
    <div class="table-header">
      <h4>📊 Artist Earnings Breakdown</h4>
    </div>

    <?php if(empty($rows)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">💸</div>
        <h4>No Royalty Data</h4>
        <p>There are currently no royalty records to display.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Artist</th>
            <th>Total Gross</th>
            <th>Artist Share</th>
          </tr>
        </thead>
        <tbody>
          <?php $rank = 1; foreach($rows as $r): ?>
            <tr>
              <td>
                <span class="rank-badge <?= $rank <= 3 ? 'top-3' : '' ?>">
                  <?= $rank ?>
                </span>
              </td>
              <td>
                <span class="artist-name"><?= e($r['artist']) ?></span>
              </td>
              <td>
                <span class="amount amount-gross">$<?= number_format($r['total_gross'] ?? 0, 2) ?></span>
              </td>
              <td>
                <span class="amount amount-share">$<?= number_format($r['total_artist_share'] ?? 0, 2) ?></span>
              </td>
            </tr>
          <?php $rank++; endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>