<?php
require __DIR__.'/levels.php';
header('Content-Type: text/plain');
$xml = file_get_contents('php://input');
$L = lvl();
// Escalating XXE filter (requires php-xml to actually parse):
//  easy   : none                  -> <!ENTITY e SYSTEM "file:///etc/passwd">
//  medium : 'SYSTEM' blocked       -> use PUBLIC: <!ENTITY e PUBLIC "x" "file:///etc/passwd">
//  hard   : + 'file:' blocked      -> use php://filter: ...PUBLIC "x" "php://filter/convert.base64-encode/resource=/etc/passwd"
//  expert : + '<!ENTITY' blocked   -> needs an external/parameter-entity DTD (advanced)
if ($xml) {
    $blk='';
    if ($L!=='easy' && stripos($xml,'SYSTEM')!==false) $blk="SYSTEM keyword blocked (use PUBLIC)";
    if (!$blk && ($L==='hard'||$L==='expert') && stripos($xml,'file:')!==false) $blk="file:// blocked (use php://filter)";
    if (!$blk && $L==='expert' && stripos($xml,'<!ENTITY')!==false) $blk="inline <!ENTITY blocked (use an external/parameter-entity DTD)";
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
