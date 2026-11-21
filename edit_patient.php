<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php'; require __DIR__.'/includes/patient_data.php';
$d=load_patient($conn,$_GET['id']??''); if(!$d){header('Location: patients_list.php');exit;} [$p,$have]=$d;
$err=msg(flash('error_message')); $all=$conn->query("SELECT nom FROM Maladie_Chronique ORDER BY nom");
$locOk=in_array($p['lieu_vaccination'],vaccination_sites(),true); $v=fn($k)=>h($p[$k]??''); $sel=fn($a,$b)=>$a===$b?'selected':'';
open_page('Modifier le patient','app','list','Modifier · '.$p['nom'],'','edit'); ?>
<div class="card panel">
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<form id="f" action="edit_patient_action.php" method="post"><?=csrf_field()?><input type="hidden" name="idnum" value="<?=h($p['id_patient'])?>">
<h3><?=ti('user')?>Patient</h3>
<div class="field"><label for="idro"><?=icon('file')?>Numéro d’identité nationale</label><input id="idro" value="<?=h($p['id_patient'])?>" readonly><p class="muted small" style="margin:6px 0 0">Le numéro d’identité ne peut pas être modifié.</p></div>
<div class="field"><label for="name"><?=icon('user')?>Nom complet</label><input id="name" name="name" value="<?=$v('nom')?>" required></div>
<div class="two"><div class="field"><label for="gender"><?=icon('users')?>Sexe</label><select id="gender" name="gender" required><option value="">Choisir</option><option value="Male" <?=$sel($p['sexe'],'Male')?>>Homme</option><option value="Female" <?=$sel($p['sexe'],'Female')?>>Femme</option></select></div>
<div class="field"><label for="age"><?=icon('calendar')?>Âge</label><input id="age" name="age" type="number" min="0" max="120" value="<?=$v('age')?>" required></div></div>
<div class="field"><label for="adresse"><?=icon('pin')?>Adresse</label><input id="adresse" name="adresse" value="<?=$v('adresse')?>" required></div>
<div class="field"><label for="tel"><?=icon('phone')?>Téléphone</label><input id="tel" name="tel" type="tel" value="<?=$v('tel')?>" required></div>

<h3 style="margin-top:28px"><?=ti('syringe')?>Vaccination</h3>
<div class="two"><div class="field"><label for="vaccin"><?=icon('syringe')?>Vaccin</label><select id="vaccin" name="vaccin" required><option value="">Choisir</option><?php foreach(['Astra Zeneca','Sinopharm','Sputnik-v-','Sinovac'] as $x) echo '<option value="'.h($x).'" '.$sel($p['type_vaccin'],$x).'>'.h(td($x)).'</option>'; ?></select></div>
<div class="field"><label for="dose"><?=icon('layers')?>Dose</label><select id="dose" name="dose" required><option value="">Choisir</option><option value="1st" <?=$sel($p['dose'],'1st')?>>1re injection</option><option value="2nd" <?=$sel($p['dose'],'2nd')?>>2e injection</option></select></div></div>
<div class="two"><div class="field"><label for="numlot"><?=icon('file')?>Numéro de lot</label><input id="numlot" name="numlot" value="<?=$v('num_lot')?>" required></div>
<div class="field"><label for="dateperemp"><?=icon('calendar')?>Date de péremption</label><input id="dateperemp" name="dateperemp" type="date" value="<?=$v('date_peremption')?>" required></div></div>
<div class="two"><div class="field"><label for="datevac"><?=icon('calendar')?>Date de vaccination</label><input id="datevac" name="datevac" type="date" value="<?=$v('date_vaccination')?>" required></div>
<div class="field"><label for="timevac"><?=icon('history')?>Heure</label><input id="timevac" name="timevac" type="time" value="<?=h(substr((string)$p['heure_vaccination'],0,5))?>" required></div></div>
<div class="two"><div class="field"><label for="lieuvac"><?=icon('pin')?>Lieu de vaccination</label><select id="lieuvac" name="lieuvac" required><option value="">Choisir</option><?php foreach(vaccination_sites() as $x) echo '<option '.$sel($p['lieu_vaccination'],$x).'>'.h($x).'</option>'; ?></select>
<?php if(!$locOk): ?><p class="small" style="margin:6px 0 0;color:var(--bad)"><?=h(msg('err_loc_edit'))?></p><?php endif; ?></div>
<div class="field"><label for="Site"><?=icon('hand')?>Site d’injection</label><select id="Site" name="Site" required><option value="">Choisir</option><option value="right" <?=$sel($p['site'],'right')?>>Bras droit</option><option value="left" <?=$sel($p['site'],'left')?>>Bras gauche</option></select></div></div>

<h3 style="margin-top:28px"><?=ti('clip')?>Antécédents médicaux</h3><p class="muted small">Sélectionnez toutes les pathologies concernées. « Aucun antécédent » se décoche dès que vous en choisissez une autre.</p>
<div class="chips" id="hist"><?php while($r=$all->fetch_assoc()): $n=$r['nom']; $on=in_array($n,$have,true)||(!$have&&$n==='No Background'); ?><label><input type="checkbox" name="antecedents[]" value="<?=h($n)?>" <?=$on?'checked':''?>><span><?=h(td($n))?></span></label><?php endwhile; ?></div>
<div class="field" style="margin-top:22px"><label for="na">Autre pathologie (facultatif)</label><input id="na" name="new_antecedents[]"></div>
<div class="actions" style="justify-content:flex-start;flex-wrap:wrap"><button class="btn"><?=icon('check')?>Enregistrer les modifications</button><a class="btn ghost" href="patient_details.php?id=<?=urlencode($p['id_patient'])?>"><?=icon('x')?>Annuler</a></div>
</form></div>
<script nonce="<?= nonce() ?>">
const bx=[...document.querySelectorAll('#hist input')],no=bx.find(b=>b.value==='No Background');
bx.forEach(b=>b.onchange=()=>{if(b!==no&&b.checked)no.checked=false;if(!bx.some(x=>x!==no&&x.checked))no.checked=true});
</script>
<?php close_page('app'); ?>
