<?php require __DIR__.'/includes/bootstrap.php'; require_login();
$err=msg(flash('error')); $saved=flash('prediction_saved'); $pred=$_SESSION['predicted_side_effects']??[]; unset($_SESSION['predicted_side_effects']); $p=$_SESSION['patient_info']??null;
$norm=[];$avg=[]; // chaque modèle est ramené à un % de son propre total, puis les modèles sont moyennés
foreach($pred as $a=>$fx){$sum=array_sum($fx)?:1;foreach($fx as $e=>$v)$norm[$e][$a]=round($v/$sum*100);}
foreach($norm as $e=>$v)$avg[$e]=round(array_sum($v)/count($pred)); arsort($avg); $avg=array_filter($avg,fn($v)=>$v>=1);
open_page('Résultats de la prédiction','app','add','Effets secondaires prédits','','activity'); ?>
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<?php if($saved && !$err): ?><div class="alert ok" role="status"><?=h(msg('pred_saved'))?></div><?php endif; ?>
<?php if($avg): $first=array_key_first($avg); ?>
<div class="card top1"><b><?=$avg[$first]?>%</b><div><p>Effet secondaire le plus probable<?= $p?' pour '.h($p['name']):'' ?></p><h2 style="margin:0;color:#fff"><?=h(td($first))?></h2></div></div>
<div class="card"><ul class="rank"><?php foreach($avg as $e=>$v): $c=$v>=25?'hi':($v>=10?'mid':'lo'); ?>
<li class="<?=$c?>"><div><strong><?=h(td($e))?></strong><div class="muted small"><?php foreach($norm[$e] as $a=>$x) echo '<span class="tag">'.h($a).' '.$x.'%</span>'; ?></div></div>
<div class="meter" role="img" aria-label="<?=$v?> pour cent"><i style="width:<?=min(100,$v*2)?>%"></i></div><div class="pct"><?=$v?>%</div></li><?php endforeach; ?></ul>
<p class="muted small" style="margin:14px 0 0">Part des cas déclarés dans le groupe du patient, moyennée sur <?=count($pred)?> modèles. Les barres sont tracées sur une échelle de 50 %.</p></div>
<?php else: ?><div class="card empty">Aucune prédiction à afficher. Enregistrez un patient, puis choisissez « Prédire les effets secondaires ».</div><?php endif; ?>
<div class="actions" style="justify-content:flex-start;flex-wrap:wrap"><?php if($p && !empty($p['idnum'])): ?><a class="btn" href="patient_details.php?id=<?=urlencode($p['idnum'])?>"><?=icon('user')?>Ouvrir le dossier du patient</a><?php endif; ?><a class="btn ghost" href="add_patient.php"><?=icon('userplus')?>Ajouter un autre patient</a><a class="btn ghost" href="patients_list.php"><?=icon('back')?>Retour aux patients</a></div>
<?php close_page('app'); ?>
