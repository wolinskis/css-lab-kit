<?php
// Reset VulnNexus to a clean state: drop the DB; db.php re-seeds it.
$S=getenv('VN_DB_SOCK')?:'';
$dsn=$S?"mysql:unix_socket=$S":"mysql:host=127.0.0.1;port=".(getenv('VN_DB_PORT')?:'3308');
try { $p=new PDO($dsn,getenv('VN_DB_USER')?:'root',getenv('VN_DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
      $p->exec("DROP DATABASE IF EXISTS vulnnexus"); } catch(Exception $e){}
require __DIR__.'/db.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Location: index.php?reset=1');
