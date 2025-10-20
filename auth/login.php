<?php 
require "../config/bootstrap.php"; 
$msg = ""; 
 
if ($_SERVER["REQUEST_METHOD"] === "POST") { 
    $email = trim($_POST["email"]); 
    $password = $_POST["password"]; 
 
    $stmt = $pdo->prepare(" 
        SELECT u.*, r.name AS role 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? 
    "); 
    $stmt->execute([$email]); 
    $user = $stmt->fetch(PDO::FETCH_ASSOC); 
 
    if ($user && password_verify($password, $user["password"])) { 
        $_SESSION["user"] = [ 
            "id" => $user["id"], 
            "name" => $user["name"], 
            "role_id" => $user["role_id"], 
            "role" => $user["role"] 
        ]; 
        header("Location: ../dashboard/dashboard.php"); 
        exit; 
    } else { 
        $msg = "❌ Invalid email or password."; 
    } 
} 
?> 
<!DOCTYPE html> 
<html lang="en">
<head> 
  <meta charset="utf-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🎵 Login - Harmony</title> 
  <link rel="stylesheet" href="../assets/bootstrap.min.css"> 
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

    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 50px 40px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 420px;
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

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }

    .form-group {
      margin-bottom: 25px;
      position: relative;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 600;
      font-size: 14px;
    }

    input.form-control {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fff;
    }

    input.form-control:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    input.form-control::placeholder {
      color: #aaa;
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
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .signup-link {
      text-align: center;
      margin-top: 25px;
      color: #666;
      font-size: 14px;
    }

    .signup-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .signup-link a:hover {
      color: #764ba2;
    }
  </style>
</head> 
<body> 
  <div class="login-container">
    <div class="logo">
      <h1>🎧 Harmony</h1>
      <p>Sign in to continue</p>
    </div>

    <?php if($msg): ?>
      <div class="alert alert-danger"><?= $msg ?></div>
    <?php endif; ?> 

    <form method="post"> 
      <div class="form-group">
        <input type="email" name="email" class="form-control" placeholder="Email Address" required> 
      </div>
      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="Password" required> 
      </div>
      <button type="submit" class="btn btn-primary">Sign In</button> 
    </form> 

    <p class="signup-link">New here? <a href="register.php">Create Account</a></p> 
  </div>
</body> 
</html>