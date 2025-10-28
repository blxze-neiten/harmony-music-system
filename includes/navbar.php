<?php
require_once "../config/bootstrap.php";
require_login();
$user = current_user();

// Fetch role name
$stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
$stmt->execute([$user['role_id']]);
$role_name = $stmt->fetchColumn() ?: 'User';
?>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm"
     style="background: linear-gradient(90deg, #764ba2 0%, #2575fc 100%); backdrop-filter: blur(8px); padding: 0.5rem 1rem;">
  <div class="container-fluid">

    <!-- Brand -->
    <a class="navbar-brand fw-bold text-light d-flex align-items-center" href="/harmony/dashboard/dashboard.php" style="font-size: 1.3rem;">
      🎵 Harmony
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="/harmony/dashboard/dashboard.php">🏠 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/harmony/music/list.php">🎧 Music</a></li>
        <li class="nav-item"><a class="nav-link" href="/harmony/notifications/list.php">🔔 Notifications</a></li>

        <!-- ADMIN MENU -->
        <?php if ($role_name === 'Admin'): ?>
          <li class="nav-item"><a class="nav-link" href="/harmony/admin/users.php">👥 Manage Users</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/admin/royalties.php">💰 Royalties</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/admin/reports.php">📊 Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/admin/send_notification.php">📢 Send Notifications</a></li>

        <!-- ARTIST MENU -->
        <?php elseif ($role_name === 'Artist'): ?>
          <li class="nav-item"><a class="nav-link" href="/harmony/royalties/view.php">💸 My Royalties</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa Payouts</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/licensing/manage.php">🎼 Licensing</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/collab/manage.php">🤝 Collaborations</a></li>

        <!-- PRODUCER MENU -->
        <?php elseif ($role_name === 'Producer'): ?>
          <li class="nav-item"><a class="nav-link" href="/harmony/music/manage.php">🎵 Productions</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/licensing/request.php">📝 Licensing</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/collab/request.php">💬 Collaborations</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa Payouts</a></li>
          <li class="nav-item"><a class="nav-link" href="/harmony/royalties/view.php">💰 Royalties</a></li>
        <?php endif; ?>
      </ul>

      <!-- Right Side -->
      <div class="d-flex align-items-center">
        <span class="navbar-text text-light me-3">
          👋 Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong> (<?= htmlspecialchars($role_name) ?>)
        </span>
        <a href="/harmony/auth/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
      </div>
    </div>
  </div>
</nav>

<!-- ======= STYLING ======= -->
<style>
body {
  padding-top: 70px; /* Prevents navbar overlap */
  margin: 0;
}

.navbar {
  font-family: 'Segoe UI', Roboto, sans-serif;
  transition: all 0.3s ease-in-out;
  border: none;
}

/* Compact spacing */
.navbar-brand {
  margin: 0;
  padding: 0;
}

/* Link styles */
.navbar .nav-link {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 500;
  border-radius: 8px;
  margin-right: 6px;
  transition: all 0.2s ease;
}

.navbar .nav-link:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #fff !important;
  transform: translateY(-1px);
}

.navbar .btn-outline-light:hover {
  background: #fff !important;
  color: #6a11cb !important;
  border-color: transparent;
  font-weight: 600;
}

.navbar-text strong {
  color: #ffeb3b;
}
</style>
