<?php require __DIR__.'/includes/bootstrap.php'; if(!empty($_SESSION['username'])){header('Location: patients_list.php');exit;}
$err=msg(flash('error_message')); open_page('Connexion médecin'); ?>
<div class="wrap" style="max-width:460px;padding-top:80px"><div class="card login-card">
<h1 style="font-size:2rem"><?=ti('lock')?>Connexion médecin</h1><p class="muted">Connectez-vous pour enregistrer des patients et consulter les prédictions.</p>
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<form action="login_action.php" method="post"><?=csrf_field()?>
<div class="field"><label for="username"><?=icon('user')?>Nom d’utilisateur</label><input id="username" name="username" autocomplete="username" required autofocus></div>
<div class="field"><label for="password"><?=icon('lock')?>Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
<button class="btn block" name="btn_login"><?=icon('login')?>Se connecter</button></form></div></div>
<?php close_page(); ?>
