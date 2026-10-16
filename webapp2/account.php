<?php require __DIR__.'/jwt.php';
header('Content-Type: text/plain');
$tok = $_COOKIE['session'] ?? (preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION']??'', $m)? $m[1]:null);
$claims = jwt_verify($tok);
if (!$claims) { echo "no valid session — login first, or forge a token\n"; exit; }
echo "user: {$claims['user']}\nrole: {$claims['role']}\n";
if (($claims['role'] ?? '')==='admin') echo "ADMIN PANEL → FLAG{jwt_4lg_none_or_w3ak_s3cret_forg3d_admin}\n";
else echo "(you are not admin — forge role=admin via alg:none or by cracking the HS256 secret)\n";
