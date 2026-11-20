<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php';
$id=$_GET['id']??''; if($id===''){header('Location: patients_list.php');exit;}
$st=$conn->prepare("SELECT Patient.*,GROUP_CONCAT(DISTINCT Maladie_Chronique.nom SEPARATOR '|') antecedents,GROUP_CONCAT(DISTINCT Effet_Secondaire.description SEPARATOR '|') side_effects,
 Vaccination.type_vaccin,Vaccination.date_vaccination,Vaccination.num_lot,Vaccination.date_peremption,Vaccination.lieu_vaccination,Vaccination.season,Vaccination.site,Vaccination.dose,Vaccination.heure_vaccination,Patient_Vaccination.date_apparition,Patient_Vaccination.duree_manifestation
 FROM Patient LEFT JOIN Patient_Maladie_Chronique ON Patient.id_patient=Patient_Maladie_Chronique.id_patient LEFT JOIN Maladie_Chronique ON Patient_Maladie_Chronique.id_maladie=Maladie_Chronique.id_maladie
 LEFT JOIN Patient_Vaccination ON Patient.id_patient=Patient_Vaccination.id_patient LEFT JOIN Vaccination ON Patient_Vaccination.id_vaccination=Vaccination.id_vaccination
 LEFT JOIN Vaccination_Effet_Secondaire ON Vaccination.id_vaccination=Vaccination_Effet_Secondaire.id_vaccination LEFT JOIN Effet_Secondaire ON Effet_Secondaire.id_effet=Vaccination_Effet_Secondaire.id_effet
 WHERE Patient.id_patient=? GROUP BY Patient.id_patient");
$st->bind_param('s',$id);$st->execute();$p=$st->get_result()->fetch_assoc();
if(!$p){open_page('Not found','app','list','Patient not found');echo '<div class="card empty">No patient has the ID “'.h($id).'”. <a href="patients_list.php">Back to patients</a></div>';close_page('app');exit;}
$sec=['Patient'=>['National ID'=>'id_patient','Gender'=>'sexe','Age'=>'age','Phone'=>'tel','Address'=>'adresse','Added'=>'createdate'],
 'Vaccination'=>['Vaccine'=>'type_vaccin','Dose'=>'dose','Batch number'=>'num_lot','Expiry date'=>'date_peremption','Date'=>'date_vaccination','Time'=>'heure_vaccination','Location'=>'lieu_vaccination','Injection site'=>'site','Season'=>'season']];
open_page($p['nom'],'app','list',$p['nom'],'<a class="btn sm ghost" href="patients_list.php">Back</a> <a class="btn sm" href="patient_form.php?id='.urlencode($p['id_patient']).'">'.icon('clip').'Report side effects</a>'); ?>
<div class="cols"><div>
<?php foreach($sec as $t=>$f): ?><div class="card" style="margin-bottom:18px"><h3><?=$t?></h3><dl class="dgrid"><?php foreach($f as $l=>$k): ?><div><dt><?=$l?></dt><dd><?=h($p[$k]?:'–')?></dd></div><?php endforeach; ?></dl></div><?php endforeach; ?>
<div class="card"><h3>Medical history</h3><?php foreach(explode('|',$p['antecedents']?:'No Background') as $a) echo '<span class="tag">'.h($a).'</span>'; ?></div></div>
<div class="card"><h3>Reported side effects</h3>
<?php if($p['side_effects']): foreach(explode('|',$p['side_effects']) as $e) echo '<span class="tag coral">'.h($e).'</span>'; ?>
<dl class="dgrid" style="margin-top:14px"><div><dt>Started</dt><dd><?=h($p['date_apparition']?:'–')?></dd></div><div><dt>Duration</dt><dd><?=h($p['duree_manifestation']?$p['duree_manifestation'].' days':'–')?></dd></div></dl>
<?php else: ?><p class="muted">Nothing reported yet.</p><a class="btn sm" href="patient_form.php?id=<?=urlencode($p['id_patient'])?>">Report side effects</a><?php endif; ?></div></div>
<?php close_page('app'); ?>
