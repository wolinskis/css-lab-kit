<?php
// Reset the lab to a clean state: drop the DB (db.php re-seeds it) and clear uploads.
$S=getenv('VC_DB_SOCK')?:'';
$dsn=$S?"mysql:unix_socket=$S":"mysql:host=".(getenv('VC_DB_HOST')?:'127.0.0.1').";port=".(getenv('VC_DB_PORT')?:'3307');
try { $p=new PDO($dsn,getenv('VC_DB_USER')?:'root',getenv('VC_DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
      $p->exec("DROP DATABASE IF EXISTS vulncorp"); } catch(Exception $e){}
foreach (glob(__DIR__.'/uploads/*') as $f){ if(basename($f)!=='.gitkeep') @unlink($f); }
require __DIR__.'/db.php';            // recreate + reseed
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Location: home.php?reset=1');
