<?php
header('Content-Type: text/plain');
$xml = file_get_contents('php://input');
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
