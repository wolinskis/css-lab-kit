<?php require __DIR__.'/header.php'; $db = require __DIR__.'/db.php'; ?>

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
  <h2>Guestbook</h2>
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
  <h2>Page</h2>
<?php
  $page = $_GET['page'] ?? 'home.html';
  // Local File Inclusion / path traversal: no sanitisation of the page name.
  @include __DIR__ . '/pages/' . $page;
?>
</div>

<?php require __DIR__.'/footer.php';
