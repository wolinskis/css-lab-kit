<?php $db = require __DIR__.'/db.php';
header('Content-Type: text/plain');
$q = $_GET['q'] ?? '';
echo "Product search for: $q\n\n";
if ($q!=='') {
    // UNION SQLi: 3 columns (name, category, price).
    $sql = "SELECT name, category, price FROM products WHERE name LIKE '%$q%'";
    try { foreach ($db->query($sql) as $r) echo "- {$r['name']} [{$r['category']}] \${$r['price']}\n"; }
    catch (Exception $e) { echo "DB error: ".$e->getMessage()."\n"; }
}
