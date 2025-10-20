<?php
require "../config/bootstrap.php";
require_roles(['Artist']);
$user = current_user();

$msg = "";

// Handle Mpesa payout simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $phone  = trim($_POST['mpesa_phone'] ?? '');

    if ($amount > 0 && preg_match('/^2547\d{8}$/', $phone)) {
        // Generate fake Mpesa transaction code
        $mpesa_code = 'MPESA' . strtoupper(bin2hex(random_bytes(3)));

        // Record in payouts table
        $stmt = $pdo->prepare("INSERT INTO payouts (user_id, amount, method, status) VALUES (?, ?, 'Mpesa', 'pending')");
        $stmt->execute([$user['id'], $amount]);

        // Record in transactions for simulation log
        $pdo->prepare("INSERT INTO transactions (user_id, mpesa_phone, amount, status, mpesa_code) VALUES (?, ?, ?, 'Completed', ?)")
            ->execute([$user['id'], $phone, $amount, $mpesa_code]);

        // Notify the artist
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([
            $user['id'],
            "✅ Mpesa payout simulation successful! Code: {$mpesa_code}, Amount: Ksh {$amount}"
        ]);

        $msg = "✅ Simulated Mpesa payout completed successfully! Code: <strong>{$mpesa_code}</strong>";
    } else {
        $msg = "❌ Invalid phone number or amount.";
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
  <title>M-Pesa - Harmony</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <div class="card p-4 shadow-lg border-0">
    <h2 class="text-center mb-3">📱 M-Pesa Payout</h2>
    <?php if ($msg): ?>
      <div class="alert alert-info text-center"><?= $msg ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-bold">Phone Number (Format: 2547XXXXXXXX)</label>
        <input type="text" name="mpesa_phone" class="form-control" required placeholder="2547XXXXXXXX">
      </div>
      <div class="col-md-6">
        <label class="form-label fw-bold">Amount (KES)</label>
        <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
      </div>
      <div class="col-12 text-center mt-3">
        <button class="btn btn-success px-4 py-2">💸 Payment</button>
      </div>
    </form>
  </div>

  <div class="card p-4 mt-4 shadow-sm">
    <h4>🧾 Recent Transactions</h4>
    <table class="table table-striped table-hover">
      <thead class="table-success">
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
              <td><?= htmlspecialchars($t['status']) ?></td>
              <td><?= htmlspecialchars($t['mpesa_code']) ?></td>
              <td><?= $t['transaction_time'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center text-muted">No transactions yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>