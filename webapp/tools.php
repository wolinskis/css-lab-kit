<?php require __DIR__.'/header.php'; ?>
<div class="card">
  <h2>Network tools — ping</h2>
  <form method="get">
    <input name="host" placeholder="host to ping (e.g. 127.0.0.1)" value="<?php echo htmlspecialchars($_GET['host'] ?? ''); ?>">
    <br><br><button>Ping</button>
  </form>
<?php
if (isset($_GET['host']) && $_GET['host']!=='') {
    $host = $_GET['host'];
    // Command injection: user input passed straight to the shell.
    $out = shell_exec("ping -c 1 " . $host . " 2>&1");
    echo "<pre>".htmlspecialchars($out)."</pre>";
}
?>
</div>
<?php require __DIR__.'/footer.php';
