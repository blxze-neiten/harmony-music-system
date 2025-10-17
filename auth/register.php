<?php
require "../config/bootstrap.php"; 
$msg="";
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name=$_POST['name']; $email=$_POST['email'];
  $pass=password_hash($_POST['password'],PASSWORD_BCRYPT);
  $role_id=$_POST['role_id'];
  try {
    $pdo->prepare("INSERT INTO users(name,email,password,role_id) VALUES (?,?,?,?)")
        ->execute([$name,$email,$pass,$role_id]);
    $msg="✅ Registration successful. Please login.";
  } catch(Exception $e) { $msg="❌ Email already registered."; }
}
$roles=$pdo->query("SELECT * FROM roles")->fetchAll();
?>
<!doctype html><html><head>
<link rel="stylesheet" href="../assets/bootstrap.min.css"><link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="container mt-5 col-md-6">
  <div class="card p-4 shadow-lg">
    <h3 class="text-center mb-4">📝 Register</h3>
    <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <form method="post">
      <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>
      <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
      <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
      <select name="role_id" class="form-select mb-3" required>
        <option value="">Select Role</option>
        <?php foreach($roles as $r): ?>
          <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-success w-100">Register</button>
    </form>
    <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>
</body></html>
