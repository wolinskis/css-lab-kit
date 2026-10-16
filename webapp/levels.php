<?php
// Difficulty engine for VulnCorp. Each vulnerable endpoint reads lvl() and applies an
// escalating, realistic defense. The flag stays reachable at every level — the bypass gets harder.
function lvl(){ $l=strtolower($_REQUEST['level'] ?? 'easy'); return in_array($l,['easy','medium','hard','expert'],true)?$l:'easy'; }

/* ---- SQL injection (search ?q=) ---- */
// easy   : raw concatenation.
// medium : strips 'union'/'select' ONCE (case-insensitive) -> nest them: UNIunionON SELselectECT
// hard   : + strips ALL spaces -> use /**/ for spaces (and still nest the keywords); end with #
// expert : no input filter, but the result is BLIND (only match/no-match) -> boolean/time-based
function f_sqli($q,$l){
  if($l==='medium') return str_ireplace(['union','select'],'',$q);
  if($l==='hard')   return str_replace(' ','', str_ireplace(['union','select'],'',$q));
  return $q;
}

/* ---- command injection (tools ?host=) ---- */
// easy   : raw.
// medium : strips ; and &  -> use a pipe |  (or $(...) or a newline)
// hard   : strips ; & | ` and spaces -> use $(cmd) with ${IFS} for spaces
// expert : no filter, but output is NOT returned (blind) -> exfiltrate (write to uploads/, DNS, time)
function f_cmd($h,$l){
  if($l==='medium') return str_replace([';','&'],'',$h);
  if($l==='hard')   return str_replace([';','&','|','`',' '],'',$h);
  return $h;
}

/* ---- LFI (?page=) ---- */
// easy   : raw include of pages/<name>.
// medium : strips '../' ONCE   -> ....//  (collapses back to ../ after one pass)
// hard   : strips '../' RECURSIVELY (....// dies) -> only a php://filter wrapper works (base64)
// expert : recursive strip + 'base64' blocked in wrappers -> use another filter (e.g. string.rot13)
function f_lfi($p,$l){
  if($l==='medium') return str_replace('../','',$p);
  if($l==='hard' || $l==='expert'){ while(strpos($p,'../')!==false) $p=str_replace('../','',$p); return $p; }
  return $p;
}

/* ---- XSS (reflected search echo + stored guestbook render) ---- */
// All bypasses fire automatically on load (no user interaction needed):
// easy   : raw                       -> <script>alert(1)</script>
// medium : strips '<script'          -> <img src=x onerror=alert(1)>
// hard   : + strips 'onerror'        -> <svg onload=alert(1)>
// expert : + strips 'onload'         -> <svg><animate onbegin=alert(1) attributeName=x dur=1s>
function f_xss($s,$l){
  if($l==='medium') return str_ireplace('<script','',$s);
  if($l==='hard')   return str_ireplace(['<script','onerror'],'',$s);
  if($l==='expert') return str_ireplace(['<script','onerror','onload'],'',$s);
  return $s;
}

/* ---- Insecure upload (upload.php) — content filter. Returns '' if allowed, else a reason. ---- */
// easy   : no checks                               -> a system() webshell
// medium : 'system' blocked                        -> use passthru()
// hard   : + exec/passthru/shell_exec/... blocked  -> use backticks
// expert : + backticks blocked                     -> split the name:  $f='sy'.'stem'; $f(...)
function upload_reject($name,$ctype,$content,$l){
  if($l==='easy') return '';
  if(stripos($content,'system')!==false) return "'system' is blocked in the content";
  if($l==='medium') return '';
  if(preg_match('/\b(passthru|exec|shell_exec|popen|proc_open)\b/i',$content)) return 'exec-family functions are blocked';
  if($l==='hard') return '';
  if(strpos($content,'`')!==false) return 'backticks are blocked';
  return '';
}

/* ---- SQLi login / auth bypass (login.php username field) ---- */
// easy   : raw                       -> admin'-- -            (comment out the password check)
// medium : strips line comments      -> admin' or '1'='1      (self-closing OR, no comment needed)
// hard   : + strips 'or'             -> admin' oorr '1'='1    (nest: oorr -> or)
// expert : + strips spaces           -> admin'/**/oorr/**/'1'='1   (/**/ as spaces)
function f_login($s,$l){
  if($l==='easy') return $s;
  $s=str_ireplace(['-- ','--','#'],'',$s);
  if($l==='medium') return $s;
  $s=str_ireplace('or','',$s);
  if($l==='hard') return $s;
  return str_replace(' ','',$s);
}

/* ---- UI: a level selector button row for the hub (JS-driven) ---- */
function level_row($card){
  $btns='';
  foreach(['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','expert'=>'Expert'] as $k=>$v)
    $btns.="<button type=\"button\" class=\"lvlbtn\" data-l=\"$k\" onclick=\"setLvl('$card','$k')\">$v</button>";
  return "<div class=\"levels\" id=\"lv_$card\">$btns</div>";
}
