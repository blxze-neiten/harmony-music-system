<?php 
require_once __DIR__ . "/../config/bootstrap.php"; 
require_login(); 
$user = current_user();

// Count notifications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=?");
$stmt->execute([$user['id']]);
$notif_count = $stmt->fetchColumn();
?>

<!-- 🎵 Harmony Navbar -->
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(90deg, #6C63FF, #FF6584);">
  <div class="container-fluid">
    <!-- Brand -->
    <a class="navbar-brand fw-bold text-white" href="/harmony/index.php">🎵 Harmony</a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar Links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <!-- Dashboard & Music -->
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="/harmony/dashboard/dashboard.php">🏠 Dashboard</a>
        </li>

        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="/harmony/music/list.php">🎧 Music</a>
        </li>

        <!-- Notifications -->
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="/harmony/notifications/list.php">
            🔔 Notifications 
            <?php if($notif_count > 0): ?>
              <span class="badge bg-danger"><?= $notif_count ?></span>
            <?php endif; ?>
          </a>
        </li>

        <!-- Artist-Specific Menu -->
        <?php if($user['role'] === 'Artist'): ?>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/music/upload.php">⬆️ Upload</a>
          </li>

          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/royalties/view.php">💰 Royalties</a>
          </li>

          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa Payouts </a>
          </li>

          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/licensing/manage.php">📜 Licensing</a>
          </li>

          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/collab/list.php">🤝 Collaboration</a>
          </li>
        <?php endif; ?>

        <!-- Producer Menu -->
        <?php if($user['role'] === 'Producer'): ?>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/collab/request.php">🎶 Collab Requests</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/licensing/request.php">📜 Request License</a>
          </li>
        <?php endif; ?>

        <!-- Admin Menu -->
        <?php if($user['role'] === 'Admin'): ?>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/admin/users.php">👑 Manage Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-light fw-semibold" href="/harmony/royalties/admin_view.php">💼 Admin Royalties</a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Right Side: User Info + Logout -->
      <span class="navbar-text text-white me-3">
        👤 <?= htmlspecialchars($user['name']) ?> (<?= $user['role'] ?>)
      </span>
      <a href="/harmony/auth/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
  </div>
</nav>
