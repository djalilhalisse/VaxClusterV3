<?php
require __DIR__ . '/includes/bootstrap.php';
require './configuration/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: doctors_login.php'); exit(); }
csrf_check();
$username = mb_substr(trim($_POST['username'] ?? ''), 0, 255);
$pw = (string)($_POST['password'] ?? '');
$key = 'login:' . client_ip() . ':' . mb_strtolower($username);
if (rate_blocked($key, 5, 900)) { $_SESSION['error_message'] = 'err_locked'; header('Location: doctors_login.php'); exit(); }   // 5 failures / 15 min

$stmt = $pdo->prepare("SELECT username, pass FROM users WHERE username = :u");
$stmt->execute(['u' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$hashed = $user && password_verify($pw, $user['pass']);
$legacy = $user && !$hashed && hash_equals($user['pass'], $pw);      // old plain-text rows: accepted once, then upgraded
if ($hashed || $legacy) {
    if ($legacy || ($hashed && password_needs_rehash($user['pass'], PASSWORD_DEFAULT))) {
        $up = $pdo->prepare("UPDATE users SET pass = :p WHERE username = :u");
        $up->execute(['p' => password_hash($pw, PASSWORD_DEFAULT), 'u' => $user['username']]);
    }
    session_regenerate_id(true);
    $_SESSION['username'] = $user['username']; $_SESSION['last'] = time();
    unset($_SESSION['csrf']);
    header('Location: patients_list.php'); exit();
}
if (!$user) password_verify($pw, '$2y$10$usesomesillystringforsaltiuBfZQ3k5pKX9v1p6yq2uJ7p0m4lq');   // equalise timing for unknown users
rate_add($key, 900);
$_SESSION['error_message'] = 'err_login';
header('Location: doctors_login.php'); exit();
