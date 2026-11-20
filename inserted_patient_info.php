<?php require __DIR__.'/includes/bootstrap.php'; require_login();
$p=$_SESSION['patient_info']??null; if(!$p){header('Location: add_patient.php');exit;} $ok=flash('success_message');
$rows=['National ID'=>'idnum','Gender'=>'gender','Age'=>'age','Address'=>'adresse','Phone'=>'tel','Vaccine'=>'vaccin','Dose'=>'dose','Batch number'=>'lot','Expiry date'=>'peremption','Location'=>'lieuvac','Date'=>'datevac','Time'=>'heurevac','Injection site'=>'site','Season'=>'season'];
open_page('Patient saved','app','add',$p['name'].' was added'); ?>
<?php if($ok): ?><div class="alert ok" role="status"><?=h($ok)?></div><?php endif; ?>
<div class="card panel"><dl class="dgrid"><?php foreach($rows as $l=>$k): ?><div><dt><?=$l?></dt><dd><?=h($p[$k])?></dd></div><?php endforeach; ?></dl>
<p class="lbl" style="margin-top:22px">Medical history</p><?php foreach($p['antecedents'] as $a) echo '<span class="tag">'.h($a).'</span>'; ?>
<div class="actions" style="justify-content:flex-start"><a class="btn" href="predicts.php">Predict side effects <?=icon('arrow')?></a><a class="btn ghost" href="add_patient.php">Add another patient</a></div></div>
<?php close_page('app'); ?>
