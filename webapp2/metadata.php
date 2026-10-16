<?php
// "Internal" metadata service — intended to be reachable only server-side (via fetch.php SSRF).
header('Content-Type: text/plain');
echo "instance-id: i-nexus-stg-01\nrole: web01-stg\nFLAG{ssrf_reached_the_internal_metadata_service}\n";
