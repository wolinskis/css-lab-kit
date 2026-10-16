<?php
// Minimal JWT (HS256) — intentionally flawed: weak secret + accepts alg=none.
const JWT_SECRET = 'secret';   // weak, crackable (hashcat -m 16500 / jwt_tool)
function b64url($d){ return rtrim(strtr(base64_encode($d),'+/','-_'),'='); }
function b64url_dec($d){ return base64_decode(strtr($d,'-_','+/').str_repeat('=', (4-strlen($d)%4)%4)); }

function jwt_make(array $payload): string {
    $h = b64url(json_encode(['alg'=>'HS256','typ'=>'JWT']));
    $p = b64url(json_encode($payload));
    $s = b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return "$h.$p.$s";
}
function jwt_verify(?string $tok): ?array {
    if (!$tok || substr_count($tok,'.')!==2) return null;
    [$h,$p,$s] = explode('.', $tok);
    $hdr = json_decode(b64url_dec($h), true);
    $pl  = json_decode(b64url_dec($p), true);
    if (!is_array($hdr) || !is_array($pl)) return null;
    // VULN 1: alg:none is accepted with no signature check.
    if (strtolower($hdr['alg'] ?? '') === 'none') return $pl;
    // VULN 2: HS256 verified against a weak secret ('secret').
    $calc = b64url(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return hash_equals($calc, $s) ? $pl : null;
}
