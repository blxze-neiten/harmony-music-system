<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Artist']);
$user = current_user();
$msg='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? 'manual';
    if ($amount <= 0) $error = "Invalid amount";
    else {
        $pdo->prepare("INSERT INTO payouts (user_id, amount, method, status) VALUES (?,?,?, 'pending')")->execute([$user['id'],$amount,$method]);
        // notify admin (first admin)
        $admin = $pdo->query("SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name='Admin' LIMIT 1")->fetchColumn() ?: null;
        if ($admin) $pdo->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")->execute([$admin, "Payout request from {$user['name']} of " . number_format($amount,2)]);
        $msg = "Payout requested. Admin will process it.";
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Request Payout</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container-pad">
<div class="container col-md-6">
  <h3>Request Payout</h3>
  <?php if(!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if(!empty($msg)): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <form method="post" class="card p-3">
    <input name="amount" type="number" step="0.01" class="form-control mb-2" placeholder="Amount" required>
    <select name="method" class="form-select mb-2">
      <option value="manual">Manual (Admin will pay)</option>
      <option value="mpesa">Mpesa (Use Mpesa Simulator)</option>
    </select>
    <div class="d-flex gap-2">
      <button class="btn btn-primary">Request</button>
      <a class="btn btn-outline-secondary" href="mpesa_demo.php">Open Mpesa Simulator</a>
    </div>
  </form>
</div>
</body></html>
