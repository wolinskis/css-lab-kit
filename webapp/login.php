<?php require __DIR__.'/header.php'; require __DIR__.'/levels.php'; $db = require __DIR__.'/db.php'; ?>
<div class="card">
  <h2>Employee login</h2>
<?php if(!isset($_REQUEST['embed'])): ?>
  <form method="post">
    <input name="username" placeholder="username"><br><br>
    <input name="password" type="password" placeholder="password"><br><br>
    <button>Sign in</button>
  </form>
<?php endif; ?>
<?php
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $L = lvl();
    $u = f_login($_POST['username'] ?? '', $L);     // escalating filter per level
    $p = f_login($_POST['password'] ?? '', $L);
    echo "<div class='muted' style='opacity:.55'>level: $L</div>";
    // SQL injection auth bypass: credentials concatenated straight into the query.
    // password is stored SALTED (md5(pass.salt)), so the legit check hashes the input with the row's
    // salt — but $u/$p are still concatenated, so the injection/bypass is unchanged.
    $sql = "SELECT * FROM users WHERE username='$u' AND password=MD5(CONCAT('$p', salt))";
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
