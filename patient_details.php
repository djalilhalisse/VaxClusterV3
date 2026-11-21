<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php';
$id=$_GET['id']??''; if($id===''){header('Location: patients_list.php');exit;}
$st=$conn->prepare("SELECT Patient.*,GROUP_CONCAT(DISTINCT Maladie_Chronique.nom SEPARATOR '|') antecedents,GROUP_CONCAT(DISTINCT Effet_Secondaire.description SEPARATOR '|') side_effects,
 Vaccination.type_vaccin,Vaccination.date_vaccination,Vaccination.num_lot,Vaccination.date_peremption,Vaccination.lieu_vaccination,Vaccination.season,Vaccination.site,Vaccination.dose,Vaccination.heure_vaccination,Patient_Vaccination.date_apparition,Patient_Vaccination.duree_manifestation
 FROM Patient LEFT JOIN Patient_Maladie_Chronique ON Patient.id_patient=Patient_Maladie_Chronique.id_patient LEFT JOIN Maladie_Chronique ON Patient_Maladie_Chronique.id_maladie=Maladie_Chronique.id_maladie
 LEFT JOIN Patient_Vaccination ON Patient.id_patient=Patient_Vaccination.id_patient LEFT JOIN Vaccination ON Patient_Vaccination.id_vaccination=Vaccination.id_vaccination
 LEFT JOIN Vaccination_Effet_Secondaire ON Vaccination.id_vaccination=Vaccination_Effet_Secondaire.id_vaccination LEFT JOIN Effet_Secondaire ON Effet_Secondaire.id_effet=Vaccination_Effet_Secondaire.id_effet
 WHERE Patient.id_patient=? GROUP BY Patient.id_patient");
$st->bind_param('s',$id);$st->execute();$p=$st->get_result()->fetch_assoc();
if(!$p){open_page('Patient introuvable','app','list','Patient introuvable');echo '<div class="card empty">Aucun patient n’a l’identifiant « '.h($id).' ». <a href="patients_list.php">Retour aux patients</a></div>';close_page('app');exit;}
ensure_prediction_table($conn);
$pq=$conn->prepare("SELECT created_at, results FROM Prediction_Result WHERE id_patient=? ORDER BY id DESC LIMIT 10");$pq->bind_param('s',$id);$pq->execute();$preds=$pq->get_result()->fetch_all(MYSQLI_ASSOC);
$okmsg=msg(flash('success_message'));
$tr=['sexe','type_vaccin','dose','site','season']; $dt=['createdate','date_peremption','date_vaccination'];
$sec=[['Patient','user',['Numéro d’identité'=>'id_patient','Sexe'=>'sexe','Âge'=>'age','Téléphone'=>'tel','Adresse'=>'adresse','Ajouté le'=>'createdate']],
 ['Vaccination','syringe',['Vaccin'=>'type_vaccin','Dose'=>'dose','Numéro de lot'=>'num_lot','Date de péremption'=>'date_peremption','Date de vaccination'=>'date_vaccination','Heure'=>'heure_vaccination','Lieu'=>'lieu_vaccination','Site d’injection'=>'site','Saison'=>'season']]];
open_page($p['nom'],'app','list',$p['nom'],'<a class="btn sm ghost" href="patients_list.php">'.icon('back').'Retour</a> <a class="btn sm ghost" href="edit_patient.php?id='.urlencode($p['id_patient']).'">'.icon('edit').'Modifier</a> <a class="btn sm" href="patient_form.php?id='.urlencode($p['id_patient']).'">'.icon('clip').'Déclarer des effets secondaires</a>','user'); ?>
<?php if($okmsg): ?><div class="alert ok" role="status"><?=h($okmsg)?></div><?php endif; ?>
<div class="cols"><div>
<?php foreach($sec as [$t,$ic,$f]): ?><div class="card" style="margin-bottom:18px"><h3><?=ti($ic)?><?=$t?></h3><dl class="dgrid"><?php foreach($f as $l=>$k): $val=$p[$k]; if(in_array($k,$tr,true)&&$val)$val=td($val); elseif(in_array($k,$dt,true)&&$val)$val=fdate($val); ?><div><dt><?=$l?></dt><dd><?=h($val?:'–')?></dd></div><?php endforeach; ?></dl></div><?php endforeach; ?>
<div class="card"><h3><?=ti('clip')?>Antécédents médicaux</h3><?php foreach(explode('|',$p['antecedents']?:'No Background') as $a) echo '<span class="tag">'.h(td($a)).'</span>'; ?></div></div>
<div><div class="card" style="margin-bottom:18px"><h3><?=ti('thermo')?>Effets secondaires déclarés</h3>
<?php if($p['side_effects']): foreach(explode('|',$p['side_effects']) as $e) echo '<span class="tag coral">'.h(td($e)).'</span>'; ?>
<dl class="dgrid" style="margin-top:14px"><div><dt>Début</dt><dd><?=h($p['date_apparition']?fdate($p['date_apparition']):'–')?></dd></div><div><dt>Durée</dt><dd><?=h($p['duree_manifestation']?$p['duree_manifestation'].' jour(s)':'–')?></dd></div></dl>
<?php else: ?><p class="muted">Rien n’a encore été déclaré.</p><a class="btn sm" href="patient_form.php?id=<?=urlencode($p['id_patient'])?>"><?=icon('clip')?>Déclarer des effets secondaires</a><?php endif; ?></div>
<div class="card"><h3><?=ti('activity')?>Prédictions enregistrées</h3>
<?php if($p['type_vaccin']): ?><form method="post" action="predicts.php" style="margin:0 0 12px"><?=csrf_field()?><input type="hidden" name="id" value="<?=h($p['id_patient'])?>"><button class="btn sm<?=$preds?' ghost':''?>" data-busy="busy"><?=icon($preds?'refresh':'activity')?><?=$preds?'Prédire à nouveau':'Prédire les effets secondaires'?></button></form>
<?php else: ?><p class="muted small"><?=h(msg('no_vacc'))?></p><?php endif; ?>
<?php if(!$preds): ?><p class="muted small">Aucune prédiction n’a encore été enregistrée pour ce patient.</p><?php endif; ?>
<?php foreach($preds as $pr): [$avg,]=avg_predictions(json_decode($pr['results'],true)?:[]); $top=$avg?array_key_first($avg):null; ?>
<details class="pred"><summary><?=icon('history')?><b><?=h(fdate(substr($pr['created_at'],0,16)))?></b><?php if($top!==null): ?> <span class="tag"><?=h(td($top))?> <?=$avg[$top]?>%</span><?php endif; ?></summary>
<ul class="bars"><?php foreach(array_slice($avg,0,8,true) as $e=>$v): ?><li><span><?=h(td($e))?></span><span class="t"><i style="width:<?=min(100,$v*2)?>%"></i></span><b><?=$v?></b></li><?php endforeach; ?></ul></details>
<?php endforeach; ?></div></div></div>
<div class="busy" id="busy" role="status" aria-live="polite"><div><div class="spin"></div><p>Exécution des trois modèles… cela peut prendre un moment.</p></div></div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php close_page('app'); ?>
