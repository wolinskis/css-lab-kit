<?php require __DIR__.'/header.php'; require __DIR__.'/levels.php'; $db = require __DIR__.'/db.php';
$L = lvl();
// IDOR with escalating reference obfuscation — every level is still forgeable.
if     ($L==='medium') { $id = (int)(base64_decode($_GET['id'] ?? 'Mg==') ?: 2); $how = "id is base64-encoded — send ?id=".base64_encode('1')." (= base64 of 1)"; }
elseif ($L==='hard')   { $h=$_GET['id']??md5('2'); $id=2; for($i=1;$i<=50;$i++){ if(md5((string)$i)===$h){$id=$i;break;} } $how = "id is md5(real id) — send ?id=".md5('1')." (= md5 of 1)"; }
elseif ($L==='expert') { $id = $_COOKIE['uid'] ?? 2; $how = "the ?id param is IGNORED — the server trusts a 'uid' cookie. In the Hub click 'become uid=1' (sets uid=1 scoped to /profile.php, so only this exercise is affected); or manually DevTools -> Application -> Cookies -> add uid=1, then reload"; }
else                   { $id = $_GET['id'] ?? 2; $how = "raw id — just change ?id="; }
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
  <p class="muted">Level <b><?php echo $L; ?></b>: <?php echo htmlspecialchars($how); ?></p>
<?php else: ?>
  <p class="muted">No such user.</p>
<?php endif; ?>
</div>
<?php require __DIR__.'/footer.php';
