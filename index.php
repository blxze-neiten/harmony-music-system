<?php
require __DIR__ . '/config/bootstrap.php';
if (isset($_SESSION['user'])) {
    header("Location: /harmony/dashboard/dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Harmony Music Industry System</title>
  <link rel="stylesheet" href="/harmony/assets/bootstrap.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
    }

    /* Hero Section */
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      width: 600px;
      height: 600px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -300px;
      right: -200px;
      animation: float 8s ease-in-out infinite;
    }

    .hero::after {
      content: '';
      position: absolute;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 50%;
      bottom: -250px;
      left: -150px;
      animation: float 10s ease-in-out infinite reverse;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) scale(1); }
      50% { transform: translateY(30px) scale(1.05); }
    }

    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      animation: fadeInUp 1s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      animation: fadeInUp 1s ease-out 0.2s backwards;
    }

    .hero .lead {
      font-size: 1.4rem;
      margin-bottom: 2.5rem;
      opacity: 0.95;
      animation: fadeInUp 1s ease-out 0.4s backwards;
    }

    .hero-buttons {
      display: flex;
      justify-content: center;
      gap: 1rem;
      flex-wrap: wrap;
      animation: fadeInUp 1s ease-out 0.6s backwards;
    }

    .btn-hero {
      padding: 16px 40px;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 50px;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-hero-light {
      background: white;
      color: #667eea;
    }

    .btn-hero-light:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
      color: #667eea;
    }

    .btn-hero-outline {
      background: transparent;
      color: white;
      border: 2px solid white;
    }

    .btn-hero-outline:hover {
      background: white;
      color: #667eea;
      transform: translateY(-3px);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
    }

    /* Features Section */
    .features-section {
      padding: 100px 0;
      background: #f8f9fa;
    }

    .features-section h2 {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 3rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .feature-card {
      background: white;
      border-radius: 20px;
      padding: 40px 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      height: 100%;
      border: 2px solid transparent;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      border-color: #667eea;
    }

    .feature-icon {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      display: block;
    }

    .feature-card h4 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #333;
    }

    .feature-card p {
      color: #666;
      line-height: 1.6;
      margin: 0;
    }

    /* Stagger animation for cards */
    .feature-card:nth-child(1) { animation: fadeInUp 0.6s ease-out 0.1s backwards; }
    .feature-card:nth-child(2) { animation: fadeInUp 0.6s ease-out 0.3s backwards; }
    .feature-card:nth-child(3) { animation: fadeInUp 0.6s ease-out 0.5s backwards; }

    /* Footer */
    footer {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 40px 0;
      text-align: center;
    }

    footer small {
      font-size: 1rem;
      opacity: 0.9;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .hero .lead {
        font-size: 1.2rem;
      }

      .btn-hero {
        padding: 14px 30px;
        font-size: 1rem;
      }

      .features-section h2 {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>

<div class="hero">
  <div class="container hero-content">
    <h1>🎧 Harmony Music Industry System</h1>
    <p class="lead">Empowering <strong>Artists</strong>, <strong>Producers</strong>, and <strong>Listeners</strong>.</p>
    <div class="hero-buttons">
      <a href="/harmony/auth/login.php" class="btn-hero btn-hero-light">
        <span>🎧</span> Login
      </a>
      <a href="/harmony/auth/register.php" class="btn-hero btn-hero-outline">
        <span>📝</span> Register
      </a>
    </div>
  </div>
</div>

<div class="features-section">
  <div class="container">
    <h2>What You Can Do</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card">
          <span class="feature-icon">🎤</span>
          <h4>Artists</h4>
          <p>Upload music, manage royalties, and accept collaborations to grow your career.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <span class="feature-icon">🎹</span>
          <h4>Producers</h4>
          <p>Send collaboration requests, manage projects, and create amazing tracks.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <span class="feature-icon">🎵</span>
          <h4>Listeners</h4>
          <p>Stream music, leave comments, and follow your favorite artists.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<footer>
  <small>© <?= date('Y') ?> Harmony - Where Music Meets Innovation</small>
</footer>

<script src="/harmony/assets/bootstrap.bundle.min.js"></script>
</body>
</html>