<?php require __DIR__.'/levels.php';
$L = lvl(); $raw = $_GET['file'] ?? 'readme.txt';
header('Content-Type: text/plain');
if ($L==='expert') {                                 // traversal blocked; php:// wrapper allowed
    if (preg_match('#^php://#i',$raw)) { readfile($raw); }
    else { $p=__DIR__.'/docs/'.str_replace('../','',$raw); echo is_file($p)?file_get_contents($p):"blocked (use a php:// wrapper)\n"; }
} elseif ($L==='hard') {                              // ../ stripped once + require .txt/.conf/.md
    $f = f_trav($raw,$L);
    if (preg_match('/\.(txt|conf|md)$/i',$f)) { $p=__DIR__.'/docs/'.$f; echo is_file($p)?file_get_contents($p):"not found: $f\n"; }
    else { echo "only .txt/.conf/.md allowed\n"; }
} else {                                              // easy raw; medium strips ../ once
    $p = __DIR__.'/docs/'.f_trav($raw,$L);
    if (is_file($p)) { readfile($p); } else { http_response_code(404); echo "not found: $raw\n"; }
}
