<?php require __DIR__.'/includes/bootstrap.php'; require __DIR__.'/configuration/db_connection.php';
$mode=empty($_SESSION['username'])?'public':'app'; $ok=msg(flash('success_message')); $err=msg(flash('error_message'));
$fx=$conn->query("SELECT description FROM effet_secondaire ORDER BY description");
open_page('Déclarer des effets secondaires',$mode,'report'); if($mode==='public') echo '<div class="wrap" style="padding-top:48px"><h1 style="font-size:2.4rem">'.ti('clip').'Déclarer des effets secondaires</h1>'; else echo '<div>'; ?>
<div class="card panel">
<?php if($ok): ?><div class="alert ok" role="status"><?=h($ok)?></div><?php endif; ?><?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<form action="patient_form_action.php" method="post"><?=csrf_field()?>
<div class="field"><label for="idnum"><?=icon('file')?>Numéro d’identité nationale</label><input id="idnum" name="idnum" inputmode="numeric" maxlength="20" pattern="[0-9]+" value="<?=h($_GET['id']??'')?>" required></div>
<div class="field"><span class="lbl"><?=icon('thermo')?>Effets secondaires ressentis</span><div class="chips">
<?php while($r=$fx->fetch_assoc()): ?><label><input type="checkbox" name="side_effects[]" value="<?=h($r['description'])?>"><span><?=h(td($r['description']))?></span></label><?php endwhile; ?></div></div>
<div class="field"><label for="other">Autres effets secondaires (facultatif)</label><?=multi_input('side_effects[]','Saisissez un effet secondaire non listé ci-dessus','other')?></div>
<div class="two"><div class="field"><label for="dateappar"><?=icon('calendar')?>Date d’apparition</label><input class="ui" id="dateappar" name="dateappar" type="date" max="<?=date('Y-m-d')?>" required></div>
<div class="field"><label for="dureemanif"><?=icon('history')?>Durée (en jours)</label><input class="ui" id="dureemanif" name="dureemanif" type="number" min="1" required></div></div>
<button class="btn" name="btn_submit"><?=icon('check')?>Enregistrer la déclaration</button></form></div></div>
<?php close_page($mode); ?>
