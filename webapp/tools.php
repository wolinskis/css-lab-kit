<?php require __DIR__.'/header.php'; ?>
<div class="card">
  <h2>Network tools — ping <span class="flag-pill">🏁 command injection</span></h2>
  <p class="muted">This host is passed to a shell <code>ping</code>. Chain your own command, e.g.
     <code>127.0.0.1; cat private/cmdi_flag.txt</code> (also <code>| id</code>, <code>$(id)</code>).</p>
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
