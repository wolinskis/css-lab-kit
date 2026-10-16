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

/* ---- UI: a level selector button row for the hub (JS-driven) ---- */
function level_row($card){
  $btns='';
  foreach(['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','expert'=>'Expert'] as $k=>$v)
    $btns.="<button type=\"button\" class=\"lvlbtn\" data-l=\"$k\" onclick=\"setLvl('$card','$k')\">$v</button>";
  return "<div class=\"levels\" id=\"lv_$card\">$btns</div>";
}
