<?php
header('Content-Type: text/plain');
$url = $_GET['url'] ?? '';
if ($url==='') { echo "usage: fetch.php?url=http://example.com  (server-side fetcher)\n"; exit; }
// SSRF: no scheme/host allow-list — reaches internal services and file:// .
$ctx = stream_context_create(['http'=>['timeout'=>4]]);
$data = @file_get_contents($url, false, $ctx);
echo $data === false ? "fetch failed\n" : $data;
