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
// medium : strips '../' once  -> ....//  (collapses back to ../ after one pass)
// hard   : strips '../' once AND requires a .html/.txt extension -> ....//….//lfi_secret/flag.txt
// expert : traversal blocked, but php:// wrappers allowed -> php://filter source disclosure
function f_lfi($p,$l){
  if($l==='medium' || $l==='hard') return str_replace('../','',$p);
  return $p;
}

/* ---- XSS (reflected search echo + stored guestbook render) ---- */
// easy   : raw -> <script>alert(1)</script>
// medium : strips '<script' once -> <img src=x onerror=alert(1)>
// hard   : + strips onerror/onload -> <details open ontoggle=alert(1)>
// expert : + strips ontoggle -> <input autofocus onfocus=alert(1)>   (blocklists always miss one)
function f_xss($s,$l){
  if($l==='medium') return str_ireplace('<script','',$s);
  if($l==='hard')   return str_ireplace(['<script','onerror','onload'],'',$s);
  if($l==='expert') return str_ireplace(['<script','onerror','onload','ontoggle'],'',$s);
  return $s;
}

/* ---- Insecure upload (upload.php). Returns '' if allowed, else a rejection reason. ---- */
// easy   : no checks.
// medium : Content-Type must be image/* -> set a fake image content-type on the upload.
// hard   : + content must start with image magic bytes -> prepend 'GIF89a' before <?php .
// expert : + the word 'system' is blocked in the content -> use passthru()/shell_exec()/backticks.
function upload_reject($name,$ctype,$content,$l){
  if($l==='easy') return '';
  if(strpos((string)$ctype,'image/')!==0) return 'content-type must be image/*';
  if($l==='medium') return '';
  if(!preg_match('/^(GIF8|\xFF\xD8\xFF|\x89PNG)/',$content)) return 'content must start with image magic bytes';
  if($l==='hard') return '';
  if(stripos($content,'system')!==false) return "the word 'system' is blocked";
  return '';
}

/* ---- UI: a level selector button row for the hub (JS-driven) ---- */
function level_row($card){
  $btns='';
  foreach(['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','expert'=>'Expert'] as $k=>$v)
    $btns.="<button type=\"button\" class=\"lvlbtn\" data-l=\"$k\" onclick=\"setLvl('$card','$k')\">$v</button>";
  return "<div class=\"levels\" id=\"lv_$card\">$btns</div>";
}
