<?php require __DIR__.'/includes/bootstrap.php'; if(!empty($_SESSION['username'])){header('Location: patients_list.php');exit;}
$err=flash('error_message'); open_page('Doctor login'); ?>
<div class="wrap" style="max-width:460px;padding-top:80px"><div class="card">
<h1 style="font-size:2rem">Doctor login</h1><p class="muted">Sign in to register patients and view predictions.</p>
<?php if($err): ?><div class="alert" role="alert"><?=h($err)?></div><?php endif; ?>
<form action="login_action.php" method="post">
<div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required autofocus></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
<button class="btn block" name="btn_login">Log in</button></form></div></div>
<?php close_page(); ?>
