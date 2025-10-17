<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Harmony Music Industry System</title>
  <link rel="stylesheet" href="assets/bootstrap.min.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">🎶 Harmony</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="auth/register.php">Register</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<div class="hero d-flex align-items-center text-center">
  <div class="container">
    <h1 class="display-4 fw-bold">Harmony Music Industry System</h1>
    <p class="lead mb-4">Empowering <span class="highlight">Artists</span>, <span class="highlight">Producers</span>, and <span class="highlight">Listeners</span> in one platform.</p>
    <div class="d-flex justify-content-center gap-3">
      <a href="auth/login.php" class="btn btn-primary btn-lg">🎧 Login</a>
      <a href="auth/register.php" class="btn btn-success btn-lg">📝 Register</a>
    </div>
  </div>
</div>

<!-- Features Section -->
<div class="container text-center my-5">
  <h2 class="mb-4">✨ What You Can Do</h2>
  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card p-3">
        <h3>🎤 Artists</h3>
        <p>Upload music, collaborate, view royalties, and cash out via Mpesa.</p>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card p-3">
        <h3>🎧 Users</h3>
        <p>Stream music, leave comments, and get notified about new releases.</p>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card p-3">
        <h3>🎹 Producers</h3>
        <p>Send production requests and work with top artists easily.</p>
      </d
