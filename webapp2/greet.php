<?php require __DIR__.'/levels.php';
setrawcookie('flag','FLAG{xss_filter_byp4ss_c00kie}');  // non-HttpOnly flag cookie — steal via the bypassed XSS
header('Content-Type: text/html');
$L = lvl();
$name = f_xss($_GET['name'] ?? 'guest', $L);            // filter escalates per level
echo "<h2>Hello, $name</h2> <p style='color:#888;font-size:.8em'>level: $L</p>";
