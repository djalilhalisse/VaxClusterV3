<?php
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}
// ---- security headers (CSP uses a per-request nonce for the few inline scripts)
$NONCE = base64_encode(random_bytes(16));
if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$NONCE'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($https) header('Strict-Transport-Security: max-age=31536000');
    if (!empty($_SESSION['username'])) header('Cache-Control: no-store');   // patient data must not be cached by the browser
}
function nonce(){ global $NONCE; return $NONCE; }
function client_ip(){ $x = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''; return $x ? trim(explode(',', $x)[0]) : ($_SERVER['REMOTE_ADDR'] ?? '?'); }
// Simple file-based rate limit (per key), good enough for a single-instance deployment.
function rate_file($key){ return sys_get_temp_dir() . '/vax_rl_' . md5($key) . '.json'; }
function rate_hits($key, $window){ $f = rate_file($key); $a = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : []; return array_values(array_filter($a, fn($t) => $t > time() - $window)); }
function rate_blocked($key, $max, $window){ return count(rate_hits($key, $window)) >= $max; }
function rate_add($key, $window){ $a = rate_hits($key, $window); $a[] = time(); @file_put_contents(rate_file($key), json_encode($a), LOCK_EX); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function flash($k){ $v = $_SESSION[$k] ?? null; unset($_SESSION[$k]); return $v; }
// Session messages hold a key from this list (or raw text); msg() turns a key into the sentence shown to the user.
$MESSAGES = [
    'err_login'=>'Wrong username or password.', 'err_locked'=>'Too many attempts. Please try again in 15 minutes.',
    'err_required'=>'All fields are required.', 'err_invalid'=>'Some values are invalid. Please check the form.',
    'err_location'=>'Choose a vaccination location from the list.', 'err_loc_edit'=>'This vaccination location is not one the models know. Choose a location from the list.',
    'err_id_exists'=>'This ID number already exists.', 'err_id_missing'=>'No patient was found with this ID.',
    'err_generic'=>'Something went wrong. Please try again.',
    'err_predict'=>'The prediction could not be completed. Please try again or contact the administrator.',
    'no_vacc'=>'This patient has no vaccination record, so a prediction is not possible. Edit the patient to add one.',
    'ok_added'=>'Patient information saved successfully.', 'ok_updated'=>'Patient information updated.',
    'ok_effects'=>'Side effects saved successfully.', 'ok_effects_generic'=>'Thank you. If this ID is registered, your report has been recorded.',
    'pred_saved'=>'Prediction saved to the patient’s record.',
];
function msg($m){ global $MESSAGES; return $m === null ? null : ($MESSAGES[$m] ?? $m); }
function require_login(){
  if (empty($_SESSION['username']) || time() - ($_SESSION['last'] ?? time()) > 7200) {   // 2 h idle timeout
    $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    header('Location: doctors_login.php'); exit; }
  $_SESSION['last'] = time(); }
function csrf_field(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return '<input type="hidden" name="_csrf" value="' . h($_SESSION['csrf']) . '">'; }
function csrf_check(){ if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(400); exit('Invalid or expired form. Go back, reload the page and try again.'); } }
// The clustering models' label encoders only know these vaccination locations; any other value crashes predictions.
// Keep in sync with label_encoders['Lieu de La vaccination'].classes_ in predictions/Models/*.pkl
function vaccination_sites(){ return ['BADR','Batimetal','Clinique El Farabi','Clinique El Jazair','Dividus','EPSP Annaba','EPSP Annaba (Algérie Poste)','EPSP Annaba (Casnos)','Ibn Nafiss','LTPEst','MTV','SAIDAL','SGA','SIPA','Service MDT','Siege']; }
function avg_predictions($pred){ $norm = []; $avg = []; foreach ($pred as $a => $fx) { if (!is_array($fx)) continue; $sum = array_sum($fx) ?: 1; foreach ($fx as $e => $v) $norm[$e][$a] = round($v / $sum * 100); }
  $n = max(1, count($pred)); foreach ($norm as $e => $v) $avg[$e] = round(array_sum($v) / $n); arsort($avg); return [array_filter($avg, fn($v) => $v >= 1), $norm]; }
