<?php
// register.php
// Simple registration form to create a Firebase Auth user with chosen email/password.
// Uses Firebase REST API accounts:signUp

require_once __DIR__ . '/firebase_config.php';
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_email'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// check configuration
if (defined('FIREBASE_API_KEY') && FIREBASE_API_KEY === 'YOUR_FIREBASE_API_KEY_HERE') {
    $error = 'Server configuration error: set FIREBASE_API_KEY in firebase_config.php';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    $confirm = filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW);

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email.';
    } elseif (!$password || strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    }

    if (!$error) {
        // build payload
        $payload = json_encode([
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=' . FIREBASE_API_KEY);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($curlErr) {
            $error = 'Network error: ' . $curlErr;
        } elseif ($httpCode === 200 && isset($data['localId'])) {
            $success = 'User created successfully. You can now <a href="login.php">log in</a>.';
        } else {
            // show the Firebase message plus raw response for debugging
            $firebaseMsg = $data['error']['message'] ?? 'Registration failed.';
            $error = $firebaseMsg . ' (raw: ' . htmlspecialchars($response) . ')';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register - PHP Firebase Login System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <main class="centered">
    <div class="card">
      <h1>Create account</h1>
      <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="muted"><?php echo $success; ?></div>
      <?php endif; ?>
      <form method="post" action="register.php">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" minlength="6" required>

        <label for="confirm">Confirm password</label>
        <input id="confirm" name="confirm" type="password" minlength="6" required>

        <button type="submit" class="btn">Register</button>
      </form>
      <p class="muted">Already have an account? <a href="login.php">Login</a>.</p>
    </div>
  </main>
</body>
</html>
