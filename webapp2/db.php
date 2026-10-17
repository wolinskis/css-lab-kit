<?php
// VulnNexus (app 2) — MariaDB bootstrap (uses the throwaway instance run.sh starts).
$S = getenv('VN_DB_SOCK') ?: '';
$dsn = $S ? "mysql:unix_socket=$S" : "mysql:host=127.0.0.1;port=".(getenv('VN_DB_PORT')?:'3308');
$pdo = new PDO($dsn, getenv('VN_DB_USER')?:'root', getenv('VN_DB_PASS')?:'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS vulnnexus CHARACTER SET utf8mb4");
$pdo->exec("USE vulnnexus");
$pdo->exec("CREATE TABLE IF NOT EXISTS products(id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(80), category VARCHAR(40), price DECIMAL(10,2))");
$pdo->exec("CREATE TABLE IF NOT EXISTS secrets(id INT PRIMARY KEY AUTO_INCREMENT, k VARCHAR(40), v VARCHAR(120))");
$pdo->exec("CREATE TABLE IF NOT EXISTS users(id INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(64), email VARCHAR(128), password VARCHAR(255), salt VARCHAR(16), role VARCHAR(32))");
if ($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO products(name,category,price) VALUES
      ('Nexus Router X1','network',249.00),('Nexus Switch 24','network',599.00),
      ('Nexus Firewall Pro','security',1299.00),('Nexus AP Mini','wireless',89.00)");
    $pdo->exec("INSERT INTO secrets(k,v) VALUES
      ('db_flag','FLAG{un10n_sql1_dumps_the_secrets_table}'),
      ('admin_pw','Nexus!Adm1n#2026')");
}
// users table: MIXED hash algorithms — this is a hash-IDENTIFICATION + cracking lab. Dump it via the
// SQLi, then `hashid`/`hash-identifier` each hash, pick the right hashcat/john mode, and crack:
//   admin   sha256(pass.salt)  hashcat -m 1410  (salt in the salt column)  -> STRONG, won't crack
//   j.doe   md5 (unsalted)     hashcat -m 0   / john raw-md5    -> instant  ("md5 is broken")
//   a.smith sha1 (unsalted)    hashcat -m 100 / john raw-sha1   -> instant
//   m.chen  sha256(pass.salt)  hashcat -m 1410 (salt col)       -> fast, but you must dump the salt
//   svc_api bcrypt (cost 8)    hashcat -m 3200/ john bcrypt     -> slow by design (self-salted $2y$)
// Lesson: fast/legacy hashes fall instantly, salting forces a per-hash crack, and bcrypt's work factor
// is what actually buys time. admin's strong password won't crack even so, but it equals secrets.admin_pw
// (credential reuse), which the SQLi already leaked -> account takeover without any cracking.
if ($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO users(username,email,password,salt,role) VALUES
      ('admin','admin@vulnnexus.local','99641c0c750dcc9293db073d2e7cbf977d7d455aeaef4547f12a65a6740b79fb','kR9fQ2','admin'),
      ('j.doe','j.doe@vulnnexus.local','84d961568a65073a3bcf0eb216b2a576','','editor'),
      ('a.smith','a.smith@vulnnexus.local','e68e11be8b70e435c65aef8ba9798ff7775c361e','','user'),
      ('m.chen','m.chen@vulnnexus.local','f145d6227d344b596be8dd6624f81a099e5ccd4234d181a653c1fdfc0a13d5d1','Tb82nR','user'),
      ('svc_api','svc_api@vulnnexus.local','\$2y\$08\$cguMochk7lKasJ6uKqA4dOQ8HnotyVZe7P0apRXlmfRdyP.9Vvbh.','','service')");
}
// expert JWT signing secret lives in the secrets table -> recover it via the SQLi, then sign admin.
if ($pdo->query("SELECT COUNT(*) FROM secrets WHERE k='jwt_sign_secret'")->fetchColumn()==0) {
    $pdo->exec("INSERT INTO secrets(k,v) VALUES ('jwt_sign_secret','\$Z3pk9_NexusJWT_s1gning_k3y_2026')");
}
return $pdo;
