<?php
// Export Excel (.xlsx) de tous les patients : une feuille « Patients » mise en forme et une feuille « Synthèse ».
require __DIR__ . '/includes/bootstrap.php';
require_login();
session_write_close();

require __DIR__ . '/configuration/db_connection.php';
require __DIR__ . '/includes/xlsx.php';

// « | » sépare les éléments des listes pour préserver les virgules des valeurs.
$sql = "SELECT
            Patient.id_patient, Patient.nom, Patient.sexe, Patient.age, Patient.adresse, Patient.tel,
            Vaccination.type_vaccin, Vaccination.num_lot, Vaccination.date_peremption, Vaccination.lieu_vaccination,
            Vaccination.date_vaccination, Vaccination.heure_vaccination, Vaccination.site, Vaccination.dose,
            GROUP_CONCAT(DISTINCT Maladie_Chronique.nom SEPARATOR '|') AS antecedents,
            GROUP_CONCAT(DISTINCT Effet_Secondaire.description SEPARATOR '|') AS side_effects,
            Patient.createdate, Vaccination.season
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
if ($result === false) {
    error_log('XLSX export query failed: ' . mysqli_error($conn));
    http_response_code(500);
    die('L’export a échoué.');
}
$rows = [];
while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; }

$bin = xlsx_patients_workbook($rows);

while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="vaxcluster_patients_' . gmdate('Y-m-d') . '.xlsx"');
header('Content-Length: ' . strlen($bin));
header('Cache-Control: no-store');
echo $bin;
