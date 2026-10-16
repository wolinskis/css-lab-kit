<?php
// Mini OAuth 2.0 authorization endpoint. VULN: redirect_uri is NOT validated against a
// registered value, so an attacker can make the victim's auth code redirect to any host.
$redirect = $_GET['redirect_uri'] ?? '';
$state    = $_GET['state'] ?? '';
if ($redirect === '') {
    header('Content-Type: text/plain');
    echo "VulnNexus OAuth — authorization endpoint\n";
    echo "Registered client 'nexus' redirect_uri: http://localhost:8001/oauth_callback.php\n\n";
    echo "Usage: oauth_authorize.php?redirect_uri=<url>&state=<s>\n";
    echo "The logged-in user is 'admin'. (Bug: redirect_uri is not checked.)\n";
    exit;
}
// Issue a one-time auth code bound to the signed-in victim ('admin'). No redirect_uri check.
$code  = bin2hex(random_bytes(8));
$store = sys_get_temp_dir().'/vulnnexus_oauth.json';
$db = is_file($store) ? (json_decode(file_get_contents($store), true) ?: []) : [];
$db[$code] = ['user' => 'admin', 'used' => false];
@file_put_contents($store, json_encode($db));
$sep = (strpos($redirect, '?') !== false) ? '&' : '?';
header('Location: '.$redirect.$sep.'code='.$code.'&state='.urlencode($state));
