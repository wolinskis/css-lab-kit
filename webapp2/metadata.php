<?php
// "Internal" instance-metadata service — mimics the cloud metadata endpoint (AWS/GCP/Azure
// http://169.254.169.254/). It is meant to be reachable ONLY from the server itself, never from the
// internet — which is exactly why reaching it through the fetch.php SSRF matters: it hands out the
// instance's identity AND its credentials. (Values below are fake lab data.)
header('Content-Type: text/plain');
echo "instance-id: i-nexus-stg-01\n";
echo "iam-role: web01-stg\n";
echo "access-key-id: AKIA_NEXUS_STG_7F3A\n";
echo "secret-access-key: s3cr3t/NexusStg+k3yM4teri4l\n";
echo "FLAG{ssrf_reached_the_internal_metadata_service}\n";
