<?php
// Mini OAuth 2.0 authorization endpoint. VULN: redirect_uri validation is weak/absent, so an
// attacker can make the victim's auth code redirect to a host they control.
require __DIR__.'/levels.php';
$L = lvl();
$redirect = $_GET['redirect_uri'] ?? '';
$state    = $_GET['state'] ?? '';
// Escalating (and still-bypassable) redirect_uri validation:
//  easy   : none.
//  medium : must CONTAIN 'localhost'          -> bypass: http://attacker.test/?x=localhost
//  hard   : must START WITH http://localhost  -> bypass with a subdomain: http://localhost.attacker.test/
//  expert : start-with + NO dots in host      -> bypass: http://localhost@attacker/  (@ userinfo, dotless host)
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
$location = $redirect.$sep.'code='.$code.($state!==''?'&state='.urlencode($state):'');

// Legit flow (redirect_uri IS the registered callback): do the real 302 so the normal login works.
if (strpos($redirect, 'oauth_callback.php') !== false) { header('Location: '.$location); exit; }

// Attacker-controlled redirect_uri: in a real browser the victim's code would be 302-redirected to
// the attacker's host (a blank page here, since that host doesn't exist — which is why it looked
// "white"). Instead of a blind redirect, SHOW the interception so you can see the leak and replay it.
header('Content-Type: text/plain');
echo "The authorization server issued a one-time code for the signed-in victim ('admin')\n";
echo "and redirected it to the redirect_uri you supplied — WITHOUT checking it:\n\n";
echo "  302 Found\n  Location: $location\n\n";
echo "That redirect_uri is NOT the registered callback, so an attacker controlling that host now\n";
echo "has the victim's auth code. Replay it at the real callback to log in as the victim:\n\n";
echo "  oauth_callback.php?code=$code\n";
