<?php
header('Content-Type: text/plain');
$code  = $_GET['code'] ?? '';
$store = sys_get_temp_dir().'/vulnnexus_oauth.json';
$db = is_file($store) ? (json_decode(file_get_contents($store), true) ?: []) : [];
if ($code === '' || !isset($db[$code]) || $db[$code]['used']) { echo "invalid or already-used code\n"; exit; }
$db[$code]['used'] = true; @file_put_contents($store, json_encode($db));
$user = $db[$code]['user'];
echo "OAuth login successful — authenticated as: $user\n";
if ($user === 'admin') echo "FLAG{oauth_redirect_uri_not_validated}\n";
