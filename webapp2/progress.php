<?php /* VulnNexus progress tracker — dark theme matching the app2 Store/Hub, with the shared app2 nav
         so you navigate back/forward to either. Offline, localStorage (vulnnexus_ keys). */
$TASKS = [
 "UNION SQL injection (search.php)",
 "JWT forgery (alg:none / weak secret)",
 "SSRF → cloud metadata IAM creds + file:// (fetch.php)",
 "XXE (import.php)",
 "Path traversal / source disclosure (download.php)",
 "Filtered reflected XSS (greet.php)",
 "OAuth — redirect_uri not validated (oauth_authorize.php)"
];
?><!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Progress — VulnNexus</title>
<style>
:root{--bg:#0e1016;--card:#171a22;--ink:#e6edf3;--muted:#9aa4b2;--line:#2a2f3a;--brand:#9a6bff;--brand2:#58a6ff;--ok:#3fb950}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.6}
.topbar{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#2a1457,#7a3df0);color:#fff;padding:12px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 2px 8px rgba(30,20,60,.18)}
.brand{font-weight:700;font-size:1.15em}.badge{font-size:.72em;background:rgba(255,255,255,.18);padding:2px 9px;border-radius:20px}
.nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.nav a{color:#fff;text-decoration:none;font-size:.85em;background:rgba(255,255,255,.12);padding:5px 11px;border-radius:7px}.nav a:hover{background:rgba(255,255,255,.26)}
.wrap{max-width:760px;margin:22px auto;padding:0 16px 60px}
.bar{background:#2a2f3a;border-radius:10px;height:14px;overflow:hidden;margin:4px 0}
.bar>i{display:block;height:100%;width:0;background:var(--ok);transition:width .3s}
.count{font-weight:600}
.section{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px 18px 14px;margin:14px 0;box-shadow:0 1px 3px rgba(20,40,80,.06)}
.section h2{font-size:1.05em;margin:8px 0 6px;color:#c7b3ff}
ul{list-style:none;padding:0;margin:0}li{display:flex;gap:10px;align-items:flex-start;padding:7px 0;border-top:1px solid var(--line)}
li:first-child{border-top:0}input[type=checkbox]{width:18px;height:18px;margin-top:3px;accent-color:var(--ok);cursor:pointer}
label{cursor:pointer}.done .name{text-decoration:line-through;color:var(--muted)}
button{background:#20242e;border:1px solid var(--line);color:var(--brand2);border-radius:8px;padding:8px 14px;cursor:pointer;font-size:.9em}button:hover{background:#262b36}
</style></head><body>
<div class="topbar"><span class="brand">◎ VulnNexus</span><span class="badge">app 2 · medium→hard</span>
  <nav class="nav"><a href="index.php">🎯 Hub</a><a href="home.php">🏪 Store</a><a href="progress.php">📊 Progress</a><a href="reset.php">↺ Reset</a><a href="feedback.php">💬 Feedback</a></nav></div>
<div class="wrap">
  <div class="section"><h2>📊 Your progress</h2>
    <div class="bar"><i id="bar"></i></div>
    <p><span class="count" id="count">0 / 0</span> flags captured <span id="trophy"></span></p>
    <button onclick="reset()">Reset my progress</button></div>
  <div class="section"><h2>App 2 — VulnNexus <span style="font-size:.72em;color:var(--muted)">web · medium → hard</span></h2>
    <ul id="tasks"></ul></div>
</div>
<script>
const tasks=<?= json_encode($TASKS) ?>;
const ul=document.getElementById('tasks');
tasks.forEach((t,i)=>{const key='vulnnexus_'+i,li=document.createElement('li');
 const cb=document.createElement('input');cb.type='checkbox';cb.id=key;
 try{cb.checked=localStorage.getItem(key)==='1';}catch(e){}
 cb.onchange=()=>{try{localStorage.setItem(key,cb.checked?'1':'0');}catch(e){}li.classList.toggle('done',cb.checked);render();};
 const lb=document.createElement('label');lb.htmlFor=key;lb.innerHTML='<span class="name">'+t+'</span>';
 li.classList.toggle('done',cb.checked);li.append(cb,lb);ul.append(li);});
function render(){let d=0;tasks.forEach((t,i)=>{try{if(localStorage.getItem('vulnnexus_'+i)==='1')d++;}catch(e){}});
 document.getElementById('count').textContent=d+' / '+tasks.length;
 document.getElementById('bar').style.width=(100*d/tasks.length)+'%';
 document.getElementById('trophy').textContent=d===tasks.length?'🏆 everything captured!':'';}
function reset(){if(confirm('Clear your checklist?')){Object.keys(localStorage).filter(k=>k.startsWith('vulnnexus_')).forEach(k=>localStorage.removeItem(k));location.reload();}}
render();
</script></body></html>
