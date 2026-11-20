<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/configuration/app.php';
chdir(__DIR__); // the Python scripts open './predictions/Models/...' relative to the project root
require_login();
require_once __DIR__ . '/includes/patient_data.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {          // predict (or re-predict) a patient already saved in the database
    csrf_check();
    require_once __DIR__ . '/configuration/db_connection.php';
    $loaded = load_patient($conn, trim((string)($_POST['id'] ?? '')));
    $fail = !$loaded ? 'err_id_missing' : (!$loaded[0]['id_vaccination'] ? 'no_vacc' : (!in_array($loaded[0]['lieu_vaccination'], vaccination_sites(), true) ? 'err_loc_edit' : null));
    if ($fail) { $_SESSION['patient_info'] = $loaded ? prediction_info($loaded[0], $loaded[1]) : ($_SESSION['patient_info'] ?? null); $_SESSION['error'] = $fail; header('Location: ./prediction_results.php'); exit(); }
    $_SESSION['patient_info'] = prediction_info($loaded[0], $loaded[1]);
}
if (!isset($_SESSION['patient_info'])) {
    header('Location: ./add_patient.php');
    exit();
}

// Retrieve patient information from session variables
$patient_info = $_SESSION['patient_info'];

// Prepare data as a JSON object
$data = [
    'age' => $patient_info['age'],
    'sexe' => $patient_info['gender'], // Male binary column
    //'female' => $patient_info['female'], // Female binary column
    'nom_vaccin' => $patient_info['vaccin'],
    'lieu_vaccination' => $patient_info['lieuvac'],
    'saison_vaccination' => $patient_info['season'],
    'medical_history' => $patient_info['atc'],
    'side_effects' => array_fill(0, 20, 0) // Set side effects to 0
];

$json_data = json_encode($data);

// Define paths and algorithms
$algorithms = [
    'KMEANS' => './predictions/Predict_KMeans.py', // Adjust this path
    'Agglomerative' => './predictions/Predict_Agg.py', // Adjust this path
    'GMM' => './predictions/GMM_Predict.py', // Adjust this path
];

// Check if Python interpreter and scripts exist
$python_path = PYTHON_PATH; // set in configuration/app.php
if (strpbrk($python_path, '/\\') !== false && !file_exists($python_path)) {
    error_log("Python not found at $python_path. Set PYTHON_PATH."); $_SESSION['error'] = 'err_predict'; header('Location: ./prediction_results.php'); exit();
}

foreach ($algorithms as $algorithm => $script_path) {
    if (!file_exists($script_path)) {
        error_log("Script not found for $algorithm at $script_path"); $_SESSION['error'] = 'err_predict'; header('Location: ./prediction_results.php'); exit();
    }
}

// Same patient profile => same prediction, so results are cached (7 days) to skip the slow Python start-up.
$predicted_side_effects = [];
$cache_file = sys_get_temp_dir() . '/vaxcluster_' . md5($json_data) . '.json';
$cached = (is_file($cache_file) && filemtime($cache_file) > time() - 7 * 86400) ? json_decode(file_get_contents($cache_file), true) : null;

if ($cached) {
    $predicted_side_effects = $cached;
} else {
    $run = function ($algorithm, $script) use ($python_path, $json_data) {
        $proc = proc_open(escapeshellcmd("$python_path $script"), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) return ['algo' => $algorithm, 'proc' => null];
        fwrite($pipes[0], $json_data); fclose($pipes[0]);
        return ['algo' => $algorithm, 'proc' => $proc, 'pipes' => $pipes];
    };
    $collect = function ($job) use (&$predicted_side_effects) {
        if (!$job['proc']) { error_log("Failed to open the Python process for {$job['algo']}."); $_SESSION['error'] = 'err_predict'; return; }
        $out = stream_get_contents($job['pipes'][1]); fclose($job['pipes'][1]);
        $err = stream_get_contents($job['pipes'][2]); fclose($job['pipes'][2]);
        if (proc_close($job['proc']) != 0) { error_log("Prediction script failed ({$job['algo']}): $err"); $_SESSION['error'] = 'err_predict'; return; }
        $predicted_side_effects[$job['algo']] = json_decode($out, true);
    };
    if (PREDICT_PARALLEL) {                       // start all three models at once (fast on a normal PC)
        $jobs = [];
        foreach ($algorithms as $algorithm => $script_path) $jobs[] = $run($algorithm, $script_path);
        foreach ($jobs as $job) $collect($job);
    } else {                                      // one at a time (low-memory hosting)
        foreach ($algorithms as $algorithm => $script_path) $collect($run($algorithm, $script_path));
    }
    if (count($predicted_side_effects) === count($algorithms)) @file_put_contents($cache_file, json_encode($predicted_side_effects));
}

// Save the prediction on the patient's record (skipped if identical to the last saved one)
if (count($predicted_side_effects) === count($algorithms)) {
    require_once __DIR__ . '/configuration/db_connection.php';
    ensure_prediction_table($conn);
    $js = json_encode($predicted_side_effects, JSON_UNESCAPED_UNICODE); $pid = (string)$patient_info['idnum'];
    $q = $conn->prepare("SELECT results FROM Prediction_Result WHERE id_patient = ? ORDER BY id DESC LIMIT 1");
    $q->bind_param('s', $pid); $q->execute(); $last = $q->get_result()->fetch_assoc();
    if (!$last || $last['results'] !== $js) { $ins = $conn->prepare("INSERT INTO Prediction_Result (id_patient, results) VALUES (?, ?)"); $ins->bind_param('ss', $pid, $js); $ins->execute(); }
    $_SESSION['prediction_saved'] = true;
}

// Store the predicted side effects in the session variable
$_SESSION['predicted_side_effects'] = $predicted_side_effects;

// Redirect to the display prediction page
header('Location: ./prediction_results.php');
exit();
?>
