<?php
require "../config/bootstrap.php";
$msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email=$_POST['email']; $pass=$_POST['password'];
  $stmt=$pdo->prepare("SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id=r.id WHERE email=?");
  $stmt->execute([$email]); $u=$stmt->fetch(PDO::FETCH_ASSOC);
  if($u && password_verify($pass,$u['password'])) {
    $_SESSION['user']=$u;
    header("Location: ../dashboard/dashboard.php"); exit;
  } else $msg="❌ Invalid login.";
}
?>
<!doctype html><html><head>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="container mt-5 col-md-6">
  <div class="card p-4 shadow-lg">
    <h3 class="text-center mb-4">🔑 Login</h3>
    <?php if($msg): ?><div class="alert alert-danger"><?= $msg ?></div><?php endif; ?>
    <form method="post">
      <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
      <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
      <button class="btn btn-primary w-100">Login</button>
    </form>
    <p class="mt-3 text-center">No account? <a href="register.php">Register</a></p>
  </div>
</div>
</body></html>
