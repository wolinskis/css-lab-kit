<?php $self = $_SERVER['HTTP_HOST'] ?? 'localhost:8001'; ?><!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>VulnNexus — API Portal</title>
<style>
:root{--bg:#f4f6fb;--card:#fff;--ink:#1b2430;--muted:#5b6b7e;--line:#e3e8f0;--brand:#7a3df0;--brand2:#1f6feb;--ok:#1f9d57}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.55}
.topbar{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#2a1457,#7a3df0);color:#fff;padding:12px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(30,20,60,.18)}
.brand{font-weight:700;font-size:1.15em;letter-spacing:.3px}.badge{font-size:.72em;background:rgba(255,255,255,.18);padding:2px 9px;border-radius:20px}
.nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;font-size:.85em;background:rgba(255,255,255,.12);padding:5px 11px;border-radius:7px}.nav a:hover{background:rgba(255,255,255,.26)}
.wrap{max-width:880px;margin:22px auto;padding:0 16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 3px rgba(20,40,80,.06)}
.card h2{margin:0 0 10px;font-size:1.05em;color:#2a1457}
.ep{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-top:1px solid var(--line)}.ep:first-child{border-top:0}
.ep code{background:#f0ecfb;color:#5b2bd6;padding:2px 7px;border-radius:5px;font-size:.88em;white-space:nowrap}
.ep .d{flex:1}.ep .v{font-size:.78em;color:var(--muted)}
.flag-pill{font-size:.7em;font-weight:600;background:#fdebc6;color:#8a5a00;padding:2px 8px;border-radius:20px;white-space:nowrap}
.ok{border-color:var(--ok);background:#eafaf0}.muted{color:var(--muted);font-size:.92em}
footer{max-width:880px;margin:10px auto 50px;padding:14px 16px;color:var(--muted);font-size:.85em;border-top:1px solid var(--line)}
footer a{color:var(--brand2)}
</style></head><body>
<div class="topbar"><span class="brand">⬡ VulnNexus</span><span class="badge">app 2 · medium → hard</span>
<nav class="nav"><a href="index.php">Home</a><a href="progress.php">📊 Progress</a><a href="reset.php" title="drop &amp; reseed the DB">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">
<?php if(isset($_GET['reset'])) echo "<div class='card ok'>✅ Lab reset to a clean state (DB reseeded).</div>"; ?>

<div class="card"><h2>API endpoints — seven flags to capture 🏁</h2>
<p class="muted">An API-style target: responses are raw (JSON / text / XML), like a real backend. Attack each
endpoint, capture its <code>FLAG{…}</code>, and tick it on the <a href="progress.php">Progress</a> page.
Default creds matter only for the JWT lab; everything else you attack unauthenticated.</p>

<div class="ep"><code>login.php</code><div class="d">POST <code>username,password</code> → issues a JWT (try <code>alice/alice123</code>).<div class="v">entry point for the JWT lab</div></div></div>
<div class="ep"><code>account.php</code><div class="d">Shows your role from the JWT cookie. <span class="flag-pill">🏁 JWT forgery</span><div class="v">forge <code>role=admin</code>: <code>alg:none</code> or crack the weak HS256 secret</div></div></div>
<div class="ep"><code>search.php?q=</code><div class="d">Product search. <span class="flag-pill">🏁 UNION SQLi</span><div class="v"><code>' UNION SELECT k,v,3 FROM secrets-- -</code></div></div></div>
<div class="ep"><code>fetch.php?url=</code><div class="d">Server-side URL fetcher. <span class="flag-pill">🏁 SSRF</span><div class="v"><code>url=http://127.0.0.1:8001/metadata.php</code>, <code>file:///etc/passwd</code></div></div></div>
<div class="ep"><code>import.php</code><div class="d">POST an XML document (<code>Content-Type: application/xml</code>). <span class="flag-pill">🏁 XXE</span><div class="v">external entity → <code>file:///etc/passwd</code> (needs <code>php-xml</code>)</div></div></div>
<div class="ep"><code>download.php?file=</code><div class="d">Download a doc from <code>docs/</code>. <span class="flag-pill">🏁 path traversal</span><div class="v"><code>file=../private2/secret_config.txt</code></div></div></div>
<div class="ep"><code>oauth_authorize.php?redirect_uri=</code><div class="d">OAuth login flow. <span class="flag-pill">🏁 OAuth</span><div class="v"><code>redirect_uri</code> not validated → steal the victim's auth code</div></div></div>
<div class="ep"><code>greet.php?name=</code><div class="d">Greeting with a naive XSS filter. <span class="flag-pill">🏁 filter bypass</span><div class="v"><code>&lt;img src=x onerror=...&gt;</code> or nested <code>&lt;scr&lt;script&gt;ipt&gt;</code></div></div></div>
</div>

<div class="card muted">Walkthroughs (Answer / Command / Result) are in <code>SOLUTIONS.md</code> — try each endpoint first, then check. Method: the Web Penetration Test Checklist.</div>
</div>
<footer>⚠️ <b>Intentionally vulnerable — training only.</b> Run locally / in an isolated lab; never expose it. ·
<a href="feedback.php">Report a bug / idea</a></footer>
</body></html>
