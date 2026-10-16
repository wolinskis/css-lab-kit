<?php require __DIR__.'/header.php'; $db = require __DIR__.'/db.php'; ?>
<div class="card">
  <h2>Employee login</h2>
  <form method="post">
    <input name="username" placeholder="username"><br><br>
    <input name="password" type="password" placeholder="password"><br><br>
    <button>Sign in</button>
  </form>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u = $_POST['username'] ?? ''; $p = $_POST['password'] ?? '';
    // SQL injection auth bypass: credentials concatenated straight into the query.
    $sql = "SELECT * FROM users WHERE username='$u' AND password='$p'";
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "<div class='card'>Welcome, <strong>".htmlspecialchars($row['username'])."</strong> (".$row['role'].")<br>";
            echo "API key: <code>".htmlspecialchars($row['api_key'])."</code><br>";
            echo "Note: ".htmlspecialchars($row['private_note'])."</div>";
        } else { echo "<div class='muted'>Invalid credentials.</div>"; }
    } catch (Exception $e) { echo "<div class='muted'>DB error: ".$e->getMessage()."</div>"; }
}
?>
</div>
<?php require __DIR__.'/footer.php';
