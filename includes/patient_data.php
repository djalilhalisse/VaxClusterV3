<?php
// Shared patient helpers (used by edit and prediction pages).
// The Python scripts split the history on "," WITHOUT trimming, so conditions must be joined with a plain comma.
const HISTORY_SEP = ',';
function season_of($date){ $m = (int)date('n', strtotime($date)); return $m >= 3 && $m <= 5 ? 'Spring' : ($m >= 6 && $m <= 8 ? 'Summer' : ($m >= 9 && $m <= 11 ? 'Autumn' : 'Winter')); }

// Patient + latest vaccination + condition names. Returns [row, conditions] or null.
function load_patient($conn, $id){
    $st = $conn->prepare("SELECT p.id_patient, p.nom, p.age, p.sexe, p.adresse, p.tel, v.id_vaccination, v.type_vaccin, v.num_lot, v.date_peremption, v.lieu_vaccination, v.date_vaccination, v.heure_vaccination, v.site, v.dose, v.season
        FROM Patient p LEFT JOIN Patient_Vaccination pv ON pv.id_patient = p.id_patient LEFT JOIN Vaccination v ON v.id_vaccination = pv.id_vaccination
        WHERE p.id_patient = ? ORDER BY v.id_vaccination DESC LIMIT 1");
    $st->bind_param('s', $id); $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) return null;
    $q = $conn->prepare("SELECT m.nom FROM Patient_Maladie_Chronique pm JOIN Maladie_Chronique m ON m.id_maladie = pm.id_maladie WHERE pm.id_patient = ? ORDER BY m.nom");
    $q->bind_param('s', $id); $q->execute();
    $conds = array_column($q->get_result()->fetch_all(MYSQLI_ASSOC), 'nom');
    return [$row, $conds];
}
// Same array shape the add-patient flow stores in the session (what predicts.php expects).
function prediction_info($row, $conds){
    $c = $conds ?: ['No Background'];
    return ['idnum'=>$row['id_patient'],'name'=>$row['nom'],'gender'=>$row['sexe'],'age'=>$row['age'],'adresse'=>$row['adresse'],'tel'=>$row['tel'],'vaccin'=>$row['type_vaccin'],
        'lot'=>$row['num_lot'],'peremption'=>$row['date_peremption'],'lieuvac'=>$row['lieu_vaccination'],'datevac'=>$row['date_vaccination'],'heurevac'=>$row['heure_vaccination'],
        'site'=>$row['site'],'dose'=>$row['dose'],'antecedents'=>$c,'atc'=>implode(HISTORY_SEP, $c),'season'=>$row['season'] ?: season_of($row['date_vaccination'])];
}
// Validated patient form data, or null when something is invalid.
function read_patient_post(){
    $f = fn($k) => trim((string)($_POST[$k] ?? ''));
    $d = ['idnum'=>$f('idnum'),'name'=>$f('name'),'gender'=>$f('gender'),'age'=>$f('age'),'adresse'=>$f('adresse'),'tel'=>$f('tel'),'vaccin'=>$f('vaccin'),'lot'=>$f('numlot'),
          'peremption'=>$f('dateperemp'),'lieuvac'=>$f('lieuvac'),'datevac'=>$f('datevac'),'heurevac'=>$f('timevac'),'site'=>$f('Site'),'dose'=>$f('dose')];
    $vd = fn($x) => ($o = DateTime::createFromFormat('Y-m-d', $x)) && $o->format('Y-m-d') === $x;
    if (in_array('', $d, true) || !ctype_digit($d['idnum']) || strlen($d['idnum']) > 20 || !ctype_digit($d['age']) || (int)$d['age'] > 120
        || !in_array($d['gender'], ['Male', 'Female'], true) || !in_array($d['vaccin'], ['Astra Zeneca', 'Sinopharm', 'Sputnik-v-', 'Sinovac'], true)
        || !in_array($d['dose'], ['1st', '2nd'], true) || !in_array($d['site'], ['right', 'left'], true) || !in_array($d['lieuvac'], vaccination_sites(), true)
        || !$vd($d['peremption']) || !$vd($d['datevac']) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $d['heurevac'])
        || mb_strlen($d['name']) > 100 || mb_strlen($d['adresse']) > 200 || !preg_match('/^[0-9+\s().-]{6,20}$/', $d['tel']) || mb_strlen($d['lot']) > 50) return null;
    $cut = fn($a) => array_values(array_unique(array_filter(array_map(fn($x) => mb_substr(trim((string)$x), 0, 60), (array)$a), 'strlen')));
    $d['antecedents'] = array_slice($cut($_POST['antecedents'] ?? []), 0, 30);
    $d['new'] = array_slice($cut($_POST['new_antecedents'] ?? []), 0, 10);
    $d['season'] = season_of($d['datevac']);
    return $d;
}
