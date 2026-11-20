<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php';
$rows=$conn->query("SELECT p.id_patient,p.nom,p.sexe,p.age,p.createdate,v.type_vaccin,v.dose,v.date_vaccination,pv.date_apparition,pv.duree_manifestation,
 (SELECT GROUP_CONCAT(es.description SEPARATOR '|') FROM Vaccination_Effet_Secondaire ves JOIN Effet_Secondaire es ON es.id_effet=ves.id_effet WHERE ves.id_vaccination=v.id_vaccination) AS effects
 FROM Patient p LEFT JOIN Patient_Vaccination pv ON pv.id_patient=p.id_patient LEFT JOIN Vaccination v ON v.id_vaccination=pv.id_vaccination ORDER BY p.createdate DESC")->fetch_all(MYSQLI_ASSOC);
$top=$conn->query("SELECT es.description d,COUNT(*) c FROM Vaccination_Effet_Secondaire ves JOIN Effet_Secondaire es ON es.id_effet=ves.id_effet GROUP BY es.description ORDER BY c DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$vac=[];$rep=0;foreach($rows as $r){$vac[$r['type_vaccin']?:'Unknown']=($vac[$r['type_vaccin']?:'Unknown']??0)+1;if($r['effects'])$rep++;}
$total=count($rows);$max=max(array_column($top,'c')?:[1]);
open_page('Patients','app','list','Patients','<a class="btn sm ghost" href="export_csv.php">'.icon('down').'Export CSV</a> <a class="btn sm" href="add_patient.php">'.icon('plus').'New patient</a>'); ?>
<div class="kpis"><div class="card kpi"><b><?=$total?></b><span>Patients registered</span></div>
<div class="card kpi"><b><?=$rep?></b><span>With side effects reported</span></div>
<div class="card kpi"><b><?=$total?round($rep/$total*100):0?>%</b><span>Follow-up completed</span></div>
<div class="card kpi"><b><?=h($vac?array_search(max($vac),$vac):'–')?></b><span>Most used vaccine</span></div></div>
<div class="cols"><div class="card"><div class="tools"><input id="q" type="search" placeholder="Search by name, ID or side effect" aria-label="Search patients">
<select id="vf" aria-label="Filter by vaccine"><option value="">All vaccines</option><?php foreach(array_keys($vac) as $v) echo '<option>'.h($v).'</option>'; ?></select></div>
<div class="tw"><table id="pt"><thead><tr><th>Patient</th><th>Age</th><th>Vaccine</th><th>Side effects</th><th>Added</th></tr></thead><tbody>
<?php foreach($rows as $r): $fx=$r['effects']?explode('|',$r['effects']):[]; $vn=$r['type_vaccin']?:'Unknown'; ?>
<tr data-v="<?=h($vn)?>" data-s="<?=h(mb_strtolower($r['id_patient'].' '.$r['nom'].' '.implode(' ',$fx)))?>">
<td><a class="id" href="patient_details.php?id=<?=urlencode($r['id_patient'])?>"><?=h($r['nom'])?></a><div class="muted small"><?=h($r['id_patient'])?> · <?=h($r['sexe'])?></div></td><td><?=h($r['age'])?></td>
<td><?=h($vn)?><div class="muted small"><?=h($r['dose'])?> dose</div></td>
<td><?php if($fx) foreach($fx as $f) echo '<span class="tag coral">'.h($f).'</span>'; else echo '<span class="muted small">Not reported yet</span>'; ?></td><td class="muted small"><?=h(substr($r['createdate'],0,10))?></td></tr>
<?php endforeach; ?></tbody></table></div>
<div class="empty" id="none" <?=$total?'hidden':''?>>No patients found. <a href="add_patient.php">Add the first patient</a>.</div>
<p class="muted small" style="margin:12px 0 0"><span id="count"><?=$total?></span> shown</p></div>
<div class="card"><h3>Most reported side effects</h3><?php if($top): ?><ul class="bars"><?php foreach($top as $t): ?><li><span><?=h($t['d'])?></span><span class="t"><i style="width:<?=round($t['c']/$max*100)?>%"></i></span><b><?=$t['c']?></b></li><?php endforeach; ?></ul>
<?php else: ?><p class="muted small">Charts appear once side effects are reported.</p><?php endif; ?></div></div>
<script src="assets/js/app.js"></script>
<?php close_page('app'); ?>
