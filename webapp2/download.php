<?php
$file = $_GET['file'] ?? 'readme.txt';
// Path traversal: no sanitisation of the file name.
$path = __DIR__.'/docs/'.$file;
if (is_file($path)) { header('Content-Type: text/plain'); readfile($path); }
else { http_response_code(404); echo "not found: $file\n"; }
