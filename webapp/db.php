<?php
// VulnCorp Intranet — MySQL/MariaDB bootstrap. Creates DB + seeds on first load.
// Connection is to the ephemeral MariaDB that run.sh starts on 127.0.0.1:3307 (no password).
// Override with env vars if you point it at your own DB.
$S = getenv('VC_DB_SOCK') ?: '';
$H = getenv('VC_DB_HOST') ?: '127.0.0.1';
$P = getenv('VC_DB_PORT') ?: '3307';
$U = getenv('VC_DB_USER') ?: 'root';
$W = getenv('VC_DB_PASS') ?: '';
$D = getenv('VC_DB_NAME') ?: 'vulncorp';

$dsn = $S ? "mysql:unix_socket=$S" : "mysql:host=$H;port=$P";
$pdo = new PDO($dsn, $U, $W, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$D` CHARACTER SET utf8mb4");
$pdo->exec("USE `$D`");
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(64), password VARCHAR(128),
    salt VARCHAR(16), role VARCHAR(16), email VARCHAR(128), api_key VARCHAR(64), private_note TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT, author VARCHAR(64), body TEXT, created DATETIME)");

$n = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($n == 0) {
    // password is stored SALTED: password column = md5(plaintext . salt) (hashcat -m 10, "hash:salt").
    // The SQLi dump now leaks the hash AND the salt, so cracking needs the per-user salt. The weak
    // passwords still fall from rockyou; admin's 'S3ntinel!Admin' is also leaked via config.php.bak.
    // rows: [username, plaintext, salt, role, email, api_key, private_note]
    $users = [
        ['admin','S3ntinel!Admin','a7Fk2','admin','admin@vulncorp.local','AK-9f2c1a77d4b0','FLAG{sql1_auth_byp4ss_and_1dor}'],
        ['alice','password123','Lm9xQ','user','alice@vulncorp.local','AK-3b7e55','note(base64): RkxBR3tpZG9yX2I2NF9sZWFrZWRfbm90ZX0='],
        ['bob','letmein','Zq4Wp','user','bob@vulncorp.local','AK-6611aa','note(sha1): 64356bcfae350c970263c1ce575185b289f7b836'],
        ['svc_backup','Backup2024!','Rt6Nv','user','backup@vulncorp.local','AK-backup','note(md5 pw+salt): c78b83df83c379fb396bdbc90e1be1c3:Rk2d'],
    ];
    $st = $pdo->prepare("INSERT INTO users(username,password,salt,role,email,api_key,private_note) VALUES (?,?,?,?,?,?,?)");
    foreach ($users as $u) { $st->execute([$u[0], md5($u[1].$u[2]), $u[2], $u[3], $u[4], $u[5], $u[6]]); }
    $pdo->exec("INSERT INTO comments(author,body,created) VALUES
        ('alice','Welcome to the new intranet!', '2026-06-01 09:00:00'),
        ('bob','Where do I file an IT ticket?', '2026-06-02 10:00:00')");
}
return $pdo;
