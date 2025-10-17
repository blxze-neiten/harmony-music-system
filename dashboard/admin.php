<?php require "../config/bootstrap.php"; require_roles(['Admin']); ?>
<!doctype html>
<html>
<head><link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container">
<h2>Admin Dashboard</h2>
<a href="../admin/users.php" class="btn btn-danger">Manage Users</a>
<a href="../admin/payouts.php" class="btn btn-warning">Manage Payouts</a>
<a href="../notifications/list.php" class="btn btn-info">Notifications</a>
</body>
</html>
