<?php require __DIR__.'/header.php';
$saved=false;
if ($_SERVER['REQUEST_METHOD']==='POST' && trim($_POST['message']??'')!=='') {
    $line=date('c')." | app=1 | diff=".($_POST['difficulty']??'-')." | ".str_replace(["\n","\r"],' ',$_POST['message'])."\n";
    @file_put_contents(__DIR__.'/feedback.log',$line,FILE_APPEND); $saved=true;
}
?>
<div class="card">
  <h2>💬 Help improve these labs</h2>
  <p class="muted">What was confusing? Too easy / too hard? What vuln or lab should we add? Your notes make the training better.</p>
  <?php if($saved) echo "<div class='card' style='border-color:#2e9b4f'>✅ Thanks — your feedback was saved (feedback.log).</div>"; ?>
  <form method="post">
    <label>Difficulty</label>
    <select name="difficulty"><option>just right</option><option>too easy</option><option>too hard</option></select><br><br>
    <textarea name="message" rows="5" placeholder="What worked, what didn't, ideas for new labs / vuln types..."></textarea><br><br>
    <button>Send feedback</button>
  </form>
  <p class="muted">Prefer GitHub? Open an issue / suggest a lab →
    <a href="https://github.com/wolinskis/css-lab-kit/issues/new?title=Lab%20feedback" target="_blank">css-lab-kit issues</a></p>
  <p class="muted">Or email: <a href="mailto:github.com.destiny581@passmail.com">github.com.destiny581@passmail.com</a></p>
</div>
<?php require __DIR__.'/footer.php';
