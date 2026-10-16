<?php
// Mini OAuth 2.0 authorization endpoint. VULN: redirect_uri validation is weak/absent, so an
// attacker can make the victim's auth code redirect to a host they control.
require __DIR__.'/levels.php';
$L = lvl();
$redirect = $_GET['redirect_uri'] ?? '';
$state    = $_GET['state'] ?? '';
// Escalating (and still-bypassable) redirect_uri validation:
//  easy   : none.
//  medium : must start with 'http://localhost'  -> bypass with the @ trick: http://localhost@attacker.test/cb
//  hard   : + '@' blocked               -> bypass with a subdomain: http://localhost.attacker.test/cb
//  expert : + no extra '.' in the host  -> same subdomain idea needs a parser differential (advanced)
if ($redirect !== '') {
    $bad='';
    //  medium : must CONTAIN 'localhost'         -> http://attacker.test/?x=localhost
    //  hard   : must START WITH http://localhost -> http://localhost.attacker.test/  (subdomain)
    //  expert : start-with + NO dots             -> http://localhost@attacker/       (@ userinfo, dotless host)
    if ($L==='medium' && strpos($redirect,'localhost')===false) $bad="must reference localhost";
    if ($L==='hard'   && strpos($redirect,'http://localhost')!==0) $bad="must start with http://localhost";
    if ($L==='expert') {
        if (strpos($redirect,'http://localhost')!==0) $bad="must start with http://localhost";
        elseif (strpos($redirect,'.')!==false) $bad="no '.' allowed in the redirect_uri";
    }
    if ($bad) { header('Content-Type: text/plain'); echo "redirect_uri rejected (level $L): $bad\n"; exit; }
}
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
