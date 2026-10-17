<?php
require __DIR__.'/levels.php';
header('Content-Type: text/plain');
$xml = file_get_contents('php://input');
$L = lvl();
// This lab PARSES XML, which needs the php-xml (dom) extension. Fail loudly if it is missing
// (otherwise new DOMDocument() fatals and the client just sees a blank response).
if (!class_exists('DOMDocument')) {
    echo "import.php needs the php-xml extension (DOMDocument) to parse XML — it is NOT installed here.\n";
    echo "install it once:  sudo apt install -y php-xml   then restart the app (webapp2/run.sh).\n";
    echo "run.sh already prints a preflight warning when php-xml is absent.\n";
    exit;
}
// Escalating XXE filter — every level stays a LOCAL file read (self-contained, no out-of-band needed):
//  easy   : none                 -> <!ENTITY e SYSTEM "file:///etc/passwd">
//  medium : 'SYSTEM' blocked      -> use PUBLIC: <!ENTITY e PUBLIC "x" "file:///etc/passwd">
//  hard   : + 'file:' blocked     -> php://filter: PUBLIC "x" "php://filter/convert.base64-encode/resource=/etc/passwd"
//  expert : + 'base64' blocked    -> a different filter: PUBLIC "x" "php://filter/read=string.rot13/resource=/etc/passwd"
if ($xml) {
    $blk='';
    if ($L!=='easy' && stripos($xml,'SYSTEM')!==false) $blk="SYSTEM keyword blocked (use PUBLIC)";
    if (!$blk && ($L==='hard'||$L==='expert') && stripos($xml,'file:')!==false) $blk="file:// blocked (use php://filter)";
    if (!$blk && $L==='expert' && stripos($xml,'base64')!==false) $blk="base64 filter blocked (use another php filter, e.g. string.rot13)";
    if ($blk) { echo "blocked (level $L): $blk\n"; exit; }
}
if (!$xml) {
    echo "POST an XML order (Content-Type: application/xml), e.g.:\n";
    echo "<order><item>Nexus Router X1</item></order>\n";
    exit;
}
libxml_use_internal_errors(true);
// XXE: external entities resolved (LIBXML_NOENT) and DTD loaded (LIBXML_DTDLOAD).
$dom = new DOMDocument();
if (!$dom->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD)) { echo "invalid XML\n"; exit; }
echo "Imported order:\n";
foreach ($dom->getElementsByTagName('item') as $n) echo "- ".$n->textContent."\n";
