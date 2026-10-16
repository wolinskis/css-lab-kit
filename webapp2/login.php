<?php require __DIR__.'/jwt.php';
header('Content-Type: text/plain');
$USERS = ['alice'=>'alice123','bob'=>'bob123'];   // low-priv accounts; admin's password is NOT here
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $u=$_POST['username']??''; $p=$_POST['password']??'';
    if (isset($USERS[$u]) && hash_equals($USERS[$u],$p)) {
        $tok = jwt_make(['user'=>$u,'role'=>'user','iat'=>time()]);
        setcookie('session',$tok);
        echo "login OK\nrole=user\nsession=$tok\n(decode it at jwt.io — then try to become admin)\n";
    } else { http_response_code(401); echo "invalid credentials\n"; }
} else { echo "POST username & password (e.g. alice/alice123)\n"; }
