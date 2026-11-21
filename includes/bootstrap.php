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
    'err_login'=>'Nom d’utilisateur ou mot de passe incorrect.', 'err_locked'=>'Trop de tentatives. Réessayez dans 15 minutes.',
    'err_required'=>'Tous les champs sont obligatoires.', 'err_invalid'=>'Certaines valeurs sont invalides. Vérifiez le formulaire.',
    'err_location'=>'Choisissez un lieu de vaccination dans la liste.', 'err_loc_edit'=>'Ce lieu de vaccination n’est pas reconnu par les modèles. Choisissez un lieu dans la liste.',
    'err_id_exists'=>'Ce numéro d’identité existe déjà.', 'err_id_missing'=>'Aucun patient n’a été trouvé avec ce numéro.',
    'err_generic'=>'Une erreur s’est produite. Veuillez réessayer.',
    'err_predict'=>'La prédiction n’a pas pu être effectuée. Réessayez ou contactez l’administrateur.',
    'no_vacc'=>'Ce patient n’a aucune vaccination enregistrée : la prédiction est impossible. Modifiez le patient pour en ajouter une.',
    'ok_added'=>'Informations du patient enregistrées avec succès.', 'ok_updated'=>'Informations du patient mises à jour.',
    'ok_effects'=>'Effets secondaires enregistrés avec succès.', 'ok_effects_generic'=>'Merci. Si ce numéro est enregistré, votre déclaration a été prise en compte.',
    'pred_saved'=>'Prédiction enregistrée dans le dossier du patient.',
];
// Stored values (side effects, conditions, vaccines, gender...) are displayed in properly accented French.
function norm($s){ return strtr(mb_strtolower(trim((string)$s), 'UTF-8'), ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ô'=>'o','î'=>'i','ï'=>'i','ç'=>'c','û'=>'u','ù'=>'u']); }
$D = ['diarhee'=>'Diarrhée','diarrhee'=>'Diarrhée','fievre'=>'Fièvre','frisson'=>'Frissons','maux de tete'=>'Maux de tête','nausees'=>'Nausées','neuralgie'=>'Névralgie','neveralgie'=>'Névralgie','toux'=>'Toux','allergie cutanee'=>'Allergie cutanée','ecoulement'=>'Écoulement','meningite'=>'Méningite','metrorragie'=>'Métrorragie','syndrom grippal'=>'Syndrome grippal','anemie'=>'Anémie','cardiaque'=>'Cardiaque','diabete type 1'=>'Diabète type 1','diabete type 2'=>'Diabète type 2','dyslipidemie'=>'Dyslipidémie','hemiplegie'=>'Hémiplégie','hypothyroidie'=>'Hypothyroïdie','insuffisance renale'=>'Insuffisance rénale','no background'=>'Aucun antécédent','male'=>'Homme','female'=>'Femme','right'=>'Bras droit','left'=>'Bras gauche','1st'=>'1re injection','2nd'=>'2e injection','spring'=>'Printemps','summer'=>'Été','autumn'=>'Automne','fall'=>'Automne','winter'=>'Hiver','sputnik-v-'=>'Sputnik V','astra zeneca'=>'AstraZeneca'];
function td($s){ global $D; return $D[norm($s)] ?? (string)$s; }
// 2026-10-31 or 2026-10-31 14:05:09  ->  31/10/2026 or 31/10/2026 14:05
function fdate($s){ $s = trim((string)$s); if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/', $s, $m)) return $m[3].'/'.$m[2].'/'.$m[1].(isset($m[4]) ? ' '.$m[4].':'.$m[5] : ''); return $s; }
function msg($m){ global $MESSAGES; return $m === null ? null : ($MESSAGES[$m] ?? $m); }
function require_login(){
  if (empty($_SESSION['username']) || time() - ($_SESSION['last'] ?? time()) > 7200) {   // 2 h idle timeout
    $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    header('Location: doctors_login.php'); exit; }
  $_SESSION['last'] = time(); }
function csrf_field(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return '<input type="hidden" name="_csrf" value="' . h($_SESSION['csrf']) . '">'; }
function csrf_check(){ if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(400); exit('Formulaire invalide ou expiré. Revenez en arrière, rechargez la page et réessayez.'); } }
// The clustering models' label encoders only know these vaccination locations; any other value crashes predictions.
// Keep in sync with label_encoders['Lieu de La vaccination'].classes_ in predictions/Models/*.pkl
function vaccination_sites(){ return ['BADR','Batimetal','Clinique El Farabi','Clinique El Jazair','Dividus','EPSP Annaba','EPSP Annaba (Algérie Poste)','EPSP Annaba (Casnos)','Ibn Nafiss','LTPEst','MTV','SAIDAL','SGA','SIPA','Service MDT','Siege']; }
function avg_predictions($pred){ $norm = []; $avg = []; foreach ($pred as $a => $fx) { if (!is_array($fx)) continue; $sum = array_sum($fx) ?: 1; foreach ($fx as $e => $v) $norm[$e][$a] = round($v / $sum * 100); }
  $n = max(1, count($pred)); foreach ($norm as $e => $v) $avg[$e] = round(array_sum($v) / $n); arsort($avg); return [array_filter($avg, fn($v) => $v >= 1), $norm]; }
function ensure_prediction_table($conn){ $conn->query("CREATE TABLE IF NOT EXISTS Prediction_Result (id INT AUTO_INCREMENT PRIMARY KEY, id_patient VARCHAR(20) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, results MEDIUMTEXT NOT NULL, INDEX idx_pred_patient (id_patient)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }
function icon($n){ static $p = [
  'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  'plus'=>'<path d="M12 5v14M5 12h14"/>',
  'clip'=>'<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 12h6M9 16h4"/>',
  'down'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
  'out'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
  'login'=>'<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
  'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>',
  'search'=>'<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  'back'=>'<path d="M19 12H5M12 19l-7-7 7-7"/>',
  'check'=>'<path d="M20 6 9 17l-5-5"/>',
  'x'=>'<path d="M18 6 6 18M6 6l12 12"/>',
  'edit'=>'<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
  'refresh'=>'<path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/>',
  'activity'=>'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
  'history'=>'<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l4 2"/>',
  'user'=>'<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  'userplus'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
  'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
  'info'=>'<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
  'home'=>'<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
  'thermo'=>'<path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>',
  'virus'=>'<circle cx="12" cy="12" r="5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
  'file'=>'<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
  'table'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/>',
  'panel'=>'<path d="m15 18-6-6 6-6"/>',
  'calendar'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  'syringe'=>'<path d="m18 2 4 4M17 7l3-3M19 9 8.7 19.3a1 1 0 0 1-1.4 0L4.7 16.7a1 1 0 0 1 0-1.4L15 5M9 11l4 4M5 19l-3 3M14 4l6 6"/>',
  'pin'=>'<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  'phone'=>'<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
  'lock'=>'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
  'bar'=>'<path d="M12 20V10M18 20V4M6 20v-4"/>',
  'layers'=>'<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
  'school'=>'<path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
  'external'=>'<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
  'hand'=>'<path d="M18 11V6a2 2 0 0 0-4 0v5M14 10V4a2 2 0 0 0-4 0v6M10 10.5V6a2 2 0 0 0-4 0v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.9-5.9-2.4L3.4 16a2 2 0 0 1 3.2-2.4L8 15"/>',
];
  return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">'.($p[$n] ?? '').'</svg>';
}
// round icon "bubble" placed before titles
function ti($n){ return '<span class="ti">'.icon($n).'</span>'; }
function logo(){ return '<a class="logo" href="index.php"><svg width="28" height="28" viewBox="0 0 28 28" aria-hidden="true"><circle cx="9" cy="10" r="5" fill="#2563eb"/><circle cx="19" cy="9" r="4" fill="#ff6a55"/><circle cx="15" cy="20" r="5" fill="#12b886"/></svg><span>VaxCluster</span></a>'; }
function open_page($title, $mode = 'public', $active = '', $heading = '', $actions = '', $ic = null){
  $icons = ['list'=>'users','add'=>'userplus','report'=>'clip'];
  $ic = $ic ?: ($icons[$active] ?? 'activity'); ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> - VaxCluster</title><meta name="color-scheme" content="light dark"><meta name="theme-color" content="#2563eb">
<meta name="description" content="VaxCluster : prédiction des effets secondaires des vaccins contre la COVID-19 par clustering.">
<link rel="icon" href="favicon.svg" type="image/svg+xml"><link rel="alternate icon" href="favicon.ico" sizes="any"><link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
<script nonce="<?= nonce() ?>">try{if(localStorage.getItem('vaxSide')==='1')document.documentElement.classList.add('side-min')}catch(e){}</script>
<script src="assets/js/ui.js" defer></script></head><body>
<?php if ($mode === 'app'): $nav = ['list'=>['patients_list.php','users','Patients'],'add'=>['add_patient.php','userplus','Nouveau patient'],'report'=>['patient_form.php','clip','Effets secondaires'],'csv'=>['export_csv.php','down','Exporter en CSV'],'xlsx'=>['export_xlsx.php','table','Exporter en Excel']]; ?>
<div class="shell"><aside class="side"><div class="side-head"><?= logo() ?><button type="button" class="coll" id="coll" aria-label="Réduire le menu" aria-expanded="true" title="Réduire / agrandir le menu"><?= icon('panel') ?></button></div>
<?php foreach ($nav as $k => $n): ?><a class="n <?= $active === $k ? 'on' : '' ?>" href="<?= $n[0] ?>" title="<?= h($n[2]) ?>"><?= icon($n[1]) ?><span class="lb"><?= h($n[2]) ?></span></a><?php endforeach; ?>
<div class="who"><span class="muted small">Connecté en tant que</span><b><?= h($_SESSION['username'] ?? '') ?></b><a class="small" href="logout.php">Se déconnecter</a></div>
<a class="out-min" href="logout.php" title="Se déconnecter" aria-label="Se déconnecter"><?= icon('out') ?></a></aside>
<div class="main"><header><h1><?= ti($ic) ?><?= h($heading ?: $title) ?></h1><div><?= $actions ?></div></header>
<?php else: ?>
<nav class="top" aria-label="Navigation principale"><div><?= logo() ?>
<a class="l" href="index.php#coronavirus">Qu’est-ce que la COVID-19 ?</a><a class="l" href="index.php#prevention">Prévention</a><a class="l" href="index.php#symptoms">Symptômes</a><a class="l" href="patient_form.php">Déclarer un effet secondaire</a>
<?php if (!empty($_SESSION['username'])): ?><a class="btn sm" href="patients_list.php"><?= icon('bar') ?>Tableau de bord</a><?php else: ?><a class="btn sm" href="doctors_login.php"><?= icon('login') ?>Connexion médecin</a><?php endif; ?></div></nav>
<?php endif; }
function close_page($mode = 'public'){
  if ($mode === 'app') { ?><div class="grow"></div><footer class="app-foot"><img src="images/Logo_EPSP_Annaba.png" alt="Logo de l’EPSP Annaba"><span><b>VaxCluster</b> · Projet de fin d’études de Master (2024) de <b>Abdeldjalil Halisse</b>, réalisé lors d’un stage à l’<b>EPSP Annaba</b>.</span></footer></div></div><?php }
  else { ?><footer class="foot"><div class="wrap"><div class="grid2">
<div class="foot-logos"><img class="round" src="images/Logo_EPSP_Annaba.png" alt="Logo de l’EPSP Annaba"><img class="sq" src="images/Badji_Mokhtar_-_Annaba_University_Logo.png" alt="Université Badji Mokhtar Annaba"><img class="sq" src="images/Logo_LRI.png" alt="Laboratoire LRI"></div>
<div><p class="foot-t"><?= icon('syringe') ?>VaxCluster · Projet de fin d’études de Master, 2024</p>
<p>Conçu et développé par <b>Abdeldjalil Halisse</b> dans le cadre de son projet de fin d’études de Master, réalisé lors d’un stage au sein de l’<b>EPSP Annaba</b> (Établissement Public de Santé de Proximité).</p>
<p>Université Badji Mokhtar – Annaba · Département d’informatique · Laboratoire LRI.</p>
<p class="small">Les prédictions proviennent de modèles de clustering et appuient le jugement clinique ; elles ne constituent pas un diagnostic.</p></div>
<div class="foot-links"><a href="https://www.univ-annaba.dz/" target="_blank" rel="noopener"><?= icon('school') ?>Site de l’université<?= icon('external') ?></a><a href="patient_form.php"><?= icon('clip') ?>Déclarer un effet secondaire</a><a href="doctors_login.php"><?= icon('login') ?>Connexion médecin</a></div>
</div><div class="foot-bar">© 2024 Abdeldjalil Halisse · Projet de Master, Université Badji Mokhtar, Annaba · EPSP Annaba</div></div></footer><?php }
  echo '</body></html>'; }
