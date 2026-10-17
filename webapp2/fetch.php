<?php require __DIR__.'/levels.php'; require __DIR__.'/imds.php';
// Server-side URL fetcher (like a link/image-preview proxy). The bug: no allow-list, so you can aim
// it at resources only the SERVER can reach — the cloud instance-metadata service (AWS/GCP/Azure) or
// local files. Intended use is an external URL. The SSRF filter escalates per level (see levels.php).
header('Content-Type: text/plain');
$url = $_GET['url'] ?? '';
if ($url==='') {
  echo "usage: fetch.php?url=<URL>            — the server fetches <URL> for you\n";
  echo "       &header=Name:Value             — optional request header the server will add\n\n";
  echo "SSRF targets the server can reach but you can't:\n";
  echo "  AWS   http://169.254.169.254/latest/meta-data/iam/security-credentials/\n";
  echo "  GCP   http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token  (&header=Metadata-Flavor:Google)\n";
  echo "  Azure http://169.254.169.254/metadata/instance?api-version=2021-02-01  (&header=Metadata:true)\n";
  echo "  file  file:///etc/passwd\n";
  exit;
}
$L = lvl();
if (ssrf_block($url, $L)) { echo "blocked by SSRF filter (level $L)\n"; exit; }

// A real fetcher can be told to add headers; cloud GCP/Azure metadata require one.
$hdr = $_GET['header'] ?? '';

// 1) cloud instance-metadata (emulated, offline) — the SSRF crown jewel
$meta = imds_route($url, $hdr);
if ($meta !== null) { echo $meta; exit; }

// 2) file:// — SSRF becomes a local file read on the host
if (preg_match('#^\s*file://#i', $url)) {
  $path = preg_replace('#^\s*file://#i', '', $url);
  $data = @file_get_contents($path);
  echo $data === false ? "fetch failed (no such file / not readable)\n" : $data;
  exit;
}

// 3) otherwise a normal outbound request (external host — no outbound network in the lab)
$opts = ['http'=>['timeout'=>3]];
if ($hdr !== '') $opts['http']['header'] = $hdr;
$ctx = stream_context_create($opts);
$data = @file_get_contents($url, false, $ctx);
echo $data === false ? "fetch failed (external network is not reachable in the lab)\n" : $data;
