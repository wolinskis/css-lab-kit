<?php require __DIR__.'/levels.php';
header('Content-Type: text/plain');
$url = $_GET['url'] ?? '';
if ($url==='') { echo "usage: fetch.php?url=http://example.com  (server-side fetcher)\n"; exit; }
$L = lvl();
if (ssrf_block($url, $L)) { echo "blocked by SSRF filter (level $L)\n"; exit; }
// SSRF: reaches internal services and file:// (filter escalates per level).
$ctx = stream_context_create(['http'=>['timeout'=>4]]);
$data = @file_get_contents($url, false, $ctx);
echo $data === false ? "fetch failed\n" : $data;
