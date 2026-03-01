<?php
// login.php
// Simple login form that posts to auth.php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit;
}

// Read error message from query string if any
$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - PHP Firebase Login System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <main class="centered">
    <div class="card">
      <h1>Sign in</h1>
      <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="post" action="auth.php" novalidate>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required placeholder="you@example.com">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="6" placeholder="Enter your password">

        <button type="submit" class="btn">Log in</button>
      </form>
      <p class="muted">Note: This demo uses Firebase Authentication REST API. Run with XAMPP/Apache.</p>
    </div>
  </main>
</body>
</html>
