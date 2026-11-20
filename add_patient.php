<?php require __DIR__.'/includes/bootstrap.php'; require_login(); require __DIR__.'/configuration/db_connection.php';
$err=msg(flash('error_message')); $hist=$conn->query("SELECT nom FROM Maladie_Chronique ORDER BY nom");
open_page('New patient','app','add'); ?>
<div class="card panel">
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<ol class="stepper"><li class="on"><span>Patient</span></li><li><span>Vaccination</span></li><li><span>Medical history</span></li></ol>
<form id="f" action="add_patient_action.php" method="post"><?=csrf_field()?>
<div class="step on"><div class="field"><label for="idnum">National ID</label><input id="idnum" name="idnum" inputmode="numeric" maxlength="20" pattern="[0-9]+" required></div>
<div class="field"><label for="name">Full name</label><input id="name" name="name" required></div>
<div class="two"><div class="field"><label for="gender">Gender</label><select id="gender" name="gender" required><option value="">Choose</option><option>Male</option><option>Female</option></select></div>
<div class="field"><label for="age">Age</label><input id="age" name="age" type="number" min="0" max="120" required></div></div>
<div class="field"><label for="adresse">Address</label><input id="adresse" name="adresse" required></div>
<div class="field"><label for="tel">Phone</label><input id="tel" name="tel" type="tel" required></div></div>
<div class="step"><div class="two"><div class="field"><label for="vaccin">Vaccine</label><select id="vaccin" name="vaccin" required><option value="">Choose</option><option>Astra Zeneca</option><option>Sinopharm</option><option>Sputnik-v-</option><option>Sinovac</option></select></div>
<div class="field"><label for="dose">Dose</label><select id="dose" name="dose" required><option value="">Choose</option><option value="1st">1st injection</option><option value="2nd">2nd injection</option></select></div></div>
<div class="two"><div class="field"><label for="numlot">Batch number</label><input id="numlot" name="numlot" required></div>
<div class="field"><label for="dateperemp">Expiry date</label><input id="dateperemp" name="dateperemp" type="date" required></div></div>
<div class="two"><div class="field"><label for="datevac">Vaccination date</label><input id="datevac" name="datevac" type="date" value="<?=date('Y-m-d')?>" required></div>
<div class="field"><label for="timevac">Time</label><input id="timevac" name="timevac" type="time" required></div></div>
<div class="two"><div class="field"><label for="lieuvac">Location</label><select id="lieuvac" name="lieuvac" required><option value="">Choose</option><?php foreach(vaccination_sites() as $x) echo '<option>'.h($x).'</option>'; ?></select></div>
<div class="field"><label for="Site">Injection site</label><select id="Site" name="Site" required><option value="">Choose</option><option value="right">Right arm</option><option value="left">Left arm</option></select></div></div></div>
<div class="step"><p class="muted small">Select every condition that applies. “No Background” clears when you pick another.</p>
<div class="chips" id="hist"><?php while($r=$hist->fetch_assoc()): $n=$r['nom']; ?><label><input type="checkbox" name="antecedents[]" value="<?=h($n)?>" <?=$n==='No Background'?'checked':''?>><span><?=h($n)?></span></label><?php endwhile; ?></div>
<div class="field" style="margin-top:22px"><label for="na">Other condition (optional)</label><input id="na" name="new_antecedents[]"></div></div>
<div class="actions"><button type="button" class="btn ghost" id="back">Back</button><button type="button" class="btn" id="next">Continue</button></div>
</form></div>
<script nonce="<?= nonce() ?>">
const S=[...document.querySelectorAll('.step')],P=[...document.querySelectorAll('.stepper li')],f=document.getElementById('f'),B=document.getElementById('back'),N=document.getElementById('next');let i=0;
function show(){S.forEach((s,k)=>s.classList.toggle('on',k===i));P.forEach((p,k)=>{p.classList.toggle('on',k===i);p.classList.toggle('done',k<i)});B.style.visibility=i?'visible':'hidden';N.textContent=i===S.length-1?'Save and continue':'Continue'}
N.onclick=()=>{const bad=[...S[i].querySelectorAll('[required]')].find(e=>!e.checkValidity());if(bad){bad.reportValidity();return}i<S.length-1?(i++,show()):f.requestSubmit()};B.onclick=()=>{i--;show()};
const bx=[...document.querySelectorAll('#hist input')],no=bx.find(b=>b.value==='No Background');bx.forEach(b=>b.onchange=()=>{if(b!==no&&b.checked)no.checked=false;if(!bx.some(x=>x!==no&&x.checked))no.checked=true});show();
</script>
<?php close_page('app'); ?>
