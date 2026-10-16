<?php
setrawcookie("flag","FLAG{xss_st34ls_the_c00kie}");  // flag cookie (deliberately NOT HttpOnly) — steal it via XSS
// Weak/misconfigured CSP on purpose (CSP-bypass lab): allows 'unsafe-inline' for scripts.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; img-src *");
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VulnCorp Intranet</title>
<style>
 :root{--bg:#0d1117;--panel:#161b22;--panel2:#1c2330;--line:#2b3442;--txt:#e6edf3;--muted:#8b949e;--accent:#c81d25;--link:#58a6ff;--ok:#3fb950;--warn:#d29922}
 *{box-sizing:border-box}
 body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,Arial,sans-serif;margin:0;background:var(--bg);color:var(--txt);line-height:1.5}
 header.top{background:linear-gradient(180deg,#10233a,#0e1726);border-bottom:1px solid var(--line);padding:10px 22px;display:flex;flex-wrap:wrap;align-items:center;gap:10px 20px;position:sticky;top:0;z-index:10}
 .brand{font-weight:700;font-size:1.05rem;letter-spacing:.3px;display:flex;align-items:center;gap:8px}
 .brand .dot{width:10px;height:10px;border-radius:2px;background:var(--accent);display:inline-block}
 .brand small{color:var(--muted);font-weight:400;font-size:.8rem}
 nav.main{display:flex;flex-wrap:wrap;gap:4px;margin-left:auto}
 nav.main a{color:var(--link);text-decoration:none;padding:6px 11px;border-radius:7px;font-size:.92rem;transition:background .15s}
 nav.main a:hover{background:#ffffff14}
 nav.main a.tool{color:var(--txt)}
 nav.main a.act-reset{color:var(--warn)} nav.main a.act-prog{color:#79c0ff} nav.main a.act-fb{color:var(--ok)}
 .wrap{max-width:920px;margin:26px auto;padding:0 18px}
 h1,h2,h3{line-height:1.25}
 .card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:18px 20px;margin:16px 0;box-shadow:0 1px 0 #ffffff07,0 8px 24px #00000040}
 .card h2{margin:.1em 0 .6em;font-size:1.25rem}
 label{display:inline-block;color:var(--muted);font-size:.85rem;margin-bottom:4px}
 input,textarea,select{background:#0b0f16;color:var(--txt);border:1px solid #30394a;border-radius:8px;padding:9px 11px;width:100%;font-size:.95rem}
 input:focus,textarea:focus,select:focus{outline:none;border-color:var(--link);box-shadow:0 0 0 3px #1f6feb33}
 button{background:var(--accent);color:#fff;border:0;border-radius:8px;padding:9px 18px;cursor:pointer;font-weight:600;font-size:.95rem}
 button:hover{filter:brightness(1.08)}
 code{background:#0b0f16;padding:2px 6px;border-radius:5px;color:#7ee787;font-size:.9em;border:1px solid #ffffff10}
 .muted{color:var(--muted);font-size:.9rem}
 .banner-ok{background:#12351f;border:1px solid #2ea04399;color:#aff0b5}
 .flag-pill{display:inline-block;background:#1f2a1f;border:1px solid #2ea04355;color:#7ee787;border-radius:999px;padding:2px 10px;font-size:.85rem}
 a{color:var(--link)}
 footer.foot{max-width:920px;margin:40px auto 24px;padding:14px 18px;border-top:1px solid var(--line);color:var(--muted);font-size:.82rem}
 footer.foot .warn{color:var(--warn)}
</style></head><body>
<header class="top">
 <span class="brand"><span class="dot"></span>VulnCorp <small>Intranet · training</small></span>
 <nav class="main">
  <a href="home.php">Home</a>
  <a href="index.php">🎯 Hub</a>
  <a href="login.php">Login</a>
  <a href="profile.php?id=2">My Profile</a>
  <a href="tools.php" class="tool">Net Tools</a>
  <a href="upload.php" class="tool">Upload</a>
  <a href="home.php?page=about.html">About</a>
  <a href="progress.php" class="act-prog">📊 Progress</a>
  <a href="reset.php" class="act-reset" title="wipe DB, comments and uploads to a clean state">↺ Reset</a>
  <a href="feedback.php" class="act-fb">💬 Feedback</a>
 </nav>
</header>
<div class="wrap">
<!-- TODO(dev): remove the server-status link and the config backup before go-live. SRV: web01-stg -->
