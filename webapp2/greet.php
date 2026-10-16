<?php
setrawcookie('flag','FLAG{xss_filter_byp4ss_c00kie}');  // non-HttpOnly flag cookie — steal via the bypassed XSS
header('Content-Type: text/html');
$name = $_GET['name'] ?? 'guest';
// Naive XSS filter: strips "<script>" once, case-insensitively. Bypassable.
$name = str_ireplace('<script>','',$name);
echo "<h2>Hello, $name</h2>";
