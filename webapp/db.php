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
    role VARCHAR(16), email VARCHAR(128), api_key VARCHAR(64), private_note TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT, author VARCHAR(64), body TEXT, created DATETIME)");

$n = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($n == 0) {
    $users = [
        ['admin','S3ntinel!Admin','admin','admin@vulncorp.local','AK-9f2c1a77d4b0','FLAG{sql1_auth_byp4ss_and_1dor}'],
        ['alice','password123','user','alice@vulncorp.local','AK-3b7e55','Quarterly review notes — nothing secret.'],
        ['bob','letmein','user','bob@vulncorp.local','AK-6611aa','Remember to rotate the VPN cert.'],
        ['svc_backup','Backup2024!','user','backup@vulncorp.local','AK-backup','Cron runs /opt/backup.sh nightly.'],
    ];
    $st = $pdo->prepare("INSERT INTO users(username,password,role,email,api_key,private_note) VALUES (?,?,?,?,?,?)");
    foreach ($users as $u) { $st->execute($u); }
    $pdo->exec("INSERT INTO comments(author,body,created) VALUES
        ('alice','Welcome to the new intranet!', '2026-06-01 09:00:00'),
        ('bob','Where do I file an IT ticket?', '2026-06-02 10:00:00')");
}
return $pdo;
