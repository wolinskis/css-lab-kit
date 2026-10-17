<?php /* VulnCorp — standalone gamified progress tracker (dark theme, offline, localStorage) */ ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Progress — VulnCorp</title>
<style>
:root{--bg:#0e1016;--card:#171a22;--ink:#e6edf3;--muted:#9aa4b2;--line:#2a2f3a;--brand:#9a6bff;--brand2:#58a6ff;--ok:#3fb950}
*{box-sizing:border-box}body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);margin:0;line-height:1.6}
.wrap{max-width:760px;margin:0 auto;padding:0 16px 60px}
header{background:linear-gradient(120deg,#2a1457,#7a3df0);color:#fff;padding:24px 16px;box-shadow:0 2px 8px rgba(30,20,60,.18)}
header .wrap{padding-bottom:0}h1{margin:0 0 4px;font-size:1.5em}header p{margin:0;opacity:.9}
header .home{display:inline-block;margin-bottom:8px;font-size:.82em;color:#fff;background:rgba(255,255,255,.14);padding:4px 10px;border-radius:7px;text-decoration:none}
header .home:hover{background:rgba(255,255,255,.26)}
.bar{background:#2a2f3a;border-radius:10px;height:14px;overflow:hidden;margin:14px 0 4px}
.bar>i{display:block;height:100%;width:0;background:var(--ok);transition:width .3s}
.count{font-weight:600}
.section{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:6px 18px 14px;margin:18px 0;box-shadow:0 1px 3px rgba(20,40,80,.06)}
.section h2{font-size:1.05em;margin:14px 0 6px;color:#c7b3ff}
ul{list-style:none;padding:0;margin:0}li{display:flex;gap:10px;align-items:flex-start;padding:7px 0;border-top:1px solid var(--line)}
li:first-child{border-top:0}input[type=checkbox]{width:18px;height:18px;margin-top:3px;accent-color:var(--ok);cursor:pointer}
label{cursor:pointer}.done label{color:var(--muted)}.done .name{text-decoration:line-through}
button{background:#20242e;border:1px solid var(--line);color:var(--brand2);border-radius:8px;padding:8px 14px;cursor:pointer;font-size:.9em}button:hover{background:#262b36}
a{color:var(--brand2);text-decoration:none}a:hover{text-decoration:underline}
</style></head><body>
<header><div class="wrap"><a class="home" href="index.php">← back to VulnCorp</a><h1>🏁 VulnCorp — Progress</h1>
<p>Tick each vuln as you capture it. Saved in your browser (localStorage) — no account, fully offline.</p></div></header>
<div class="wrap">
<div class="section"><div class="bar"><i id="bar"></i></div>
<p><span class="count" id="count">0 / 0</span> captured <span id="trophy"></span></p>
<button onclick="reset()">Reset my progress</button></div>
<div class="section"><h2>App 1 — VulnCorp <span style="font-size:.72em;color:var(--muted)">web · easy · foundations</span></h2>
<ul id="tasks"></ul></div>
</div>
<script>
const tasks=[
 "SQL injection — auth bypass / UNION dump",
 "Reflected XSS (search box)",
 "Stored XSS (guestbook)",
 "IDOR (profile ?id=1 → admin flag)",
 "Command injection (Net Tools)",
 "LFI / path traversal (?page=)",
 "File upload webshell (upload.php)",
 "Weak CSP identified",
 "Information disclosure (config.php.bak / security.txt)",
 "Weak / default credentials (login)"
];
const ul=document.getElementById('tasks');
tasks.forEach((t,i)=>{const key='vulncorp_'+i,li=document.createElement('li');
 const cb=document.createElement('input');cb.type='checkbox';cb.id=key;
 try{cb.checked=localStorage.getItem(key)==='1';}catch(e){}
 cb.onchange=()=>{try{localStorage.setItem(key,cb.checked?'1':'0');}catch(e){}li.classList.toggle('done',cb.checked);render();};
 const lb=document.createElement('label');lb.htmlFor=key;lb.innerHTML='<span class="name">'+t+'</span>';
 li.classList.toggle('done',cb.checked);li.append(cb,lb);ul.append(li);});
function render(){let d=0;tasks.forEach((t,i)=>{try{if(localStorage.getItem('vulncorp_'+i)==='1')d++;}catch(e){}});
 document.getElementById('count').textContent=d+' / '+tasks.length;
 document.getElementById('bar').style.width=(100*d/tasks.length)+'%';
 document.getElementById('trophy').textContent=d===tasks.length?'🏆 all done!':'';}
function reset(){if(confirm('Clear your checklist?')){Object.keys(localStorage).filter(k=>k.startsWith('vulncorp_')).forEach(k=>localStorage.removeItem(k));location.reload();}}
render();
</script></body></html>
