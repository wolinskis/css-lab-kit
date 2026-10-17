<?php
// Minimal JWT (HS256) with difficulty levels.
require_once __DIR__.'/levels.php';
// Signing secret per level:
//  easy/medium : 'secret'   (alg:none also accepted at easy)
//  hard        : 'superman' (still in rockyou -> crackable, just slower)
//  expert      : a strong random secret -> NOT crackable, but it's LEAKED in the secrets table
//                (dump it via the SQLi, then sign your own admin token). SQLi -> JWT chain.
function jwt_secret($l){
  if($l==='hard')   return 'superman';
  if($l==='expert') return '$Z3pk9_NexusJWT_s1gning_k3y_2026';
  return 'secret';
}
function b64url($d){ return rtrim(strtr(base64_encode($d),'+/','-_'),'='); }
function b64url_dec($d){ return base64_decode(strtr($d,'-_','+/').str_repeat('=', (4-strlen($d)%4)%4)); }

function jwt_make(array $payload): string {
    $s0 = jwt_secret(lvl());
    $h = b64url(json_encode(['alg'=>'HS256','typ'=>'JWT']));
    $p = b64url(json_encode($payload));
    $s = b64url(hash_hmac('sha256', "$h.$p", $s0, true));
    return "$h.$p.$s";
}
function jwt_verify(?string $tok): ?array {
    if (!$tok || substr_count($tok,'.')!==2) return null;
    $L = lvl();
    [$h,$p,$s] = explode('.', $tok);
    $hdr = json_decode(b64url_dec($h), true);
    $pl  = json_decode(b64url_dec($p), true);
    if (!is_array($hdr) || !is_array($pl)) return null;
    // alg:none is accepted ONLY at easy; every other level rejects it (sign with the secret instead).
    if (strtolower($hdr['alg'] ?? '') === 'none') return $L==='easy' ? $pl : null;
    $calc = b64url(hash_hmac('sha256', "$h.$p", jwt_secret($L), true));
    return hash_equals($calc, $s) ? $pl : null;
}
