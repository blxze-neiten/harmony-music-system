<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'fail';
    $status = $action === 'send' ? 'sent' : 'failed';
    $pdo->prepare("UPDATE payouts SET status = ? WHERE id = ?")->execute([$status,$id]);

    $p = $pdo->prepare("SELECT user_id, amount FROM payouts WHERE id = ?"); $p->execute([$id]); $row = $p->fetch();
    if ($row) $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")->execute([$row['user_id'], "Your payout of " . number_format($row['amount'],2) . " has been marked $status."]);
    header("Location: payouts.php"); exit;
}

$rows = $pdo->query("SELECT p.*, u.name FROM payouts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC")->fetchAll();

// Calculate statistics
$totalPending = 0;
$totalSent = 0;
$pendingCount = 0;
foreach($rows as $r) {
    if($r['status'] === 'pending') {
        $totalPending += $r['amount'];
        $pendingCount++;
    } elseif($r['status'] === 'sent') {
        $totalSent += $r['amount'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Payouts - Harmony Admin</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css">
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
    animation: fadeInUp 0.5s ease-out 0.4s backwards;
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

  .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
  }

  .badge-pending {
    background: #fff3cd;
    color: #856404;
  }

  .badge-sent {
    background: #d4edda;
    color: #155724;
  }

  .badge-failed {
    background: #f8d7da;
    color: #721c24;
  }

  .btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-success {
    background: #28a745;
    color: white;
  }

  .btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
  }

  .btn-danger {
    background: #dc3545;
    color: white;
  }

  .btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
  }

  .action-buttons {
    display: flex;
    gap: 8px;
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

    .action-buttons {
      flex-direction: column;
    }
  }
</style>
</head>
<body>
<div class="container">
  <div class="page-header">
    <h3>💰 Payout Requests Management</h3>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">⏳</div>
      <div class="stat-label">Pending Payouts</div>
      <div class="stat-value"><?= $pendingCount ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💵</div>
      <div class="stat-label">Pending Amount</div>
      <div class="stat-value">$<?= number_format($totalPending, 2) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-label">Total Sent</div>
      <div class="stat-value">$<?= number_format($totalSent, 2) ?></div>
    </div>
  </div>

  <div class="table-container">
    <?php if(empty($rows)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <h4>No Payout Requests</h4>
        <p>There are currently no payout requests to display.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><strong>#<?= $r['id'] ?></strong></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><strong>$<?= number_format($r['amount'], 2) ?></strong></td>
              <td><?= htmlspecialchars($r['method']) ?></td>
              <td>
                <span class="badge badge-<?= $r['status'] ?>">
                  <?= htmlspecialchars($r['status']) ?>
                </span>
              </td>
              <td>
                <?php if($r['status'] === 'pending'): ?>
                  <div class="action-buttons">
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button name="action" value="send" class="btn btn-success" onclick="return confirm('Mark this payout as sent?')">
                        ✓ Mark Sent
                      </button>
                    </form>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button name="action" value="fail" class="btn btn-danger" onclick="return confirm('Mark this payout as failed?')">
                        ✗ Mark Failed
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <span style="color: #999;">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script src="../assets/bootstrap.bundle.min.js"></script>
</body>
</html>