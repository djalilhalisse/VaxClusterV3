<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/includes/patient_data.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: patients_list.php'); exit; }
csrf_check();
require __DIR__ . '/configuration/db_connection.php';

$d = read_patient_post();
$id = trim((string)($_POST['idnum'] ?? ''));
$back = 'edit_patient.php?id=' . urlencode($id);
if (!$d) { $_SESSION['error_message'] = 'err_invalid'; header("Location: $back"); exit; }
$cur = load_patient($conn, $d['idnum']);
if (!$cur) { header('Location: patients_list.php'); exit; }
$vid = $cur[0]['id_vaccination'] ? (int)$cur[0]['id_vaccination'] : 0;

$conn->begin_transaction();
try {
    $st = $conn->prepare("UPDATE Patient SET nom = ?, age = ?, sexe = ?, adresse = ?, tel = ? WHERE id_patient = ?");
    $st->bind_param('sissss', $d['name'], $d['age'], $d['gender'], $d['adresse'], $d['tel'], $d['idnum']); $st->execute();

    if ($vid) {   // update the patient's current vaccination record
        $st = $conn->prepare("UPDATE Vaccination SET type_vaccin = ?, num_lot = ?, date_peremption = ?, lieu_vaccination = ?, date_vaccination = ?, heure_vaccination = ?, site = ?, dose = ?, season = ? WHERE id_vaccination = ?");
        $st->bind_param('sssssssssi', $d['vaccin'], $d['lot'], $d['peremption'], $d['lieuvac'], $d['datevac'], $d['heurevac'], $d['site'], $d['dose'], $d['season'], $vid); $st->execute();
    } else {      // legacy patient without a vaccination record: create one
        $st = $conn->prepare("INSERT INTO Vaccination (type_vaccin, num_lot, date_peremption, lieu_vaccination, date_vaccination, heure_vaccination, site, dose, season) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $st->bind_param('sssssssss', $d['vaccin'], $d['lot'], $d['peremption'], $d['lieuvac'], $d['datevac'], $d['heurevac'], $d['site'], $d['dose'], $d['season']); $st->execute();
        $vid = $conn->insert_id;
        $st = $conn->prepare("INSERT INTO Patient_Vaccination (id_patient, id_vaccination) VALUES (?, ?)");
        $st->bind_param('si', $d['idnum'], $vid); $st->execute();
    }
    // medical history: replace with the submitted list
    $st = $conn->prepare("DELETE FROM Patient_Maladie_Chronique WHERE id_patient = ?");
    $st->bind_param('s', $d['idnum']); $st->execute();
    $link = $conn->prepare("INSERT INTO Patient_Maladie_Chronique (id_patient, id_maladie) VALUES (?, ?)");
    $find = $conn->prepare("SELECT id_maladie FROM Maladie_Chronique WHERE nom = ?");
    $ins  = $conn->prepare("INSERT INTO Maladie_Chronique (nom) VALUES (?)");
    $names = $d['antecedents'] + [];
    foreach (array_merge($d['antecedents'], $d['new']) as $nom) {
        if (isset($seen[mb_strtolower($nom)])) continue; $seen[mb_strtolower($nom)] = 1;
        $find->bind_param('s', $nom); $find->execute(); $r = $find->get_result()->fetch_assoc();
        if ($r) { $mid = (int)$r['id_maladie']; } else { $ins->bind_param('s', $nom); $ins->execute(); $mid = $conn->insert_id; }
        $link->bind_param('si', $d['idnum'], $mid); $link->execute();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback(); error_log('edit_patient failed: ' . $e->getMessage());
    $_SESSION['error_message'] = 'err_generic'; header("Location: $back"); exit;
}
$_SESSION['success_message'] = 'ok_updated';
header('Location: patient_details.php?id=' . urlencode($d['idnum'])); exit;
