<?php require "../config/bootstrap.php"; require_roles(['Admin']);
$users=$pdo->query("SELECT u.id,u.name,u.email,r.name as role,u.created_at FROM users u JOIN roles r ON u.role_id=r.id ORDER BY u.created_at DESC")->fetchAll();
?>
<!doctype html><html><head><link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head><body>
<div class="container mt-5">
  <h2>🛡️ Manage Users</h2>
  <table class="table table-striped">
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
    <?php foreach($users as $u): ?>
    <tr><td><?= $u['id'] ?></td><td><?= $u['name'] ?></td><td><?= $u['email'] ?></td><td><?= $u['role'] ?></td><td><?= $u['created_at'] ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
</body></html>
