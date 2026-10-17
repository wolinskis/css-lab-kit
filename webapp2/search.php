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
            if ($n>0) {
                echo "TRUE (match)\n\n";
                echo "This level is BLIND: it only answers TRUE/FALSE, never prints rows.\n";
                echo "Exfiltrate one character at a time with yes/no questions, e.g.\n";
                echo "  ' OR (SELECT SUBSTRING(v,1,1) FROM secrets WHERE k='db_flag')='F'-- -\n";
                echo "TRUE -> char 1 is 'F'. Walk the position + guessed letter to rebuild it, or automate with sqlmap.\n";
                echo "(Want it dumped directly? use a lower level and UNION the secrets table.)\n";
            } else {
                echo "FALSE (no match)\n\nBlind SQLi: change the guessed character/position and watch TRUE/FALSE.\n";
            }
        } else {
            foreach ($db->query($sql) as $r) echo "- {$r['name']} [{$r['category']}] \${$r['price']}\n";
        }
    } catch (Exception $e) { echo "DB error: ".$e->getMessage()."\n"; }
}
