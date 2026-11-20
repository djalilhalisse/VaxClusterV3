<?php require __DIR__.'/includes/bootstrap.php'; open_page('Vaccine side-effect prediction'); ?>
<div class="wrap">
<section class="hero"><div>
<h1>See which side effects a vaccine is likely to cause.</h1>
<p class="lead">Add a patient's age, vaccine and medical history. Three clustering models place them among similar past cases and rank the side effects that followed.</p>
<div class="row"><a class="btn" href="doctors_login.php">Doctor login <?= icon('arrow') ?></a><a class="btn ghost" href="patient_form.php">Report side effects</a></div></div>
<div class="viz"><canvas id="cluster" role="img" aria-label="Animated scatter plot: a new patient moves between three clusters of past cases"></canvas>
<div class="cap"><span><i style="background:#5646ff"></i>Cluster A</span><span><i style="background:#ff6a55"></i>Cluster B</span><span><i style="background:#12b886"></i>Cluster C</span><span>New patient</span></div></div>
</section></div>

<section class="sec"><div class="wrap"><h2>From registration to prediction in three steps</h2><div class="grid">
<div class="card"><div class="step-n">1</div><h3>Register the patient</h3><p>Identity, vaccine, dose and chronic conditions, in a short guided form.</p></div>
<div class="card"><div class="step-n">2</div><h3>Get a ranked prediction</h3><p>K-Means, Gaussian Mixture and Agglomerative clustering each score the likely side effects. You see the average and each model's view.</p></div>
<div class="card"><div class="step-n">3</div><h3>Record what happened</h3><p>Log the real side effects, their start date and duration so every record stays complete and exportable.</p></div>
</div></div></section>

<section class="sec" id="coronavirus"><div class="wrap"><h2>What is COVID-19?</h2>
<p class="muted">COVID-19 is a respiratory illness caused by the SARS-CoV-2 virus. It became a global pandemic in 2020 and led to worldwide health and economic crises. Vaccination and public health measures are the main tools used to contain it.</p></div></section>

<section class="sec" id="prevention"><div class="wrap"><h2>How to protect yourself</h2><div class="split">
<div class="card"><h3>Do</h3><ul class="dl do"><li>Get vaccinated against COVID-19</li><li>Wear a mask in crowded or indoor places</li><li>Wash your hands or use sanitizer</li><li>Keep your distance from others</li><li>Disinfect surfaces at home</li><li>Stay home when sick or exposed</li></ul></div>
<div class="card"><h3>Avoid</h3><ul class="dl dont"><li>Close contact with infected people</li><li>Handshakes</li><li>Touching your face</li><li>Contaminated surfaces</li><li>Exposure to droplets</li></ul></div></div></div></section>

<section class="sec" id="symptoms"><div class="wrap"><h2>Symptoms to watch for</h2>
<p class="muted">Fever, cough, fatigue, shortness of breath, loss of taste or smell, muscle aches, sore throat, headache, congestion, nausea or diarrhea. Test and isolate promptly if you have any of them.</p>
<div class="grid">
<div class="card sym"><img src="images/symptom_high-fever.png" alt=""><div><h3>High fever</h3><p class="muted small">Usually above 38°C (100.4°F), often with chills and body aches.</p></div></div>
<div class="card sym"><img src="images/symptom_cough.png" alt=""><div><h3>Cough</h3><p class="muted small">Dry or productive, and it can worsen over time.</p></div></div>
<div class="card sym"><img src="images/symptom_sore-troath.png" alt=""><div><h3>Sore throat</h3><p class="muted small">Pain or scratchiness, sometimes with difficulty swallowing.</p></div></div>
<div class="card sym"><img src="images/symptom_headache.png" alt=""><div><h3>Headache</h3><p class="muted small">Persistent or severe, often felt as pressure or aching.</p></div></div>
</div></div></section>
<script src="assets/js/cluster.js"></script>
<?php close_page(); ?>
