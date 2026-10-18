<?php
/*
 * Lab Kit router for the PHP built-in server.
 * Lets each web app ALSO serve the kit landing + static sibling labs on its own port, so the
 * "🏁 Lab Kit" nav link works from inside VulnCorp / VulnNexus. run.sh uses it:
 *     php -S 0.0.0.0:<port> ../labkit_router.php
 * Any path that is not a kit path returns false -> the built-in server serves the app's own file
 * from its docroot (webapp/ or webapp2/) exactly as before.
 */

// Guarded so re-including the router on each request (php -S) never redeclares these.
if (!function_exists('md_page')) {
    // ---- minimal, self-contained Markdown -> HTML (no dependencies, works offline) ----
    function md_inline($s) {
        $spans = [];
        $s = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$spans) {
            $i = count($spans); $spans[$i] = '<code>' . htmlspecialchars($m[1], ENT_QUOTES) . '</code>';
            return "\x01C$i\x01";
        }, $s);
        $s = htmlspecialchars($s, ENT_QUOTES);
        $s = preg_replace('/!\[([^\]]*)\]\(([^)\s]+)\)/', '<img alt="$1" src="$2">', $s);
        $s = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/', '<a href="$2">$1</a>', $s);
        $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<!\*)\*([^*\s][^*]*)\*(?!\*)/', '<em>$1</em>', $s);
        $s = preg_replace_callback('/\x01C(\d+)\x01/', function ($m) use ($spans) { return $spans[$m[1]]; }, $s);
        return $s;
    }
    function md_to_html($md) {
        $md = str_replace("\r\n", "\n", $md);
        $codes = [];
        $md = preg_replace_callback('/```[^\n]*\n(.*?)\n?```/s', function ($m) use (&$codes) {
            $i = count($codes); $codes[$i] = '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES) . '</code></pre>';
            return "\x02CODE$i\x02";
        }, $md);
        $lines = explode("\n", $md); $n = count($lines); $i = 0; $out = [];
        $ul = false; $ol = false;
        $close = function () use (&$out, &$ul, &$ol) {
            if ($ul) { $out[] = '</ul>'; $ul = false; }
            if ($ol) { $out[] = '</ol>'; $ol = false; }
        };
        while ($i < $n) {
            $t = rtrim($lines[$i]);
            if (preg_match('/^\x02CODE\d+\x02$/', $t))         { $close(); $out[] = $t; $i++; continue; }
            if (trim($t) === '')                                { $close(); $i++; continue; }
            if (preg_match('/^\s*([-*_])(\s*\1){2,}\s*$/', $t)) { $close(); $out[] = '<hr>'; $i++; continue; }
            if (preg_match('/^(#{1,6})\s+(.*)$/', $t, $m))      { $close(); $lv = strlen($m[1]); $out[] = "<h$lv>" . md_inline(rtrim($m[2], ' #')) . "</h$lv>"; $i++; continue; }
            // GFM table: a pipe row followed by a |---|---| separator
            if (strpos($t, '|') !== false && isset($lines[$i + 1])
                && preg_match('/^\s*\|?[\s:\-|]*-[\s:\-|]*$/', $lines[$i + 1]) && strpos($lines[$i + 1], '-') !== false) {
                $close();
                $cells = function ($r) { return array_map('trim', explode('|', trim(rtrim($r), " \t|"))); };
                $head = $cells($t); $i += 2; $tb = '<table><thead><tr>';
                foreach ($head as $h) $tb .= '<th>' . md_inline($h) . '</th>';
                $tb .= '</tr></thead><tbody>';
                while ($i < $n && strpos($lines[$i], '|') !== false && trim($lines[$i]) !== '') {
                    $tb .= '<tr>'; foreach ($cells($lines[$i]) as $c) $tb .= '<td>' . md_inline($c) . '</td>'; $tb .= '</tr>'; $i++;
                }
                $out[] = $tb . '</tbody></table>'; continue;
            }
            if (preg_match('/^\s*>\s?(.*)$/', $t)) {
                $close(); $buf = [];
                while ($i < $n && preg_match('/^\s*>\s?(.*)$/', rtrim($lines[$i]), $mm)) { $buf[] = $mm[1]; $i++; }
                $out[] = '<blockquote>' . md_inline(implode(' ', $buf)) . '</blockquote>'; continue;
            }
            if (preg_match('/^\s*[-*+]\s+(.*)$/', $t, $m)) {
                if ($ol) { $out[] = '</ol>'; $ol = false; } if (!$ul) { $out[] = '<ul>'; $ul = true; }
                $out[] = '<li>' . md_inline($m[1]) . '</li>'; $i++; continue;
            }
            if (preg_match('/^\s*\d+[.)]\s+(.*)$/', $t, $m)) {
                if ($ul) { $out[] = '</ul>'; $ul = false; } if (!$ol) { $out[] = '<ol>'; $ol = true; }
                $out[] = '<li>' . md_inline($m[1]) . '</li>'; $i++; continue;
            }
            $close(); $buf = [$t]; $i++;
            while ($i < $n) {
                $nx = rtrim($lines[$i]);
                if (trim($nx) === '') break;
                if (preg_match('/^(#{1,6})\s|^\s*[-*+]\s|^\s*\d+[.)]\s|^\s*>|^\x02CODE\d+\x02$/', $nx)) break;
                if (preg_match('/^\s*([-*_])(\s*\1){2,}\s*$/', $nx)) break;
                // stop before a GFM table (pipe row + |---| separator) that follows a text line
                if (strpos($nx, '|') !== false && isset($lines[$i + 1])
                    && preg_match('/^\s*\|?[\s:\-|]*-[\s:\-|]*$/', $lines[$i + 1]) && strpos($lines[$i + 1], '-') !== false) break;
                $buf[] = $nx; $i++;
            }
            $out[] = '<p>' . md_inline(implode(' ', $buf)) . '</p>';
        }
        $close();
        $html = implode("\n", $out);
        return preg_replace_callback('/\x02CODE(\d+)\x02/', function ($m) use ($codes) { return $codes[$m[1]]; }, $html);
    }
    function md_page($title, $body) {
        $t = htmlspecialchars($title, ENT_QUOTES);
        return "<!doctype html><html lang=en><head><meta charset=utf-8>"
          . "<meta name=viewport content='width=device-width,initial-scale=1'><title>$t · CSS Lab Kit</title><style>"
          . ":root{--bg:#0e1016;--card:#171a22;--ink:#e6edf3;--muted:#9aa4b2;--line:#2a2f3a;--brand:#9a6bff;--brand2:#58a6ff}"
          . "*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.65 system-ui,Segoe UI,Arial,sans-serif}"
          . "a{color:var(--brand2)}header.top{position:sticky;top:0;background:#12151dcc;backdrop-filter:blur(6px);border-bottom:1px solid var(--line);padding:10px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}"
          . "header.top .brand{font-weight:700;color:var(--brand)}main{max-width:860px;margin:0 auto;padding:26px 20px 70px}"
          . "h1,h2,h3,h4,h5{color:#c7b3ff;line-height:1.3;margin:1.4em 0 .5em}h1{margin-top:.2em;border-bottom:1px solid var(--line);padding-bottom:.3em}h2{border-bottom:1px solid var(--line);padding-bottom:.25em}"
          . "code{background:#20242e;border:1px solid var(--line);border-radius:5px;padding:.12em .4em;font:.9em ui-monospace,Consolas,monospace}"
          . "pre{background:#0b0e14;border:1px solid var(--line);border-radius:10px;padding:14px 16px;overflow:auto}pre code{background:none;border:0;padding:0}"
          . "blockquote{margin:1em 0;padding:.4em 1em;border-left:3px solid var(--brand);background:#161922;color:var(--muted)}"
          . "table{border-collapse:collapse;width:100%;margin:1em 0;font-size:.95em}th,td{border:1px solid var(--line);padding:7px 10px;text-align:left;vertical-align:top}th{background:#20242e}"
          . "hr{border:0;border-top:1px solid var(--line);margin:1.6em 0}ul,ol{padding-left:1.4em}li{margin:.25em 0}img{max-width:100%}"
          . "</style></head><body><header class=top><span class=brand>🏁 CSS Lab Kit</span>"
          . "<a href='/index.html'>Home</a><a href='javascript:history.back()'>← Back</a><a href='?raw=1'>Raw .md</a>"
          . "</header><main>$body</main></body></html>";
    }
}

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
    $docroot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $fs = $docroot . $uri;
    // App-docroot markdown (e.g. webapp/SOLUTIONS.md) renders styled too. realpath-guarded to
    // the docroot so this convenience never becomes a traversal in the router itself.
    if (!isset($_GET['raw']) && strtolower(pathinfo($fs, PATHINFO_EXTENSION)) === 'md') {
        $rp = realpath($fs);
        if ($rp !== false && strncmp($rp, realpath($docroot) . DIRECTORY_SEPARATOR, strlen(realpath($docroot)) + 1) === 0 && is_file($rp)) {
            $src = file_get_contents($rp);
            $title = basename($rp);
            if (preg_match('/^\s*#\s+(.+)$/m', $src, $mm)) $title = trim($mm[1]);
            header('Content-Type: text/html; charset=utf-8');
            echo md_page($title, md_to_html($src));
            return true;
        }
    }
    if ($uri === '/' || is_file($fs) || is_dir($fs)) return false;    // real app file/dir -> serve as normal
    http_response_code(404); header('Content-Type: text/plain'); echo "404 Not Found: $uri\n"; return true;
}

$target = realpath($root . $uri);
if ($target !== false && is_dir($target) && is_file($target . DIRECTORY_SEPARATOR . 'index.html')) {
    $target = $target . DIRECTORY_SEPARATOR . 'index.html';   // directory index: /pcap/ -> /pcap/index.html
}
if ($target === false
    || strncmp($target, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0   // stay inside the kit root
    || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Not found in Lab Kit: $uri\n";
    return true;
}
$ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

// Markdown docs (README / solution / SOLUTIONS / START_HERE) render as a styled
// page instead of raw text. Append ?raw=1 to download/read the plain source.
if ($ext === 'md' && !isset($_GET['raw'])) {
    $src   = file_get_contents($target);
    $title = basename($target);
    if (preg_match('/^\s*#\s+(.+)$/m', $src, $mm)) $title = trim($mm[1]);
    header('Content-Type: text/html; charset=utf-8');
    echo md_page($title, md_to_html($src));
    return true;
}

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
