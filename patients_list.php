<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php';
$rows=$conn->query("SELECT p.id_patient,p.nom,p.sexe,p.age,p.createdate,v.type_vaccin,v.dose,v.date_vaccination,pv.date_apparition,pv.duree_manifestation,
 (SELECT GROUP_CONCAT(es.description SEPARATOR '|') FROM Vaccination_Effet_Secondaire ves JOIN Effet_Secondaire es ON es.id_effet=ves.id_effet WHERE ves.id_vaccination=v.id_vaccination) AS effects
 FROM Patient p LEFT JOIN Patient_Vaccination pv ON pv.id_patient=p.id_patient LEFT JOIN Vaccination v ON v.id_vaccination=pv.id_vaccination ORDER BY p.createdate DESC")->fetch_all(MYSQLI_ASSOC);
$top=$conn->query("SELECT es.description d,COUNT(*) c FROM Vaccination_Effet_Secondaire ves JOIN Effet_Secondaire es ON es.id_effet=ves.id_effet GROUP BY es.description ORDER BY c DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$vac=[];$rep=0;foreach($rows as $r){$k=$r['type_vaccin']?:'Inconnu';$vac[$k]=($vac[$k]??0)+1;if($r['effects'])$rep++;}
$total=count($rows);$vtop=$vac?td(array_search(max($vac),$vac)):'–';$max=max(array_column($top,'c')?:[1]);
open_page('Patients','app','list','Patients','<a class="btn sm ghost" href="export_csv.php">'.icon('down').'Exporter en CSV</a> <a class="btn sm ghost" href="export_xlsx.php">'.icon('table').'Exporter en Excel</a> <a class="btn sm" href="add_patient.php">'.icon('userplus').'Nouveau patient</a>'); ?>
<div class="kpis"><div class="card kpi"><span class="ki"><?=icon('users')?></span><b><?=$total?></b><span>Patients enregistrés</span></div>
<div class="card kpi"><span class="ki"><?=icon('activity')?></span><b><?=$rep?></b><span>Avec effets secondaires déclarés</span></div>
<div class="card kpi"><span class="ki"><?=icon('check')?></span><b><?=$total?round($rep/$total*100):0?>%</b><span>Suivi complété</span></div>
<div class="card kpi"><span class="ki"><?=icon('syringe')?></span><b class="<?=mb_strlen($vtop)>8?'sm':''?>"><?=h($vtop)?></b><span>Vaccin le plus utilisé</span></div></div>
<div class="cols"><div class="card"><div class="tools"><input id="q" type="search" placeholder="Rechercher un patient…" aria-label="Rechercher par nom, identifiant ou effet secondaire">
<select id="vf" aria-label="Filtrer par vaccin"><option value="">Tous les vaccins</option><?php foreach(array_keys($vac) as $v) echo '<option value="'.h($v).'">'.h(td($v)).'</option>'; ?></select></div>
<div class="tw"><table id="pt"><thead><tr><th>Patient</th><th>Âge</th><th>Vaccin</th><th>Effets secondaires</th><th>Ajouté le</th></tr></thead><tbody>
<?php foreach($rows as $r): $fx=$r['effects']?explode('|',$r['effects']):[]; $vn=$r['type_vaccin']?:'Inconnu'; ?>
<tr data-v="<?=h($vn)?>" data-s="<?=h(mb_strtolower($r['id_patient'].' '.$r['nom'].' '.implode(' ',$fx).' '.implode(' ',array_map('td',$fx))))?>">
<td><a class="id" href="patient_details.php?id=<?=urlencode($r['id_patient'])?>"><?=h($r['nom'])?></a><div class="muted small"><?=h($r['id_patient'])?></div></td><td><?=h($r['age'])?> ans<div class="muted small"><?=h(td($r['sexe']))?></div></td>
<td><?=h(td($vn))?><div class="muted small"><?=h($r['dose']?td($r['dose']):'')?></div></td>
<td><?php if($fx) foreach($fx as $f) echo '<span class="tag coral">'.h(td($f)).'</span>'; else echo '<span class="muted small">Pas encore déclaré</span>'; ?></td><td class="muted small"><?=h(fdate(substr($r['createdate'],0,10)))?></td></tr>
<?php endforeach; ?></tbody></table></div>
<div class="empty" id="none" <?=$total?'hidden':''?>>Aucun patient trouvé. <a href="add_patient.php">Ajouter le premier patient</a>.</div>
<p class="muted small" style="margin:12px 0 0"><span id="count"><?=$total?></span> patient(s) affiché(s)</p></div>
<div class="card"><h3><?=ti('bar')?>Effets secondaires les plus déclarés</h3><?php if($top): ?><ul class="bars"><?php foreach($top as $t): ?><li><span><?=h(td($t['d']))?></span><span class="t"><i style="width:<?=round($t['c']/$max*100)?>%"></i></span><b><?=$t['c']?></b></li><?php endforeach; ?></ul>
<?php else: ?><p class="muted small">Les graphiques apparaissent dès que des effets secondaires sont déclarés.</p><?php endif; ?></div></div>
<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php close_page('app'); ?>
