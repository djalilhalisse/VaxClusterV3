<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require('./configuration/db_connection.php');

    $idnum = htmlspecialchars(trim($_POST['idnum']));
    $name = htmlspecialchars(trim($_POST['name']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $age = htmlspecialchars(trim($_POST['age']));
    $adresse = htmlspecialchars(trim($_POST['adresse']));
    $tel = htmlspecialchars(trim($_POST['tel']));
    $vaccin = htmlspecialchars(trim($_POST['vaccin']));
    $lot = htmlspecialchars(trim($_POST['numlot']));
    $peremption = htmlspecialchars(trim($_POST['dateperemp']));
    $lieuvac = htmlspecialchars(trim($_POST['lieuvac']));
    $datevac = htmlspecialchars(trim($_POST['datevac']));
    $heurevac = htmlspecialchars(trim($_POST['timevac']));
    $site = htmlspecialchars(trim($_POST['Site']));
    $dose = htmlspecialchars(trim($_POST['dose']));
    $antecedents = isset($_POST['antecedents']) ? $_POST['antecedents'] : [];
    $new_antecedents = isset($_POST['new_antecedents']) ? array_filter(array_map('trim', $_POST['new_antecedents'])) : []; // Trim and filter empty values

    $all_antecedents = array_merge($antecedents, $new_antecedents);

    $unique_antecedents = array_unique($all_antecedents);

    $antecedents_string = implode(', ', $unique_antecedents);

    $season = getSeason($datevac);

    if (empty($idnum) || empty($name) || empty($gender) || empty($age) || empty($adresse) || empty($tel) || empty($vaccin) || empty($lot) || empty($peremption) || empty($lieuvac) || empty($datevac) || empty($heurevac) || empty($site) || empty($dose)) {
        echo '<script>alert("All fields are required."); window.location="./add_patient.php";</script>';
        exit();
    }

    $sql_check_idnum = "SELECT id_patient FROM Patient WHERE id_patient = ?";
    $stmt_check_idnum = $conn->prepare($sql_check_idnum);
    $stmt_check_idnum->bind_param("s", $idnum);
    $stmt_check_idnum->execute();
    $stmt_check_idnum->store_result();
    if ($stmt_check_idnum->num_rows > 0) {
        $stmt_check_idnum->close();

        $_SESSION['error_message'] = "ID Number already exists.";
        header("Location: ./add_patient.php?error=idnum_exists&value=' . $idnum . '");
        exit();
    }
    $stmt_check_idnum->close();

    $conn->begin_transaction();

    try {
        // Insert into Patient table
        $sql_patient = "INSERT INTO Patient (id_patient, nom, age, sexe, adresse, tel) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_patient = $conn->prepare($sql_patient);
        $stmt_patient->bind_param("ssisss", $idnum, $name, $age, $gender, $adresse, $tel);
        $stmt_patient->execute();

        // Insert into Vaccination table
        $sql_vaccination = "INSERT INTO Vaccination (type_vaccin, num_lot, date_peremption, lieu_vaccination, date_vaccination, heure_vaccination, site, dose, season) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_vaccination = $conn->prepare($sql_vaccination);
        $stmt_vaccination->bind_param("sssssssss", $vaccin, $lot, $peremption, $lieuvac, $datevac, $heurevac, $site, $dose, $season);
        $stmt_vaccination->execute();

        // Get the inserted vaccination's ID
        $vaccination_id = $conn->insert_id;

        // Insert into Patient_Vaccination association table
        $sql_patient_vaccination = "INSERT INTO Patient_Vaccination (id_patient, id_vaccination) VALUES (?, ?)";
        $stmt_patient_vaccination = $conn->prepare($sql_patient_vaccination);
        $stmt_patient_vaccination->bind_param("si", $idnum, $vaccination_id);
        $stmt_patient_vaccination->execute();

        // Insert selected antecedents into Patient_Maladie_Chronique association table
        foreach ($antecedents as $antecedent) {
            $sql_maladie = "SELECT id_maladie FROM Maladie_Chronique WHERE nom = ?";
            $stmt_maladie = $conn->prepare($sql_maladie);
            $stmt_maladie->bind_param("s", $antecedent);
            $stmt_maladie->execute();
            $result_maladie = $stmt_maladie->get_result();
            if ($result_maladie->num_rows > 0) {
                $row = $result_maladie->fetch_assoc();
                $maladie_id = $row['id_maladie'];

                $sql_patient_maladie = "INSERT INTO Patient_Maladie_Chronique (id_patient, id_maladie) VALUES (?, ?)";
                $stmt_patient_maladie = $conn->prepare($sql_patient_maladie);
                $stmt_patient_maladie->bind_param("is", $idnum, $maladie_id);
                $stmt_patient_maladie->execute();
            }
        }

        // Insert new antecedents into Maladie_Chronique table and store them in session
        $new_antecedents_ids = [];
        foreach ($new_antecedents as $new_antecedent) {
            if (!empty($new_antecedent)) {
                $sql_new_maladie = "INSERT INTO Maladie_Chronique (nom) VALUES (?)";
                $stmt_new_maladie = $conn->prepare($sql_new_maladie);
                $stmt_new_maladie->bind_param("s", $new_antecedent);
                $stmt_new_maladie->execute();

                $new_maladie_id = $conn->insert_id;
                $new_antecedents_ids[] = $new_maladie_id;

                // Insert into Patient_Maladie_Chronique association table
                $sql_patient_new_maladie = "INSERT INTO Patient_Maladie_Chronique (id_patient, id_maladie) VALUES (?, ?)";
                $stmt_patient_new_maladie = $conn->prepare($sql_patient_new_maladie);
                $stmt_patient_new_maladie->bind_param("is", $idnum, $new_maladie_id);
                $stmt_patient_new_maladie->execute();
            }
        }

        $conn->commit();

        $_SESSION['patient_info'] = [
            'idnum' => $idnum,
            'name' => $name,
            'gender' => $gender,
            'age' => $age,
            'adresse' => $adresse,
            'tel' => $tel,
            'vaccin' => $vaccin,
            'lot' => $lot,
            'peremption' => $peremption,
            'lieuvac' => $lieuvac,
            'datevac' => $datevac,
            'heurevac' => $heurevac,
            'site' => $site,
            'dose' => $dose,
            'antecedents' => array_merge($antecedents, $new_antecedents),
            'atc' => $antecedents_string,
            'season' => $season
        ];

        $_SESSION['success_message'] = "Patient information inserted successfully";
        header("Location: ./inserted_patient_info.php");
        exit();
    
    } catch (Exception $e) {
        $conn->rollback();
        echo '<script>alert("Insertion unsuccessful: ' . htmlspecialchars($exception->getMessage()) . '"); window.location="./add_patient.php";</script>';
        exit();
    } finally {
        $stmt_patient->close();
        $stmt_vaccination->close();
        $stmt_patient_vaccination->close();
        if (!empty($new_antecedents_ids)) {
            $stmt_new_maladie->close();
            $stmt_patient_new_maladie->close();
        }
        $conn->close();
    }
} else {
    header('Location: ./add_patient.php');
    exit();
}

function getSeason($date) {
    $month = date('n', strtotime($date));
    if ($month >= 3 && $month <= 5) {
        return 'Spring';
    } elseif ($month >= 6 && $month <= 8) {
        return 'Summer';
    } elseif ($month >= 9 && $month <= 11) {
        return 'Autumn';
    } else {
        return 'Winter';
    }
}
?>
