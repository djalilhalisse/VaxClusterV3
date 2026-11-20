<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php'; require __DIR__.'/includes/patient_data.php';
$d=load_patient($conn,$_GET['id']??''); if(!$d){header('Location: patients_list.php');exit;} [$p,$have]=$d;
$err=msg(flash('error_message')); $all=$conn->query("SELECT nom FROM Maladie_Chronique ORDER BY nom");
$locOk=in_array($p['lieu_vaccination'],vaccination_sites(),true); $v=fn($k)=>h($p[$k]??''); $sel=fn($a,$b)=>$a===$b?'selected':'';
open_page('Edit patient','app','list','Edit patient · '.$p['nom']); ?>
<div class="card panel">
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<form id="f" action="edit_patient_action.php" method="post"><?=csrf_field()?><input type="hidden" name="idnum" value="<?=h($p['id_patient'])?>">
<h3>Patient</h3>
<div class="field"><label for="idro">National ID</label><input id="idro" value="<?=h($p['id_patient'])?>" readonly><p class="muted small" style="margin:6px 0 0">The national ID cannot be changed.</p></div>
<div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?=$v('nom')?>" required></div>
<div class="two"><div class="field"><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Choose</option><option <?=$sel($p['sexe'],'Male')?>>Male</option><option <?=$sel($p['sexe'],'Female')?>>Female</option></select></div>
<div class="field"><label for="age">Age</label><input id="age" name="age" type="number" min="0" max="120" value="<?=$v('age')?>" required></div></div>
<div class="field"><label for="adresse">Address</label><input id="adresse" name="adresse" value="<?=$v('adresse')?>" required></div>
<div class="field"><label for="tel">Phone</label><input id="tel" name="tel" type="tel" value="<?=$v('tel')?>" required></div>

<h3 style="margin-top:28px">Vaccination</h3>
<div class="two"><div class="field"><label for="vaccin">Vaccine</label><select id="vaccin" name="vaccin" required><option value="">Choose</option><?php foreach(['Astra Zeneca','Sinopharm','Sputnik-v-','Sinovac'] as $x) echo '<option '.$sel($p['type_vaccin'],$x).'>'.h($x).'</option>'; ?></select></div>
<div class="field"><label for="dose">Dose</label><select id="dose" name="dose" required><option value="">Choose</option><option value="1st" <?=$sel($p['dose'],'1st')?>>1st injection</option><option value="2nd" <?=$sel($p['dose'],'2nd')?>>2nd injection</option></select></div></div>
<div class="two"><div class="field"><label for="numlot">Batch number</label><input id="numlot" name="numlot" value="<?=$v('num_lot')?>" required></div>
<div class="field"><label for="dateperemp">Expiry date</label><input id="dateperemp" name="dateperemp" type="date" value="<?=$v('date_peremption')?>" required></div></div>
<div class="two"><div class="field"><label for="datevac">Vaccination date</label><input id="datevac" name="datevac" type="date" value="<?=$v('date_vaccination')?>" required></div>
<div class="field"><label for="timevac">Time</label><input id="timevac" name="timevac" type="time" value="<?=h(substr((string)$p['heure_vaccination'],0,5))?>" required></div></div>
<div class="two"><div class="field"><label for="lieuvac">Location</label><select id="lieuvac" name="lieuvac" required><option value="">Choose</option><?php foreach(vaccination_sites() as $x) echo '<option '.$sel($p['lieu_vaccination'],$x).'>'.h($x).'</option>'; ?></select>
<?php if(!$locOk): ?><p class="small" style="margin:6px 0 0;color:var(--bad)"><?=h(msg('err_loc_edit'))?></p><?php endif; ?></div>
<div class="field"><label for="Site">Injection site</label><select id="Site" name="Site" required><option value="">Choose</option><option value="right" <?=$sel($p['site'],'right')?>>Right arm</option><option value="left" <?=$sel($p['site'],'left')?>>Left arm</option></select></div></div>

<h3 style="margin-top:28px">Medical history</h3><p class="muted small">Select every condition that applies. “No Background” clears when you pick another.</p>
<div class="chips" id="hist"><?php while($r=$all->fetch_assoc()): $n=$r['nom']; $on=in_array($n,$have,true)||(!$have&&$n==='No Background'); ?><label><input type="checkbox" name="antecedents[]" value="<?=h($n)?>" <?=$on?'checked':''?>><span><?=h($n)?></span></label><?php endwhile; ?></div>
<div class="field" style="margin-top:22px"><label for="na">Other condition (optional)</label><input id="na" name="new_antecedents[]"></div>
<div class="actions" style="justify-content:flex-start;flex-wrap:wrap"><button class="btn"><?=icon('check')?>Save changes</button><a class="btn ghost" href="patient_details.php?id=<?=urlencode($p['id_patient'])?>">Cancel</a></div>
</form></div>
<script nonce="<?= nonce() ?>">
const bx=[...document.querySelectorAll('#hist input')],no=bx.find(b=>b.value==='No Background');
bx.forEach(b=>b.onchange=()=>{if(b!==no&&b.checked)no.checked=false;if(!bx.some(x=>x!==no&&x.checked))no.checked=true});
</script>
<?php close_page('app'); ?>
