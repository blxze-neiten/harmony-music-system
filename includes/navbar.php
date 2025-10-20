<?php
require_once __DIR__ . '/../config/bootstrap.php';
$user = current_user();

$notif_count = 0;
$role_name = '';

if ($user) {
    // Get role name
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$user['role_id']]);
    $role_name = $stmt->fetchColumn();

    // Get unread notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $notif_count = (int)$stmt->fetchColumn();
}
?>

<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">

<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(90deg, #6C63FF, #FF6584);">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-white" href="/harmony/index.php">🎵 Harmony</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- General Menu -->
        <li class="nav-item"><a class="nav-link text-white" href="/harmony/dashboard/dashboard.php">🏠 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/harmony/music/list.php">🎧 Music</a></li>

        <?php if ($user): ?>
          <!-- Notifications -->
          <li class="nav-item">
            <a class="nav-link text-white" href="/harmony/notifications/list.php">
              🔔 Notifications
              <?php if ($notif_count > 0): ?>
                <span class="badge bg-danger"><?= $notif_count ?></span>
              <?php endif; ?>
            </a>
          </li>

          <!-- Artist Menu -->
          <?php if ($role_name === 'Artist'): ?>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/music/upload.php">⬆️ Upload</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/royalties/view.php">💰 Royalties</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/licensing/manage.php">📜 Licensing</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/collab/manage.php">🤝 Collaboration</a></li>
          <?php endif; ?>

          <!-- Producer Menu -->
          <?php if ($role_name === 'Producer'): ?>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/collab/request.php">🎶 Collab Requests</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/licensing/request.php">📜 Request License</a></li>
          <?php endif; ?>

          <!-- Admin Menu -->
          <?php if ($role_name === 'Admin'): ?>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/admin/users.php">👥 Manage Users</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/admin/royalties.php">💼 Manage Royalties</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/admin/reports.php">📊 Charts & Reports</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/harmony/admin/send_notification.php">📨 Notifications</a></li>
          <?php endif; ?>

        <?php endif; ?>
      </ul>

      <!-- Right Side -->
      <div class="d-flex align-items-center">
        <?php if ($user): ?>
          <span class="text-white me-3">👤 <?= htmlspecialchars($user['name']) ?> (<?= $role_name ?>)</span>
          <a href="/harmony/auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        <?php else: ?>
          <a href="/harmony/auth/login.php" class="btn btn-sm btn-light me-2">Login</a>
          <a href="/harmony/auth/register.php" class="btn btn-sm btn-outline-light">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
