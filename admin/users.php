<?php
require __DIR__ . '/../config/bootstrap.php';
require_roles(['Admin']);
$user = current_user();

// ---------- Handle deletion (safe: cannot delete yourself) ----------
$msg = $error = '';
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    if ($delete_id === $user['id']) {
        $error = "You cannot delete your own admin account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        if ($stmt->rowCount()) {
            $msg = "User deleted successfully.";
        } else {
            $error = "Failed to delete user (user may not exist).";
        }
    }
}

// ---------- Filtering / Search ----------
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? 'all';

$query = "SELECT u.id, u.name, u.email, r.name AS role_name, u.created_at FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1";
$params = [];
if ($search !== '') {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($role !== 'all') {
    $query .= " AND r.name = ?";
    $params[] = $role;
}
$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query); $stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT name FROM roles")->fetchAll(PDO::FETCH_COLUMN);

// Calculate stats
$totalUsers = count($users);
$roleStats = [];
foreach($roles as $r) {
    $count = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = " . $pdo->quote($r))->fetchColumn();
    $roleStats[$r] = $count;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Harmony Admin</title>
<link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
  }

  .container {
    max-width: 1400px;
    margin: 0 auto;
  }

  .page-header {
    background: white;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: slideDown 0.5s ease-out;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
  }

  .user-count {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.95rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
  }

  .stat-badge {
    background: white;
    border-radius: 12px;
    padding: 15px 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    animation: fadeInUp 0.4s ease-out backwards;
  }

  .stat-badge:nth-child(1) { animation-delay: 0.1s; }
  .stat-badge:nth-child(2) { animation-delay: 0.15s; }
  .stat-badge:nth-child(3) { animation-delay: 0.2s; }
  .stat-badge:nth-child(4) { animation-delay: 0.25s; }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(15px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .stat-badge:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  }

  .stat-badge-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
  }

  .stat-badge-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #667eea;
  }

  .filter-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    margin-bottom: 25px;
    animation: fadeInUp 0.4s ease-out 0.3s backwards;
  }

  .filter-form {
    display: grid;
    grid-template-columns: 2fr 1.5fr auto;
    gap: 15px;
    align-items: end;
  }

  .form-group {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .form-control,
  .form-select {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #fff;
  }

  .form-control:focus,
  .form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  }

  .btn-primary {
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
  }

  .table-container {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    overflow-x: auto;
    animation: fadeInUp 0.5s ease-out 0.4s backwards;
  }

  .table {
    margin: 0;
    width: 100%;
  }

  .table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .table thead th {
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border: none;
  }

  .table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
  }

  .table tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
  }

  .table tbody td {
    padding: 15px;
    vertical-align: middle;
    color: #333;
  }

  .user-id {
    font-weight: 700;
    color: #667eea;
  }

  .user-name {
    font-weight: 600;
    color: #333;
  }

  .user-email {
    color: #666;
    font-size: 0.9rem;
  }

  .role-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
  }

  .role-admin {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
  }

  .role-artist {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .role-producer {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
  }

  .role-listener {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
  }

  .date-text {
    color: #999;
    font-size: 0.9rem;
  }

  /* small action button matching the decoration */
  .action-delete {
    display:inline-block;
    background: linear-gradient(135deg,#ff8585,#ff6b6b);
    color:white;
    padding:8px 12px;
    border-radius:10px;
    font-weight:600;
    text-decoration:none;
  }
  .action-disabled {
    display:inline-block;
    background:#d9d9d9;
    color:#666;
    padding:8px 12px;
    border-radius:10px;
    font-weight:600;
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
  }

  .empty-state-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
  }

  @media (max-width: 768px) {
    .page-header h3 {
      font-size: 1.5rem;
    }

    .filter-form {
      grid-template-columns: 1fr;
    }

    .table-container {
      padding: 20px;
    }

    .stats-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
  <div class="page-header">
    <h3>👥 Manage Users</h3>
    <div class="user-count">
      <?= $totalUsers ?> <?= $totalUsers === 1 ? 'user' : 'users' ?> found
    </div>
  </div>

  <!-- show message (keeps your style intact) -->
  <?php if ($msg): ?>
    <div style="margin-bottom:20px; padding:12px 18px; background:#e8fff0; border-radius:10px; color:#18794e;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php elseif ($error): ?>
    <div style="margin-bottom:20px; padding:12px 18px; background:#fff2f2; border-radius:10px; color:#842029;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="stats-grid">
    <?php foreach($roleStats as $roleName => $count): ?>
      <div class="stat-badge">
        <span class="stat-badge-label"><?= e($roleName) ?>s</span>
        <span class="stat-badge-value"><?= $count ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="filter-card">
    <form method="get" class="filter-form">
      <div class="form-group">
        <label>🔍 Search</label>
        <input name="search" class="form-control" placeholder="Search by name or email..." value="<?= e($search) ?>">
      </div>
      <div class="form-group">
        <label>👤 Role Filter</label>
        <select name="role" class="form-select">
          <option value="all">All Roles</option>
          <?php foreach($roles as $r): ?>
            <option value="<?= e($r) ?>" <?= $r === $role ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <button type="submit" class="btn-primary">Apply Filters</button>
      </div>
    </form>
  </div>

  <div class="table-container">
    <?php if(empty($users)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h4>No Users Found</h4>
        <p>Try adjusting your search or filter criteria.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Action</th> <!-- ADDED column -->
          </tr>
        </thead>
        <tbody>
          <?php foreach($users as $u): ?>
            <tr>
              <td><span class="user-id">#<?= $u['id'] ?></span></td>
              <td><span class="user-name"><?= e($u['name']) ?></span></td>
              <td><span class="user-email"><?= e($u['email']) ?></span></td>
              <td>
                <span class="role-badge role-<?= strtolower($u['role_name']) ?>">
                  <?= e($u['role_name']) ?>
                </span>
              </td>
              <td><span class="date-text"><?= date('M d, Y', strtotime($u['created_at'])) ?></span></td>

              <!-- ---------- Action Column (Delete) ---------- -->
              <td>
                <?php if ($u['id'] !== $user['id']): ?>
                  <a href="?delete=<?= $u['id'] ?>"
                     onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')"
                     class="action-delete">🗑️ Delete</a>
                <?php else: ?>
                  <span class="action-disabled">Admin</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>
