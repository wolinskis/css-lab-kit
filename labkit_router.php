<?php
/*
 * Lab Kit router for the PHP built-in server.
 * Lets each web app ALSO serve the kit landing + static sibling labs on its own port, so the
 * "🏁 Lab Kit" nav link works from inside VulnCorp / VulnNexus. run.sh uses it:
 *     php -S 0.0.0.0:<port> ../labkit_router.php
 * Any path that is not a kit path returns false -> the built-in server serves the app's own file
 * from its docroot (webapp/ or webapp2/) exactly as before.
 */
$root = __DIR__;                                    // the Lab Kit root (this file lives here)
$uri  = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri === '/labkit' || $uri === '/labkit/') $uri = '/index.html';   // friendly alias

$files = ['/index.html', '/progress.html', '/README.md', '/START_HERE.md', '/LICENSE'];
$dirs  = ['/certificates/', '/pcap/', '/assets/', '/tls_decrypt_lab/', '/privesc/'];

$isKit = in_array($uri, $files, true);
foreach ($dirs as $d) { if (strncmp($uri, $d, strlen($d)) === 0) { $isKit = true; break; } }
if (!$isKit) {
    // Not a Lab-Kit path: let the built-in server serve/execute the app's own file IF it exists.
    // But a plain `return false` makes php -S fall back to the docroot index.php for a MISSING path,
    // which rendered the hub ("main page") inside result iframes (e.g. an un-uploaded webshell). 404 instead.
    $fs = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $uri;
    if ($uri === '/' || is_file($fs) || is_dir($fs)) return false;    // real app file/dir -> serve as normal
    http_response_code(404); header('Content-Type: text/plain'); echo "404 Not Found: $uri\n"; return true;
}

$target = realpath($root . $uri);
if ($target === false
    || strncmp($target, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0   // stay inside the kit root
    || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Not found in Lab Kit: $uri\n";
    return true;
}
$ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
$ct  = [
    'html' => 'text/html; charset=utf-8', 'css' => 'text/css', 'js' => 'application/javascript',
    'png'  => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'gif' => 'image/gif',
    'md'   => 'text/plain; charset=utf-8', 'txt' => 'text/plain; charset=utf-8',
    'pcap' => 'application/vnd.tcpdump.pcap', 'crt' => 'application/x-x509-ca-cert',
    'pem'  => 'text/plain; charset=utf-8', 'crl' => 'application/pkix-crl',
];
header('Content-Type: ' . ($ct[$ext] ?? 'application/octet-stream'));
readfile($target);
return true;
