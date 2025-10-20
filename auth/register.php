<?php
require __DIR__ . '/../config/bootstrap.php';
if (isset($_SESSION['user'])) {
    header("Location: /harmony/dashboard/dashboard.php"); exit;
}

$msg = null;
$msgType = 'danger';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = (int)($_POST['role_id'] ?? 1);

    if (!$name || !$email || !$password) {
        $msg = "Please fill all fields.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role_id) VALUES (?,?,?,?)");
            $stmt->execute([$name,$email,$hash,$role_id]);
            $msg = "✅ Registration successful. Please login.";
            $msgType = 'success';
        } catch (Exception $e) {
            $msg = "❌ Email already registered.";
        }
    }
}

$roles = $pdo->query("SELECT id,name FROM roles")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Harmony</title>
  <link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      position: relative;
      overflow: hidden;
      padding: 20px;
    }

    body::before {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -250px;
      right: -250px;
      animation: float 6s ease-in-out infinite;
    }

    body::after {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      bottom: -200px;
      left: -200px;
      animation: float 8s ease-in-out infinite reverse;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(30px); }
    }

    .register-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 50px 40px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 450px;
      position: relative;
      z-index: 1;
      animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .logo h1 {
      font-size: 32px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .logo p {
      color: #666;
      font-size: 14px;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
      animation: shake 0.4s;
    }

    .alert-danger {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }

    .alert-success {
      background: #efe;
      color: #2a7;
      border: 1px solid #cfc;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
    }

    input.form-control,
    select.form-select {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fff;
    }

    input.form-control:focus,
    select.form-select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    input.form-control::placeholder {
      color: #aaa;
    }

    select.form-select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 16px center;
      padding-right: 45px;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
      margin-top: 10px;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .login-link {
      text-align: center;
      margin-top: 25px;
      color: #666;
      font-size: 14px;
    }

    .login-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .login-link a:hover {
      color: #764ba2;
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="logo">
      <h1>🎧 Harmony</h1>
      <p>Create your account</p>
    </div>

    <?php if($msg): ?>
      <div class="alert alert-<?= $msgType ?>"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <input name="name" class="form-control" placeholder="Full Name" required>
      </div>
      <div class="form-group">
        <input name="email" type="email" class="form-control" placeholder="Email Address" required>
      </div>
      <div class="form-group">
        <input name="password" type="password" class="form-control" placeholder="Password" required>
      </div>
      <div class="form-group">
        <select name="role_id" class="form-select" required>
          <?php foreach($roles as $r): ?>
            <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Create Account</button>
    </form>

    <p class="login-link">Already registered? <a href="/harmony/auth/login.php">Sign In</a></p>
  </div>
</body>
</html>