<?php require __DIR__.'/includes/bootstrap.php'; open_page('Accueil'); ?>
<div class="wrap">
<section class="hero"><div>
<h1>Découvrez quels effets secondaires un vaccin risque de provoquer.</h1>
<p class="lead">Renseignez l’âge, le vaccin et les antécédents médicaux d’un patient. Trois modèles de clustering le placent parmi des cas similaires et classent les effets secondaires observés.</p>
<div class="row"><a class="btn" href="doctors_login.php"><?= icon('login') ?>Connexion médecin <?= icon('arrow') ?></a><a class="btn ghost" href="patient_form.php"><?= icon('clip') ?>Déclarer un effet secondaire</a></div></div>
<div class="viz"><canvas id="cluster" role="img" aria-label="Nuage de points animé : un nouveau patient se déplace entre trois groupes de cas passés"></canvas>
<div class="cap"><span><i style="background:#2563eb"></i>Groupe A</span><span><i style="background:#ff6a55"></i>Groupe B</span><span><i style="background:#12b886"></i>Groupe C</span><span>Nouveau patient</span></div></div>
</section></div>

<section class="sec"><div class="wrap"><h2><?= ti('layers') ?>De l’enregistrement à la prédiction en trois étapes</h2><div class="grid">
<div class="card"><div class="step-n">1</div><h3><?= ti('userplus') ?>Enregistrer le patient</h3><p>Identité, vaccin, dose et maladies chroniques, dans un court formulaire guidé.</p></div>
<div class="card"><div class="step-n">2</div><h3><?= ti('activity') ?>Obtenir une prédiction classée</h3><p>K-Means, mélange gaussien (GMM) et clustering agglomératif évaluent chacun les effets secondaires probables. Vous voyez la moyenne et le point de vue de chaque modèle.</p></div>
<div class="card"><div class="step-n">3</div><h3><?= ti('clip') ?>Consigner ce qui s’est passé</h3><p>Saisissez les effets secondaires réels, leur date d’apparition et leur durée pour garder des dossiers complets et exportables.</p></div>
</div></div></section>

<section class="sec" id="coronavirus"><div class="wrap"><h2><?= ti('virus') ?>Qu’est-ce que la COVID-19 ?</h2>
<p class="muted">La COVID-19 est une maladie respiratoire causée par le virus SARS-CoV-2. Devenue une pandémie mondiale en 2020, elle a entraîné des crises sanitaires et économiques dans le monde entier. La vaccination et les mesures de santé publique sont les principaux outils pour l’endiguer.</p></div></section>

<section class="sec" id="prevention"><div class="wrap"><h2><?= ti('shield') ?>Comment se protéger</h2><div class="split">
<div class="card"><h3><?= ti('check') ?>À faire</h3><ul class="dl do"><li><?= icon('check') ?>Se faire vacciner contre la COVID-19</li><li><?= icon('check') ?>Porter un masque dans les lieux fréquentés ou fermés</li><li><?= icon('check') ?>Se laver les mains ou utiliser une solution hydroalcoolique</li><li><?= icon('check') ?>Garder ses distances avec les autres</li><li><?= icon('check') ?>Désinfecter les surfaces à la maison</li><li><?= icon('check') ?>Rester chez soi en cas de maladie ou d’exposition</li></ul></div>
<div class="card"><h3><?= ti('x') ?>À éviter</h3><ul class="dl dont"><li><?= icon('x') ?>Les contacts étroits avec des personnes infectées</li><li><?= icon('x') ?>Les poignées de main</li><li><?= icon('x') ?>Se toucher le visage</li><li><?= icon('x') ?>Les surfaces contaminées</li><li><?= icon('x') ?>L’exposition aux gouttelettes</li></ul></div></div></div></section>

<section class="sec" id="symptoms"><div class="wrap"><h2><?= ti('thermo') ?>Symptômes à surveiller</h2>
<p class="muted">Fièvre, toux, fatigue, essoufflement, perte du goût ou de l’odorat, douleurs musculaires, mal de gorge, maux de tête, congestion, nausées ou diarrhée. Faites-vous tester et isolez-vous rapidement si vous en présentez.</p>
<div class="grid">
<div class="card sym"><div class="sym-ic"><svg viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" aria-hidden="true"><path d="M20 28.8V10a4 4 0 0 1 8 0v18.8a8 8 0 1 1-8 0Z" stroke="currentColor"/><path d="M24 18v14" stroke="var(--coral)"/><circle cx="24" cy="36" r="3.6" fill="var(--coral)" stroke="none"/><path d="M33 11h5M33 17h8M33 23h5" stroke="currentColor" opacity=".5"/></svg></div><div><h3>Forte fièvre</h3><p class="muted small">Généralement supérieure à 38 °C, souvent accompagnée de frissons et de courbatures.</p></div></div>
<div class="card sym"><div class="sym-ic"><svg viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" aria-hidden="true"><path d="M24 6v17" stroke="currentColor"/><path d="M22 19c-5-1-10 5-11 13-.5 5 1 7 5 6 4-1 6-4 6-9Z" stroke="currentColor"/><path d="M26 19c5-1 10 5 11 13 .5 5-1 7-5 6-4-1-6-4-6-9Z" stroke="currentColor"/><path d="m24 23-5 4M24 23l5 4" stroke="var(--coral)"/><circle cx="41" cy="9" r="1.9" fill="var(--coral)" stroke="none"/><circle cx="36" cy="5" r="1.4" fill="var(--coral)" stroke="none"/><circle cx="43" cy="15" r="1.4" fill="var(--coral)" stroke="none"/></svg></div><div><h3>Toux</h3><p class="muted small">Sèche ou grasse, elle peut s’aggraver avec le temps.</p></div></div>
<div class="card sym"><div class="sym-ic"><svg viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" aria-hidden="true"><circle cx="24" cy="14" r="7" stroke="currentColor"/><path d="M19 21v15M29 21v15" stroke="currentColor"/><path d="M8 43c0-5 6-8 16-8s16 3 16 8" stroke="currentColor"/><ellipse cx="24" cy="28" rx="3.4" ry="5.4" fill="var(--coral)" stroke="none"/><path d="M13 25l-4-2M13 30l-4 1M35 25l4-2M35 30l4 1" stroke="var(--coral)"/></svg></div><div><h3>Mal de gorge</h3><p class="muted small">Douleur ou irritation, parfois avec une difficulté à avaler.</p></div></div>
<div class="card sym"><div class="sym-ic"><svg viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" aria-hidden="true"><circle cx="24" cy="21" r="11" stroke="currentColor"/><path d="M8 44c0-6 6-9 16-9s16 3 16 9" stroke="currentColor"/><path d="M26.5 12.5 19 22h5.2l-2.2 8 8-10.5h-5.4Z" fill="var(--coral)" stroke="none"/><path d="M9 11 5.5 8M39 11l3.5-3M24 5V1.5M6 22H2.5M45.5 22H42" stroke="var(--coral)" opacity=".75"/></svg></div><div><h3>Maux de tête</h3><p class="muted small">Persistants ou intenses, souvent ressentis comme une pression ou une douleur sourde.</p></div></div>
</div></div></section>
<script src="assets/js/cluster.js"></script>
<?php close_page(); ?>
