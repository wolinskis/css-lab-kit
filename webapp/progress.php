<?php require __DIR__.'/header.php'; ?>
<div class="card">
  <h2>📊 Your progress</h2>
  <p class="muted">Tick each vuln as you capture it. Saved in your browser (localStorage) — survives reloads.</p>
  <p id="count" style="font-size:1.2em"></p>
  <ul id="tasks" style="list-style:none;padding:0;line-height:2"></ul>
  <button onclick="if(confirm('Clear your checklist?')){Object.keys(localStorage).filter(k=>k.startsWith('vulncorp_')).forEach(k=>localStorage.removeItem(k));location.reload();}">Reset checklist</button>
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
 const cb=document.createElement('input');cb.type='checkbox';cb.id=key;cb.checked=localStorage.getItem(key)==='1';
 cb.onchange=()=>{localStorage.setItem(key,cb.checked?'1':'0');render();};
 const lb=document.createElement('label');lb.htmlFor=key;lb.textContent=' '+t;
 li.append(cb,lb);ul.append(li);});
function render(){const d=tasks.filter((t,i)=>localStorage.getItem('vulncorp_'+i)==='1').length;
 document.getElementById('count').innerHTML='<strong>'+d+' / '+tasks.length+'</strong> captured'+(d===tasks.length?' 🏆 all done!':'');}
render();
</script>
<?php require __DIR__.'/footer.php';
