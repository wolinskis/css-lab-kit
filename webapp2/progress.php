<?php /* standalone progress tracker for VulnNexus */ ?>
<!doctype html><html><head><meta charset="utf-8"><title>Progress — VulnNexus</title>
<style>body{font-family:system-ui,Arial;background:#0b0f14;color:#dfe6ee;max-width:700px;margin:30px auto;padding:0 16px;line-height:2}
a{color:#58c7e6}button{background:#12a4c9;color:#fff;border:0;border-radius:5px;padding:8px 14px;cursor:pointer}ul{list-style:none;padding:0}</style></head><body>
<h2>📊 Your progress</h2><p><a href="index.php">← back to VulnNexus</a></p>
<p class="muted">Tick each flag as you capture it (saved in your browser).</p>
<p id="count" style="font-size:1.2em"></p><ul id="tasks"></ul>
<button onclick="if(confirm('Clear your checklist?')){Object.keys(localStorage).filter(k=>k.startsWith('vulnnexus_')).forEach(k=>localStorage.removeItem(k));location.reload();}">Reset checklist</button>
<script>
const tasks=["UNION SQL injection (search.php)","JWT forgery (alg:none / weak secret)","SSRF (internal metadata + file://)","XXE (import.php)","Path traversal (download.php)","Filtered reflected XSS (greet.php)"];
const ul=document.getElementById('tasks');
tasks.forEach((t,i)=>{const key='vulnnexus_'+i,li=document.createElement('li');
 const cb=document.createElement('input');cb.type='checkbox';cb.id=key;cb.checked=localStorage.getItem(key)==='1';
 cb.onchange=()=>{localStorage.setItem(key,cb.checked?'1':'0');render();};
 const lb=document.createElement('label');lb.htmlFor=key;lb.textContent=' '+t;li.append(cb,lb);ul.append(li);});
function render(){const d=tasks.filter((t,i)=>localStorage.getItem('vulnnexus_'+i)==='1').length;
 document.getElementById('count').innerHTML='<strong>'+d+' / '+tasks.length+'</strong> captured'+(d===tasks.length?' 🏆 all done!':'');}
render();
</script></body></html>