function ensure_prediction_table($conn){ $conn->query("CREATE TABLE IF NOT EXISTS Prediction_Result (id INT AUTO_INCREMENT PRIMARY KEY, id_patient VARCHAR(20) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, results MEDIUMTEXT NOT NULL, INDEX idx_pred_patient (id_patient)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }
function icon($n){
  $p = ['users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
  'plus'=>'<path d="M12 5v14M5 12h14"/>','clip'=>'<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 12h6M9 16h4"/>',
  'down'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>','out'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
  'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>','search'=>'<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  'back'=>'<path d="M19 12H5M12 19l-7-7 7-7"/>',
  'check'=>'<path d="M20 6 9 17l-5-5"/>',
  'edit'=>'<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
  'refresh'=>'<path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/>',
  'activity'=>'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
  'history'=>'<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l4 2"/>'];
  return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">'.($p[$n] ?? '').'</svg>';
}
function logo(){ return '<a class="logo" href="index.php"><svg width="28" height="28" viewBox="0 0 28 28" aria-hidden="true"><circle cx="9" cy="10" r="5" fill="#5646ff"/><circle cx="19" cy="9" r="4" fill="#ff6a55"/><circle cx="15" cy="20" r="5" fill="#12b886"/></svg>VaxCluster</a>'; }
function open_page($title, $mode = 'public', $active = '', $heading = '', $actions = ''){ ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> · VaxCluster</title><meta name="color-scheme" content="light dark">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css"></head><body>
<?php if ($mode === 'app'): $nav = ['list'=>['patients_list.php','users','Patients'],'add'=>['add_patient.php','plus','New patient'],'report'=>['patient_form.php','clip','Report side effects'],'csv'=>['export_csv.php','down','Export CSV'],'xlsx'=>['export_xlsx.php','down','Export Excel']]; ?>
<div class="shell"><aside class="side"><?= logo() ?>
<?php foreach ($nav as $k => $n): ?><a class="n <?= $active === $k ? 'on' : '' ?>" href="<?= $n[0] ?>"><?= icon($n[1]) ?><?= $n[2] ?></a><?php endforeach; ?>
<div class="who"><span class="muted small">Signed in as</span><b><?= h($_SESSION['username'] ?? '') ?></b><a class="small" href="logout.php">Log out</a></div></aside>
<div class="main"><header><h1><?= h($heading ?: $title) ?></h1><div><?= $actions ?></div></header>
<?php else: ?>
<nav class="top" aria-label="Main"><div><?= logo() ?>
<a class="l" href="index.php#coronavirus">What is COVID-19?</a><a class="l" href="index.php#prevention">Prevention</a><a class="l" href="index.php#symptoms">Symptoms</a><a class="l" href="patient_form.php">Report side effects</a>
<?php if (!empty($_SESSION['username'])): ?><a class="btn sm" href="patients_list.php">Dashboard</a><?php else: ?><a class="btn sm" href="doctors_login.php">Doctor login</a><?php endif; ?></div></nav>
<?php endif; }
function close_page($mode = 'public'){
  if ($mode === 'app') { echo '</div></div>'; }
  else { ?><footer class="foot"><div class="wrap"><img src="images/Badji_Mokhtar_-_Annaba_University_Logo.png" alt="Badji Mokhtar Annaba University"><img src="images/Logo_LRI.png" alt="LRI laboratory">
<p>Master's thesis project, Computer Science Department, Badji Mokhtar University, Annaba. Predictions come from clustering models and support clinical judgment; they are not a diagnosis.</p>
<a href="https://www.univ-annaba.dz/" target="_blank" rel="noopener">University website</a></div></footer><?php }
  echo '</body></html>'; }
