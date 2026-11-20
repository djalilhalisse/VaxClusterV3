<?php
// Run on your own computer (not on the website):  php tools/hash_password.php "NewStrongPassword" User1
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$pw = $argv[1] ?? null; $user = $argv[2] ?? 'User1';
if (!$pw) { fwrite(STDERR, "Usage: php tools/hash_password.php \"password\" [username]\n"); exit(1); }
echo "\nUPDATE users SET pass = '" . password_hash($pw, PASSWORD_DEFAULT) . "' WHERE username = '" . str_replace("'", "''", $user) . "';\n";
