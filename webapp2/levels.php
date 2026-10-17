<?php
// Difficulty engine for VulnNexus (app 2). Each endpoint reads lvl() and applies an escalating,
// realistic defense; the flag stays reachable at every level.
function lvl(){ $l=strtolower($_REQUEST['level'] ?? 'easy'); return in_array($l,['easy','medium','hard','expert'],true)?$l:'easy'; }

/* ---- SQL injection (search ?q=) ---- */
function f_sqli($q,$l){
  if($l==='medium') return str_ireplace(['union','select'],'',$q);
  if($l==='hard')   return str_replace(' ','', str_ireplace(['union','select'],'',$q));
  return $q;                                   // easy raw; expert raw but BLIND (in search.php)
}

/* ---- XSS filter (greet ?name=) ---- */
// bypasses auto-fire on load: <script> / <img onerror> / <svg onload> / <svg><animate onbegin>
function f_xss($s,$l){
  if($l==='medium') return str_ireplace('<script','',$s);
  if($l==='hard')   return str_ireplace(['<script','onerror'],'',$s);
  if($l==='expert') return str_ireplace(['<script','onerror','onload'],'',$s);
  return $s;
}

/* ---- path traversal (download ?file=) ---- */
function f_trav($p,$l){
  if($l==='medium') return str_replace('../','',$p);                       // once -> ....//
  if($l==='hard' || $l==='expert'){ while(strpos($p,'../')!==false) $p=str_replace('../','',$p); return $p; } // recursive
  return $p;                                                                // easy raw
}

/* ---- SSRF (fetch ?url=) : returns TRUE if the URL is blocked at this level ---- */
// Target = the cloud metadata service: 169.254.169.254 (AWS/Azure) / metadata.google.internal (GCP).
// host normalization in imds.php resolves every numeric encoding, so each level forces a new bypass:
// easy   : nothing blocked -> http://169.254.169.254/latest/meta-data/iam/security-credentials/
// medium : block the literal IP + 'metadata' names (+127/localhost) -> decimal http://2852039166/...
// hard   : + block pure-decimal host + '0x' hex        -> octal   http://0251.0376.0251.0376/...
// expert : + block octal (leading-zero octet) + file:// -> IPv6-mapped http://[::ffff:a9fe:a9fe]/...
function ssrf_block($url,$l){
  if($l==='easy') return false;
  $u=strtolower($url);
  if(strpos($u,'169.254.169.254')!==false) return true;                                     // literal link-local IP
  if(strpos($u,'metadata.google.internal')!==false || strpos($u,'metadata')!==false) return true; // GCP/Azure by name/path
  if(strpos($u,'localhost')!==false || strpos($u,'127.')!==false) return true;
  if($l==='medium') return false;                              // bypass: decimal / hex / octal / IPv6-mapped
  if(preg_match('#://[0-9]+[:/]#',$u)) return true;            // no pure-decimal host
  if(strpos($u,'0x')!==false) return true;                    // no hex
  if($l==='hard') return false;                                // bypass: octal dotted / IPv6-mapped
  if(strpos($u,'file:')!==false) return true;                 // no file:// at expert
  if(preg_match('#://\[?0[0-7]+\.#',$u)) return true;          // no octal dotted (leading-zero octet)
  return false;                                                // bypass: IPv6-mapped [::ffff:a9fe:a9fe]
}
