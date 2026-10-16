<?php require __DIR__.'/levels.php';
$L = lvl(); $raw = $_GET['file'] ?? 'readme.txt';
header('Content-Type: text/plain');
if ($L==='hard' || $L==='expert') {                  // '../' stripped recursively; php:// wrappers work
    if (preg_match('#^php://#i',$raw)) {
        if ($L==='expert' && stripos($raw,'base64')!==false) { echo "the base64 filter is blocked — use another converter (e.g. string.rot13)\n"; }
        else { readfile($raw); }
    } else {
        $p=__DIR__.'/docs/'.f_trav($raw,$L); echo is_file($p)?file_get_contents($p):"not found (traversal stripped — use a php:// wrapper)\n";
    }
} elseif ($L==='medium') {                            // ../ stripped once -> ....//
    $p = __DIR__.'/docs/'.f_trav($raw,$L);
    if (is_file($p)) { readfile($p); } else { http_response_code(404); echo "not found\n"; }
} else {                                              // easy: raw
    $p = __DIR__.'/docs/'.$raw;
    if (is_file($p)) { readfile($p); } else { http_response_code(404); echo "not found: $raw\n"; }
}
