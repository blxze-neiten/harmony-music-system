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
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Manage Payouts</title>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container-pad">
<div class="container">
  <h3>Payout Requests</h3>
  <table class="table">
    <thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= number_format($r['amount'],2) ?></td>
          <td><?= htmlspecialchars($r['method']) ?></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td>
            <?php if($r['status'] === 'pending'): ?>
              <form method="post" class="d-inline"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button name="action" value="send" class="btn btn-success btn-sm">Mark Sent</button></form>
              <form method="post" class="d-inline"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button name="action" value="fail" class="btn btn-danger btn-sm">Mark Failed</button></form>
            <?php else: ?>—
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body></html>
