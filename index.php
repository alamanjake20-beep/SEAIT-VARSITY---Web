<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u AND status = 'Active' LIMIT 1");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | SEAIT VARSITY DORM VISITOR MONITORING SYSTEM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/auth-ui.css">
</head>
<body class="auth-page">
<main class="auth-layout">
  <section class="auth-hero" aria-label="SEAIT Varsity Dorm">
    <div class="auth-hero-content">
      <div class="auth-hero-badge"><span></span> SEAIT VARSITY DORMITORY</div>
      <h1>SEAIT Varsity<br>Dorm <span>Visitor</span><br><span>Monitoring System</span></h1>
      <p>A safer, more organized way to welcome and keep track of visitors at the SEAIT Varsity Dorm.</p>
    </div>
  </section>

  <section class="auth-side">
    <div class="auth-content">
      <div class="auth-brand">
        <div class="auth-brand-icon"><i class="bi bi-house-door"></i></div>
        <div>
          <strong>SEAIT Varsity Dorm</strong>
          <span>Visitor Monitoring System</span>
        </div>
      </div>

      <div class="auth-card">
        <header class="auth-card-heading">
          <h2>Welcome back</h2>
          <p>Sign in to manage dorm visitor records.</p>
        </header>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" class="auth-form">
          <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input id="username" type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
          </div>
          <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" required>
          </div>
          <button type="submit" class="btn auth-submit w-100">Sign in <i class="bi bi-arrow-right"></i></button>
        </form>

        <div class="auth-divider"><span>New to the portal?</span></div>

        <p class="auth-card-note">Need access? Contact your dorm administrator for an account.</p>
        <a href="athlete_login.php" class="auth-secondary-link">Athlete login</a>
      </div>
    </div>
  </section>
</main>
</body>
</html>
