<?php
// Weak/misconfigured CSP on purpose (CSP-bypass lab): allows 'unsafe-inline' for scripts.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; img-src *");
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VulnCorp Intranet</title>
<style>
 body{font-family:system-ui,Arial,sans-serif;margin:0;background:#0f1216;color:#e6e6e6}
 header{background:#111826;padding:12px 20px;border-bottom:2px solid #c81d25;display:flex;flex-wrap:wrap;align-items:center;gap:14px}
 header .brand{font-weight:bold}
 header nav{display:flex;flex-wrap:wrap;gap:18px}
 header nav a{color:#9cc3ff;text-decoration:none}
 header nav a:hover{text-decoration:underline}
 .wrap{max-width:900px;margin:24px auto;padding:0 16px}
 .card{background:#161c26;border:1px solid #273142;border-radius:8px;padding:16px;margin:14px 0}
 input,textarea,select{background:#0f1216;color:#e6e6e6;border:1px solid #39465c;border-radius:5px;padding:7px;width:100%;box-sizing:border-box}
 button{background:#c81d25;color:#fff;border:0;border-radius:5px;padding:8px 14px;cursor:pointer}
 code{background:#0b0e12;padding:1px 5px;border-radius:4px;color:#9ce6a0}
 .muted{color:#8a93a3;font-size:.9em}
</style></head><body>
<header>
 <span class="brand"><span style="color:#c81d25">VulnCorp</span> Intranet</span>
 <nav>
  <a href="index.php">Home</a>
  <a href="login.php">Login</a>
  <a href="profile.php?id=2">My Profile</a>
  <a href="tools.php">Net Tools</a>
  <a href="upload.php">Upload</a>
  <a href="index.php?page=about">About</a>
  <a href="reset.php" style="color:#f1a33c" title="wipe the DB, comments and uploads back to a clean state">↺ Reset</a>
  <a href="feedback.php" style="color:#5fd38a">💬 Feedback</a>
 </nav>
</header>
<div class="wrap">
<!-- TODO(dev): remove the server-status link and the config backup before go-live. SRV: web01-stg -->
