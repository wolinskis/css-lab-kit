<?php require __DIR__.'/levels.php'; $db = require __DIR__.'/db.php';
header('Content-Type: text/plain');
$L = lvl();
$raw = $_GET['q'] ?? '';
$q = f_sqli($raw, $L);                         // escalating input filter per level
echo "Product search for: $raw\n(level: $L)\n\n";
if ($raw!=='') {
    $sql = "SELECT name, category, price FROM products WHERE name LIKE '$q%'";   // 3 cols; prefix-match so a plain ' UNION ...-- - needs no filler
    try {
        if ($L==='expert') {                    // BLIND: only match / no-match
            $n=0; foreach ($db->query($sql) as $r) { $n++; }
            echo $n>0 ? "match\n" : "no match\n";
        } else {
            foreach ($db->query($sql) as $r) echo "- {$r['name']} [{$r['category']}] \${$r['price']}\n";
        }
    } catch (Exception $e) { echo "DB error: ".$e->getMessage()."\n"; }
}
