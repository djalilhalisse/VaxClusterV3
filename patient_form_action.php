<?php
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn_submit'])) { header('Location: patient_form.php'); exit; }
csrf_check();
$back = 'patient_form.php';
$staff = !empty($_SESSION['username']);
$rkey = 'report:' . client_ip();
if (!$staff && rate_blocked($rkey, 8, 900)) { $_SESSION['error_message'] = 'err_locked'; header("Location: $back"); exit; }   // public form: 8 reports / 15 min per visitor
if (!$staff) rate_add($rkey, 900);

$idnum = trim($_POST['idnum'] ?? '');
$dateAppar = $_POST['dateappar'] ?? ''; $duree = (int)($_POST['dureemanif'] ?? 0);
$d = DateTime::createFromFormat('Y-m-d', $dateAppar);
$effects = array_values(array_unique(array_filter(array_map(fn($e) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$e)), 0, 60), (array)($_POST['side_effects'] ?? [])), 'strlen')));
$effects = array_slice($effects, 0, 10);
if (!ctype_digit($idnum) || strlen($idnum) > 20 || !$d || $d->format('Y-m-d') !== $dateAppar || $dateAppar > date('Y-m-d') || $duree < 1 || $duree > 365 || !$effects) {
    $_SESSION['error_message'] = 'err_invalid'; header("Location: $back"); exit;
}
require __DIR__ . '/configuration/db_connection.php';
$st = $conn->prepare("SELECT id_vaccination FROM Patient_Vaccination WHERE id_patient = ? ORDER BY id_vaccination DESC LIMIT 1");
$st->bind_param('s', $idnum); $st->execute();
$row = $st->get_result()->fetch_assoc();
if (!$row) {   // unknown ID: staff are told; the public form answers the same way as for a valid ID (no ID enumeration)
    $_SESSION[$staff ? 'error_message' : 'success_message'] = $staff ? 'err_id_missing' : 'ok_effects_generic'; header("Location: $back"); exit;
}
$vid = (int)$row['id_vaccination'];
$conn->begin_transaction();
try {
    $find = $conn->prepare("SELECT id_effet FROM Effet_Secondaire WHERE description = ?");
    $ins  = $conn->prepare("INSERT INTO Effet_Secondaire (description) VALUES (?)");
    $has  = $conn->prepare("SELECT 1 FROM Vaccination_Effet_Secondaire WHERE id_vaccination = ? AND id_effet = ?");
    $rel  = $conn->prepare("INSERT INTO Vaccination_Effet_Secondaire (id_vaccination, id_effet) VALUES (?, ?)");
    foreach ($effects as $e) {
        $find->bind_param('s', $e); $find->execute(); $r = $find->get_result()->fetch_assoc();
        if ($r) { $eid = (int)$r['id_effet']; } else { $ins->bind_param('s', $e); $ins->execute(); $eid = $conn->insert_id; }
        $has->bind_param('ii', $vid, $eid); $has->execute();
        if ($has->get_result()->num_rows === 0) { $rel->bind_param('ii', $vid, $eid); $rel->execute(); }
    }
    $up = $conn->prepare("UPDATE Patient_Vaccination SET date_apparition = ?, duree_manifestation = ? WHERE id_vaccination = ? AND id_patient = ?");
    $up->bind_param('siis', $dateAppar, $duree, $vid, $idnum); $up->execute();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback(); error_log('patient_form failed: ' . $e->getMessage());
    $_SESSION['error_message'] = 'err_generic'; header("Location: $back"); exit;
}
$_SESSION['success_message'] = $staff ? 'ok_effects' : 'ok_effects_generic';
header("Location: $back"); exit;
