<?php require "../config/bootstrap.php"; require_roles(['Producer']); ?>
<!doctype html>
<html>
<head><link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css"></head>
<body class="container">
<h2>Producer Dashboard</h2>
<a href="../producer/request_send.php" class="btn btn-primary">Send Request to Artist</a>
<a href="../notifications/list.php" class="btn btn-info">Notifications</a>
</body>
</html>
