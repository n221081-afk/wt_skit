<?php
// dashboard.php
// Protected page. Shows basic user info and profile data + logout link.
session_start();

// If not logged in, redirect to login
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

$email = htmlspecialchars($_SESSION['user_email']);
$uid = $_SESSION['user_uid'] ?? '';
$idToken = $_SESSION['idToken'] ?? '';

// try to load additional metadata from Firestore
$userData = [];
if ($uid && $idToken) {
    require_once __DIR__ . '/firestore_helper.php';
    // primary lookup by UID
    $userData = get_user_document($uid, $idToken) ?: [];
    
    // fallback: try email as document ID directly (simpler approach)
    if (!$userData) {
        $project = FIREBASE_PROJECT_ID;
        $emailDocUrl = 'https://firestore.googleapis.com/v1/projects/' . urlencode($project) . '/databases/(default)/documents/users/' . urlencode($email);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $emailDocUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $idToken]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code === 200 && $resp) {
            $doc = json_decode($resp, true);
            if (isset($doc['fields'])) {
                foreach ($doc['fields'] as $key => $value) {
                    if (isset($value['stringValue'])) $userData[$key] = $value['stringValue'];
                    elseif (isset($value['integerValue'])) $userData[$key] = $value['integerValue'];
                    elseif (isset($value['doubleValue'])) $userData[$key] = $value['doubleValue'];
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - PHP Firebase Login System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <main class="centered">
    <div class="card">
      <header style="display:flex;justify-content:space-between;align-items:center">
        <h1>Dashboard</h1>
        <a class="btn" href="logout.php">Log out</a>
      </header>
      <p class="lead">Hello, <strong><?php echo $email; ?></strong></p>
      <p class="muted">UID: <?php echo htmlspecialchars($uid); ?></p>

      <?php if ($userData): ?>
        <div class="info">
          <?php if (isset($userData['studentId'])): ?>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($userData['studentId']); ?></p>
          <?php endif; ?>
          <?php if (isset($userData['marks'])): ?>
            <p><strong>Marks:</strong> <?php echo htmlspecialchars($userData['marks']); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <p>You're logged in using Firebase Authentication. Firestore can store additional user metadata under a `users` collection.</p>
    </div>
  </main>
</body>
</html>
