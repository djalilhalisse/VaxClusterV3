<?php require __DIR__.'/includes/bootstrap.php'; require_login();
$p=$_SESSION['patient_info']??null; if(!$p){header('Location: add_patient.php');exit;} $ok=msg(flash('success_message'));
$rows=['Numéro d’identité'=>'idnum','Sexe'=>'gender','Âge'=>'age','Adresse'=>'adresse','Téléphone'=>'tel','Vaccin'=>'vaccin','Dose'=>'dose','Numéro de lot'=>'lot','Date de péremption'=>'peremption','Lieu'=>'lieuvac','Date de vaccination'=>'datevac','Heure'=>'heurevac','Site d’injection'=>'site','Saison'=>'season'];
$tr=['gender','vaccin','dose','site','season']; $dt=['peremption','datevac'];
open_page('Patient enregistré','app','add',$p['name'].' a été ajouté(e)','','check'); ?>
<?php if($ok): ?><div class="alert ok" role="status"><?=h($ok)?></div><?php endif; ?>
<div class="card panel"><dl class="dgrid"><?php foreach($rows as $l=>$k): $val=$p[$k]; if(in_array($k,$tr,true)&&$val)$val=td($val); elseif(in_array($k,$dt,true)&&$val)$val=fdate($val); ?><div><dt><?=$l?></dt><dd><?=h($val)?></dd></div><?php endforeach; ?></dl>
<p class="lbl" style="margin-top:22px"><?=icon('clip')?>Antécédents médicaux</p><?php foreach($p['antecedents'] as $a) echo '<span class="tag">'.h(td($a)).'</span>'; ?>
<div class="actions" style="justify-content:flex-start;flex-wrap:wrap"><a class="btn" href="predicts.php"><?=icon('activity')?>Prédire les effets secondaires <?=icon('arrow')?></a><a class="btn ghost" href="add_patient.php"><?=icon('userplus')?>Ajouter un autre patient</a></div></div>
<?php close_page('app'); ?>
