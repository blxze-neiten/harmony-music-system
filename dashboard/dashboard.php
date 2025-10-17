<?php 
require_once "../config/bootstrap.php"; 
require_login(); 
$user = current_user();
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<div class="container mt-5">
  <h1 class="mb-4">Welcome, <?= htmlspecialchars($user['name']) ?> 🎶</h1>
  <p class="lead">Welcome to the Dashboard</p>

  <!-- Example Artist Stats -->
  <?php if ($user['role'] === 'Artist'): ?>
  <div class="row text-center">
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Songs Uploaded</h5>
          <p class="display-6 text-primary">24</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Total Streams</h5>
          <p class="display-6 text-success">4,520</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Royalties</h5>
          <p class="display-6 text-warning">KES 8,300</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Collabs</h5>
          <p class="display-6 text-info">4</p>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
