<?php
require "../config/bootstrap.php";
require_roles(['Artist', 'Producer']);
$user = current_user();

$msg = "";
$role = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$role->execute([$user['role_id']]);
$role_name = $role->fetchColumn();

// Handle Mpesa payout simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $phone  = trim($_POST['mpesa_phone'] ?? '');

    if ($amount > 0 && preg_match('/^2547\d{8}$/', $phone)) {
        // Generate fake Mpesa transaction code
        $mpesa_code = 'MPESA' . strtoupper(bin2hex(random_bytes(3)));

        // Record payout
        $stmt = $pdo->prepare("INSERT INTO payouts (user_id, amount, method, status) VALUES (?, ?, 'Mpesa', 'pending')");
        $stmt->execute([$user['id'], $amount]);

        // Record in transactions (simulation)
        $pdo->prepare("
            INSERT INTO transactions (user_id, mpesa_phone, amount, status, mpesa_code)
            VALUES (?, ?, ?, 'Completed', ?)
        ")->execute([$user['id'], $phone, $amount, $mpesa_code]);

        // Notify user
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([
                $user['id'],
                "✅ M-Pesa payout successful! Code: {$mpesa_code}, Amount: Ksh {$amount}"
            ]);

        $msg = "✅ Simulated Mpesa payout completed successfully!<br><strong>Transaction Code:</strong> {$mpesa_code}";
    } else {
        $msg = "❌ Invalid phone number or amount. Please try again.";
    }
}

// Fetch last 10 payout transactions
$tx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_time DESC LIMIT 10");
$tx->execute([$user['id']]);
$transactions = $tx->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>💸 M-Pesa Payout - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea, #764ba2);
      font-family: 'Segoe UI', sans-serif;
      color: #333;
      padding-top: 80px;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 35px rgba(0,0,0,0.2);
      transition: transform 0.2s;
    }
    .card:hover { transform: translateY(-3px); }
    h2 {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 700;
    }
    .btn-success {
      background: linear-gradient(135deg,#28a745,#218838);
      border: none;
      font-weight: 600;
      border-radius: 10px;
      padding: 10px 25px;
      transition: all 0.3s;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(40,167,69,0.4);
    }
    .table thead {
      background: linear-gradient(135deg,#667eea,#764ba2);
      color: white;
    }
    footer {
      margin-top: 50px;
      text-align: center;
      color: white;
      opacity: 0.8;
    }
  </style>
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container">
  <div class="card p-4 mb-4">
    <h2 class="text-center mb-3">📱 M-Pesa Payout — <?= htmlspecialchars($role_name) ?></h2>

    <?php if ($msg): ?>
      <div class="alert alert-info text-center"><?= $msg ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-bold">Phone Number (Format: 2547XXXXXXXX)</label>
        <input type="text" name="mpesa_phone" class="form-control" placeholder="2547XXXXXXXX" required>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">Amount (KES)</label>
        <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
      </div>
      <div class="col-12 text-center mt-3">
        <button class="btn btn-success btn-lg">💸 Make your Payment</button>
      </div>
    </form>
  </div>

  <div class="card p-4 shadow-sm">
    <h4 class="mb-3 text-gradient">🧾 Recent Transactions</h4>
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle">
        <thead>
          <tr>
            <th>Phone</th>
            <th>Amount (KES)</th>
            <th>Status</th>
            <th>Code</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($transactions) > 0): ?>
            <?php foreach ($transactions as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['mpesa_phone']) ?></td>
                <td><?= number_format($t['amount'], 2) ?></td>
                <td>
                  <span class="badge bg-<?= $t['status'] === 'Completed' ? 'success' : 'warning' ?>">
                    <?= htmlspecialchars($t['status']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($t['mpesa_code']) ?></td>
                <td><?= htmlspecialchars($t['transaction_time']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted">No transactions yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> Harmony Music Industry System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
