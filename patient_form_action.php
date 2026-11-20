<?php
session_start();

if(isset($_POST['btn_submit']))
{
    require('./configuration/db_connection.php');

    $idnum = mysqli_real_escape_string($conn, $_POST['idnum']);

    $checkSql = "SELECT * FROM Patient WHERE id_patient = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $idnum);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if($checkResult->num_rows > 0) {

        $sideEffects = isset($_POST['side_effects']) ? $_POST['side_effects'] : [];
        $dateAppar = isset($_POST['dateappar']) ? $_POST['dateappar'] : '';
        $dureeManif = isset($_POST['dureemanif']) ? $_POST['dureemanif'] : '';

        $vaccinationIdSql = "SELECT id_vaccination FROM Patient_Vaccination WHERE id_patient = ? ORDER BY id_vaccination DESC LIMIT 1";
        $stmt = $conn->prepare($vaccinationIdSql);
        $stmt->bind_param("s", $idnum);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $lastInsertedVaccinationId = $row['id_vaccination'];

        foreach ($sideEffects as $effect) {
            if (!empty($effect)) {
                $checkEffectSql = "SELECT * FROM Effet_Secondaire WHERE description = ?";
                $stmt = $conn->prepare($checkEffectSql);
                $stmt->bind_param("s", $effect);
                $stmt->execute();
                $checkEffectResult = $stmt->get_result();

                if ($checkEffectResult->num_rows == 0) {
                    $insertEffectSql = "INSERT INTO Effet_Secondaire (description) VALUES (?)";
                    $stmt = $conn->prepare($insertEffectSql);
                    $stmt->bind_param("s", $effect);
                    if (!$stmt->execute()) {
                        echo "Error: " . $insertEffectSql . "<br>" . $conn->error;
                    }
                }

                $sideEffectIdSql = "SELECT id_effet FROM Effet_Secondaire WHERE description = ?";
                $stmt = $conn->prepare($sideEffectIdSql);
                $stmt->bind_param("s", $effect);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $sideEffectId = $row['id_effet'];

                $insertRelSql = "INSERT INTO Vaccination_Effet_Secondaire (id_vaccination, id_effet) VALUES (?, ?)";
                $stmt = $conn->prepare($insertRelSql);
                $stmt->bind_param("ii", $lastInsertedVaccinationId, $sideEffectId);
                if (!$stmt->execute()) {
                    echo "Error: " . $insertRelSql . "<br>" . $conn->error;
                }
                $updateVaccinationSql = "UPDATE Patient_Vaccination SET date_apparition = ?, duree_manifestation = ? WHERE id_vaccination = ?";
                $stmt = $conn->prepare($updateVaccinationSql);
                $stmt->bind_param("sii", $dateAppar, $dureeManif, $lastInsertedVaccinationId);
                if (!$stmt->execute()) {
                    echo "Error: " . $updateVaccinationSql . "<br>" . $conn->error;
                }
            }
        }

        $_SESSION['success_message'] = "Side effects updated successfully";
        header("Location: ./patient_form.php");
        exit();
    } else {
        $_SESSION['error_message'] = "The provided ID number does not exist in the database.";
        header("Location: ./patient_form.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>