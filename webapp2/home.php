<?php
// Plain "storefront" view of VulnNexus — drive the raw endpoints DIRECTLY (the real-target way).
// The fancy guided console is index.php (the Hub). Same vulnerable code behind both.
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VulnNexus — store</title>
<style>
:root{--bg:#0e1016;--card:#171a22;--ink:#e6edf3;--muted:#9aa4b2;--line:#2a2f3a;--brand:#9a6bff;--brand2:#58a6ff}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.5}
.topbar{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#2a1457,#7a3df0);color:#fff;padding:12px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(30,20,60,.18)}
.brand{font-weight:700;font-size:1.15em}.badge{font-size:.72em;background:rgba(255,255,255,.18);padding:2px 9px;border-radius:20px}
.nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;font-size:.85em;background:rgba(255,255,255,.12);padding:5px 11px;border-radius:7px}.nav a:hover{background:rgba(255,255,255,.26)}
.wrap{max-width:900px;margin:22px auto;padding:0 16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:14px 0}
.card h2{margin:0 0 6px;font-size:1.02em;color:#c7b3ff;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.card h2 code{background:#20242e;color:#b79cff;padding:2px 7px;border-radius:5px;font-size:.9em;margin-left:auto}
.muted{color:var(--muted);font-size:.9em}
form{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:8px 0 0}
input[type=text],input[type=password],textarea{flex:1;min-width:200px;padding:8px 10px;background:#0b0f16;color:var(--ink);border:1px solid var(--line);border-radius:7px;font-size:.9em}
textarea{min-height:80px;width:100%;font-family:ui-monospace,Consolas,monospace}
button{background:var(--brand);color:#fff;border:0;border-radius:7px;padding:8px 14px;cursor:pointer;font-size:.88em}
a{color:var(--brand2)}
pre.out{white-space:pre-wrap;background:#0b0f16;border:1px solid var(--line);border-radius:8px;padding:10px;margin-top:8px;font-family:ui-monospace,Consolas,monospace;font-size:.85em;max-height:220px;overflow:auto;display:none}
</style></head><body>
<div class="topbar"><span class="brand">⬡ VulnNexus</span><span class="badge">store · plain view</span>
<nav class="nav"><a href="index.php">🎯 Hub</a><a href="home.php">🏪 Store</a><a href="progress.php">📊 Progress</a><a href="reset.php" title="drop &amp; reseed the DB">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">

<div class="card"><p class="muted">This is the <b>plain</b> VulnNexus app — drive each endpoint <b>directly in the URL / forms</b>, like a real target (no guided payloads, no hints). Prefer the fancy, guided console with level buttons and examples? → <a href="index.php">🎯 Hub</a>. <b>Same vulnerable code behind both.</b> Responses are raw (text / JSON / XML).</p></div>

<div class="card"><h2>Product search <code>search.php?q=</code></h2>
  <p class="muted">Search the product catalogue.</p>
  <form method="get" action="search.php"><input type="text" name="q" placeholder="e.g. Nexus"><button>Search</button></form></div>

<div class="card"><h2>Account login <code>login.php</code></h2>
  <p class="muted">Sign in to get a session token.</p>
  <form method="post" action="login.php"><input type="text" name="username" placeholder="username (e.g. alice)"><input type="password" name="password" placeholder="password"><button>Sign in</button></form></div>

<div class="card"><h2>Link preview <code>fetch.php?url=</code></h2>
  <p class="muted">The server fetches a URL on your behalf (an image / link preview).</p>
  <form method="get" action="fetch.php"><input type="text" name="url" placeholder="https://example.com/"><button>Preview</button></form></div>

<div class="card"><h2>Download a document <code>download.php?file=</code></h2>
  <p class="muted">Grab a file from the docs folder.</p>
  <form method="get" action="download.php"><input type="text" name="file" placeholder="e.g. readme.txt"><button>Download</button></form></div>

<div class="card"><h2>Greeting <code>greet.php?name=</code></h2>
  <form method="get" action="greet.php"><input type="text" name="name" placeholder="your name"><button>Greet me</button></form></div>

<div class="card"><h2>Import an order (XML) <code>import.php</code></h2>
  <p class="muted">POST an XML order; the server parses it. <i>(needs php-xml)</i></p>
  <textarea id="xml">&lt;order&gt;&lt;item&gt;Nexus Router X1&lt;/item&gt;&lt;/order&gt;</textarea>
  <div style="margin-top:8px"><button type="button" onclick="sendXml()">Send XML</button></div>
  <pre class="out" id="xo"></pre></div>

<div class="card"><h2>Sign in with NexusID <code>oauth_authorize.php</code></h2>
  <p class="muted">An OAuth-style authorization endpoint. <a href="oauth_authorize.php?redirect_uri=http://localhost:8001/oauth_callback.php&amp;state=x">Authorize →</a> (returns to the registered app).</p></div>

</div>
<script>
function sendXml(){var o=document.getElementById('xo');o.style.display='block';o.textContent='sending…';
 fetch('import.php',{method:'POST',headers:{'Content-Type':'application/xml'},body:document.getElementById('xml').value})
  .then(function(r){return r.text()}).then(function(t){o.textContent=t}).catch(function(e){o.textContent='error: '+e});}
</script>
</body></html>
