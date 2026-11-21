<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

// Neutralise spreadsheet formulas (=, +, @ ...) so exported text can't run in Excel.
function csv_safe($v){ $v = (string)$v; return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !preg_match('/^[+-]?[0-9][0-9 .\-]*$/', $v)) ? "'" . $v : $v; }

$content = fopen('php://temp', 'r+');
$headers = ['Numéro d’identité', 'Nom', 'Sexe', 'Âge', 'Adresse', 'Téléphone', 'Vaccin', 'Numéro de lot', 'Date de péremption', 'Lieu de vaccination', 'Date de vaccination', 'Heure de vaccination', 'Site d’injection', 'Dose', 'Antécédents médicaux', 'Effets secondaires', 'Date d’ajout', 'Saison de vaccination'];
// Excel en français attend « ; » comme séparateur de colonnes
$tl = function ($v) { return $v === null || $v === '' ? '' : implode(', ', array_map('td', explode(', ', $v))); };
fputcsv($content, $headers, ';');

require('./configuration/db_connection.php');
$sql = "SELECT 
            Patient.id_patient, 
            Patient.nom, 
            Patient.sexe, 
            Patient.age, 
            Patient.adresse, 
            Patient.tel, 
            Vaccination.type_vaccin, 
            Vaccination.num_lot, 
            Vaccination.date_peremption, 
            Vaccination.lieu_vaccination, 
            Vaccination.date_vaccination, 
            Vaccination.heure_vaccination, 
            Vaccination.site, 
            Vaccination.dose, 
            GROUP_CONCAT(DISTINCT Maladie_Chronique.nom SEPARATOR ', ') AS antecedents, 
            GROUP_CONCAT(DISTINCT Effet_Secondaire.description SEPARATOR ', ') AS side_effects, 
            Patient.createdate, 
            Vaccination.season
        FROM Patient
        LEFT JOIN Patient_Maladie_Chronique ON Patient.id_patient = Patient_Maladie_Chronique.id_patient
        LEFT JOIN Maladie_Chronique ON Patient_Maladie_Chronique.id_maladie = Maladie_Chronique.id_maladie
        LEFT JOIN Patient_Vaccination ON Patient.id_patient = Patient_Vaccination.id_patient
        LEFT JOIN Vaccination ON Patient_Vaccination.id_vaccination = Vaccination.id_vaccination
        LEFT JOIN Vaccination_Effet_Secondaire ON Vaccination.id_vaccination = Vaccination_Effet_Secondaire.id_vaccination
        LEFT JOIN Effet_Secondaire ON Effet_Secondaire.id_effet = Vaccination_Effet_Secondaire.id_effet
        GROUP BY Patient.id_patient
        ORDER BY Patient.createdate DESC";

$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rowData = [
            $row['id_patient'],
            $row['nom'],
            td($row['sexe']),
            $row['age'],
            $row['adresse'],
            $row['tel'],
            td($row['type_vaccin']),
            $row['num_lot'],
            fdate($row['date_peremption']),
            $row['lieu_vaccination'],
            fdate($row['date_vaccination']),
            $row['heure_vaccination'],
            td($row['site']),
            td($row['dose']),
            $tl($row['antecedents']) ?: 'Aucun antécédent',
            $tl($row['side_effects']),
            fdate($row['createdate']),
            td($row['season']),
        ];
        fputcsv($content, array_map('csv_safe', $rowData), ';');
    }
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="patients_vaxcluster.csv"');

echo "\xEF\xBB\xBF";
rewind($content);
fpassthru($content);
fclose($content);
?>
