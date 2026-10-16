<?php
// VulnNexus (app 2) — MariaDB bootstrap (uses the throwaway instance run.sh starts).
$S = getenv('VN_DB_SOCK') ?: '';
$dsn = $S ? "mysql:unix_socket=$S" : "mysql:host=127.0.0.1;port=".(getenv('VN_DB_PORT')?:'3308');
$pdo = new PDO($dsn, getenv('VN_DB_USER')?:'root', getenv('VN_DB_PASS')?:'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS vulnnexus CHARACTER SET utf8mb4");
$pdo->exec("USE vulnnexus");
$pdo->exec("CREATE TABLE IF NOT EXISTS products(id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(80), category VARCHAR(40), price DECIMAL(10,2))");
$pdo->exec("CREATE TABLE IF NOT EXISTS secrets(id INT PRIMARY KEY AUTO_INCREMENT, k VARCHAR(40), v VARCHAR(120))");
$pdo->exec("CREATE TABLE IF NOT EXISTS users(id INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(64), email VARCHAR(128), password CHAR(32), role VARCHAR(32))");
if ($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO products(name,category,price) VALUES
      ('Nexus Router X1','network',249.00),('Nexus Switch 24','network',599.00),
      ('Nexus Firewall Pro','security',1299.00),('Nexus AP Mini','wireless',89.00)");
    $pdo->exec("INSERT INTO secrets(k,v) VALUES
      ('db_flag','FLAG{un10n_sql1_dumps_the_secrets_table}'),
      ('admin_pw','Nexus!Adm1n#2026')");
}
// users table: MD5 password hashes (unsalted — the realistic weak pattern). admin's password
// equals secrets.admin_pw (credential reuse); the rest crack straight from rockyou.
if ($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO users(username,email,password,role) VALUES
      ('admin','admin@vulnnexus.local','0af631c7ac7650159ad28fa7e58ce3e7','admin'),
      ('j.doe','j.doe@vulnnexus.local','7c6a180b36896a0a8c02787eeafb0e4c','editor'),
      ('a.smith','a.smith@vulnnexus.local','0571749e2ac330a7455809c6b0e7af90','user'),
      ('m.chen','m.chen@vulnnexus.local','8621ffdbc5698829397d97767ac13db3','user'),
      ('svc_api','svc_api@vulnnexus.local','0d107d09f5bbe40cade3de5c71e9e9b7','service')");
}
return $pdo;
