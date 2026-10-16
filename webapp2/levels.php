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
  if($l==='medium' || $l==='hard') return str_replace('../','',$p);
  return $p;                                   // easy raw; expert via php://filter
}

/* ---- SSRF (fetch ?url=) : returns TRUE if the URL is blocked at this level ---- */
// easy   : nothing blocked.
// medium : blocks 127.0.0.1 / localhost -> use 127.1  (or decimal / octal / 0.0.0.0)
// hard   : + blocks 127. / 0.0.0.0      -> use decimal http://2130706433:8001/  (or hex / octal)
// expert : + blocks decimal / 0x hex / file:// -> use octal http://0177.0.0.1:8001/
function ssrf_block($url,$l){
  if($l==='easy') return false;
  $u=strtolower($url);
  if(strpos($u,'127.0.0.1')!==false || strpos($u,'localhost')!==false) return true;
  if($l==='medium') return false;
  if(strpos($u,'127.')!==false || strpos($u,'0.0.0.0')!==false) return true;
  if($l==='hard') return false;
  if(strpos($u,'file:')!==false) return true;                 // no file:// at expert
  if(preg_match('#://0x#',$u)) return true;                   // no hex host
  if(preg_match('#://[0-9]+[:/]#',$u)) return true;           // no pure-decimal host
  return false;
}
