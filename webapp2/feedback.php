<?php
$saved=false;
if ($_SERVER['REQUEST_METHOD']==='POST' && trim($_POST['message']??'')!=='') {
    $line=date('c')." | app=2 | diff=".($_POST['difficulty']??'-')." | ".str_replace(["\n","\r"],' ',$_POST['message'])."\n";
    @file_put_contents(__DIR__.'/feedback.log',$line,FILE_APPEND); $saved=true;
}
?><!doctype html><html><head><meta charset="utf-8"><title>Feedback — VulnNexus</title>
<style>body{font-family:system-ui,Arial;background:#0b0f14;color:#dfe6ee;max-width:700px;margin:30px auto;padding:0 16px}
input,textarea,select{width:100%;background:#0a0d11;color:#dfe6ee;border:1px solid #223042;border-radius:5px;padding:7px;box-sizing:border-box}
button{background:#12a4c9;color:#fff;border:0;border-radius:5px;padding:8px 14px;cursor:pointer}a{color:#58c7e6}</style></head><body>
<h2>💬 Help improve these labs</h2><p><a href="index.php">← back to VulnNexus</a></p>
<?php if($saved) echo "<p style='color:#5fd38a'>✅ Thanks — saved to feedback.log.</p>"; ?>
<form method="post">
 <label>Difficulty</label>
 <select name="difficulty"><option>just right</option><option>too easy</option><option>too hard</option></select><br><br>
 <textarea name="message" rows="5" placeholder="What worked, what didn't, ideas for new vulns / labs..."></textarea><br><br>
 <button>Send feedback</button>
</form>
<p>Prefer GitHub? <a href="https://github.com/wolinskis/css-lab-kit/issues/new?title=Lab%20feedback" target="_blank">Open an issue / suggest a lab</a></p>
<p>Or email: <a href="mailto:github.com.destiny581@passmail.com">github.com.destiny581@passmail.com</a></p>
</body></html>
