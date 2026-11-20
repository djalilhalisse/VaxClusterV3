<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function flash($k){ $v = $_SESSION[$k] ?? null; unset($_SESSION[$k]); return $v; }
function require_login(){ if (empty($_SESSION['username'])) { header('Location: doctors_login.php'); exit; } }
function icon($n){
  $p = ['users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
  'plus'=>'<path d="M12 5v14M5 12h14"/>','clip'=>'<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 12h6M9 16h4"/>',
  'down'=>'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>','out'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
  'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>','search'=>'<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>'];
  return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">'.($p[$n] ?? '').'</svg>';
}
function logo(){ return '<a class="logo" href="index.php"><svg width="28" height="28" viewBox="0 0 28 28" aria-hidden="true"><circle cx="9" cy="10" r="5" fill="#5646ff"/><circle cx="19" cy="9" r="4" fill="#ff6a55"/><circle cx="15" cy="20" r="5" fill="#12b886"/></svg>VaxCluster</a>'; }
function open_page($title, $mode = 'public', $active = '', $heading = '', $actions = ''){ ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> · VaxCluster</title><meta name="color-scheme" content="light dark">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css"></head><body>
<?php if ($mode === 'app'): $nav = ['list'=>['patients_list.php','users','Patients'],'add'=>['add_patient.php','plus','New patient'],'report'=>['patient_form.php','clip','Report side effects'],'csv'=>['export_csv.php','down','Export CSV']]; ?>
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
