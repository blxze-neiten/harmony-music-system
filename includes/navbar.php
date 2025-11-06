<?php
require_once "../config/bootstrap.php";
require_login();
$user = current_user();

// ✅ Get role name safely
$stmt = $pdo->prepare("SELECT LOWER(name) FROM roles WHERE id = ?");
$stmt->execute([$user['role_id']]);
$role_name = $stmt->fetchColumn() ?: 'user';

// ✅ Detect current file for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm"
     style="background: linear-gradient(90deg, #764ba2 0%, #2575fc 100%); backdrop-filter: blur(8px); padding: 0.6rem 1rem;">
  <div class="container-fluid">

    <!-- Brand -->
    <a class="navbar-brand fw-bold text-light d-flex align-items-center" 
       href="/harmony/dashboard/dashboard.php" style="font-size: 1.3rem;">
      🎵 Harmony
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Menu -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
        
        <!-- Common to All -->
        <li class="nav-item">
          <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="/harmony/dashboard/dashboard.php">🏠 Dashboard</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= str_contains($current_page, 'notifications') ? 'active' : '' ?>" href="/harmony/notifications/list.php">🔔 Notifications</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= str_contains($current_page, 'notifications') ? 'active' : '' ?>" href="/harmony/music/list.php">🎶 Music Library</a>
        </li>

        <!-- ========== ADMIN MENU ========== -->
        <?php if ($role_name === 'admin'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= str_contains($current_page, 'admin') ? 'active' : '' ?>" href="#" id="adminMenu" data-bs-toggle="dropdown">
              ⚙️ Admin
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="/harmony/admin/users.php">👥 Manage Users</a></li>
              <li><a class="dropdown-item" href="/harmony/admin/send_notification.php">📢 Send Notifications</a></li>
              <li><a class="dropdown-item" href="/harmony/admin/royalties.php">💰 Royalties</a></li>
              <li><a class="dropdown-item" href="/harmony/admin/reports.php">📊 Reports</a></li>
            </ul>
          </li>

        <!-- ========== ARTIST MENU ========== -->
        <?php elseif ($role_name === 'artist'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= str_contains($current_page, 'artist') ? 'active' : '' ?>" href="#" id="artistMenu" data-bs-toggle="dropdown">
              🎤 Artist
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="/harmony/music/upload.php">⬆️ Upload Song</a></li>
              <li><a class="dropdown-item" href="/harmony/music/list.php">🎶 My Songs</a></li>
              <li><a class="dropdown-item" href="/harmony/licensing/manage.php">🎼 Licensing</a></li>
              <li><a class="dropdown-item" href="/harmony/collab/manage.php">🤝 Collaborations</a></li>
              <li><a class="dropdown-item" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa Payouts</a></li>
              <li><a class="dropdown-item" href="/harmony/royalties/view.php">💰 My Royalties</a></li>
            </ul>
          </li>

        <!-- ========== PRODUCER MENU ========== -->
        <?php elseif ($role_name === 'producer'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= str_contains($current_page, 'producer') ? 'active' : '' ?>" href="#" id="producerMenu" data-bs-toggle="dropdown">
              🎹 Producer
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="/harmony/producer/upload_beat.php">⬆️ Upload Beat</a></li>
              <li><a class="dropdown-item" href="/harmony/producer/my_beats.php">🎵 My Beats</a></li>
              <li><a class="dropdown-item" href="/harmony/licensing/request.php">📝 Licensing</a></li>
              <li><a class="dropdown-item" href="/harmony/collab/request.php">💬 Collaborations</a></li>
              <li><a class="dropdown-item" href="/harmony/payouts/mpesa_demo.php">📱 M-Pesa Payouts</a></li>
              <li><a class="dropdown-item" href="/harmony/royalties/view.php">💰 Royalties</a></li>
            </ul>
          </li>

        <!-- ========== REGULAR USER MENU ========== -->
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= str_contains($current_page, 'explore') ? 'active' : '' ?>" href="/harmony/music/list.php">🎧 Explore Music</a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Right Side -->
      <div class="d-flex align-items-center">
        <span class="navbar-text text-light me-3">
          👋 Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong>
          (<span style="text-transform:capitalize;"><?= htmlspecialchars($role_name) ?></span>)
        </span>
        <a href="/harmony/auth/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">🚪 Logout</a>
      </div>
    </div>
  </div>
</nav>

<!-- ======= STYLING ======= -->
<style>
body {
  padding-top: 75px; /* Prevent navbar overlap */
  margin: 0;
  font-family: 'Segoe UI', Roboto, sans-serif;
}

/* Navbar look */
.navbar {
  transition: all 0.3s ease-in-out;
  border: none;
}

/* Links */
.navbar .nav-link {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 500;
  border-radius: 8px;
  margin-right: 6px;
  transition: all 0.2s ease;
}

.navbar .nav-link:hover,
.navbar .nav-link.active {
  background: rgba(255, 255, 255, 0.25);
  color: #fff !important;
  transform: translateY(-1px);
}

/* Dropdown menu */
.dropdown-menu-dark {
  background-color: #343a40;
  border-radius: 10px;
}

.dropdown-menu-dark .dropdown-item:hover {
  background-color: #495057;
}

/* Logout Button */
.navbar .btn-outline-light:hover {
  background: #fff !important;
  color: #2575fc !important;
  border-color: transparent;
  font-weight: 600;
}

/* Welcome text */
.navbar-text strong {
  color: #ffeb3b;
}
</style>
