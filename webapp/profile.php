<?php require __DIR__.'/header.php'; $db = require __DIR__.'/db.php';
// IDOR: the profile shown is taken straight from ?id= with no ownership / auth check.
$id = $_GET['id'] ?? 2;
$st = $db->prepare("SELECT username,email,role,api_key,private_note FROM users WHERE id=?");
$st->execute([$id]);
$u = $st->fetch(PDO::FETCH_ASSOC);
?>
<div class="card">
  <h2>Profile #<?php echo htmlspecialchars($id); ?></h2>
<?php if ($u): ?>
  <p><strong><?php echo htmlspecialchars($u['username']); ?></strong> (<?php echo htmlspecialchars($u['role']); ?>)</p>
  <p class="muted">Email: <?php echo htmlspecialchars($u['email']); ?></p>
  <p>API key: <code><?php echo htmlspecialchars($u['api_key']); ?></code></p>
  <p>Private note: <?php echo htmlspecialchars($u['private_note']); ?></p>
  <p class="muted">Tip: change the <code>id</code> in the URL to view another record.</p>
<?php else: ?>
  <p class="muted">No such user.</p>
<?php endif; ?>
</div>
<?php require __DIR__.'/footer.php';
