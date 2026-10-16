<?php
// VulnNexus (app 2) — MariaDB bootstrap (uses the throwaway instance run.sh starts).
$S = getenv('VN_DB_SOCK') ?: '';
$dsn = $S ? "mysql:unix_socket=$S" : "mysql:host=127.0.0.1;port=".(getenv('VN_DB_PORT')?:'3308');
$pdo = new PDO($dsn, getenv('VN_DB_USER')?:'root', getenv('VN_DB_PASS')?:'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS vulnnexus CHARACTER SET utf8mb4");
$pdo->exec("USE vulnnexus");
$pdo->exec("CREATE TABLE IF NOT EXISTS products(id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(80), category VARCHAR(40), price DECIMAL(10,2))");
$pdo->exec("CREATE TABLE IF NOT EXISTS secrets(id INT PRIMARY KEY AUTO_INCREMENT, k VARCHAR(40), v VARCHAR(120))");
if ($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO products(name,category,price) VALUES
      ('Nexus Router X1','network',249.00),('Nexus Switch 24','network',599.00),
      ('Nexus Firewall Pro','security',1299.00),('Nexus AP Mini','wireless',89.00)");
    $pdo->exec("INSERT INTO secrets(k,v) VALUES
      ('db_flag','FLAG{un10n_sql1_dumps_the_secrets_table}'),
      ('admin_pw','Nexus!Adm1n#2026')");
}
return $pdo;
