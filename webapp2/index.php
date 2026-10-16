<?php
$self = $_SERVER['HTTP_HOST'] ?? 'localhost:8001';
?><!doctype html><html><head><meta charset="utf-8"><title>VulnNexus API Portal</title>
<style>body{font-family:system-ui,Arial;margin:0;background:#0b0f14;color:#dfe6ee}
header{background:#0e2a3a;padding:12px 20px;border-bottom:2px solid #12a4c9}
.wrap{max-width:860px;margin:22px auto;padding:0 16px} .card{background:#121922;border:1px solid #223042;border-radius:8px;padding:14px;margin:12px 0}
a{color:#58c7e6} code{background:#0a0d11;padding:1px 5px;border-radius:4px;color:#9ce6a0}</style></head><body>
<header><strong style="color:#12a4c9">VulnNexus</strong> — API Portal (app 2 · medium→hard)</header>
<div class="wrap">
<div class="card"><h3>Endpoints</h3>
<ul>
 <li><code>login.php</code> — POST <code>username,password</code> → issues a JWT (try <code>alice/alice123</code>). <em>JWT lab</em></li>
 <li><code>account.php</code> — shows your role from the JWT cookie. <em>JWT forge → admin</em></li>
 <li><code>search.php?q=</code> — product search. <em>UNION SQLi</em></li>
 <li><code>fetch.php?url=</code> — server-side URL fetcher. <em>SSRF</em></li>
 <li><code>import.php</code> — POST an XML document (<code>Content-Type: application/xml</code>). <em>XXE</em></li>
 <li><code>download.php?file=</code> — download a doc from <code>docs/</code>. <em>Path traversal</em></li>
 <li><code>greet.php?name=</code> — greeting (has a naive XSS filter). <em>Filtered reflected XSS</em></li>
</ul>
<p>Six flags to capture. Solutions in <code>SOLUTIONS.md</code>. Method: the Web Penetration Test Checklist.</p>
</div></div></body></html>
