<?php
/*
 * Emulated cloud instance-metadata services for the SSRF lab (self-contained, no real network).
 * fetch.php routes here when the requested URL points at a real metadata address:
 *   AWS   http://169.254.169.254/latest/meta-data/...        (IMDSv1 — no token/header needed)
 *   GCP   http://metadata.google.internal/computeMetadata/v1/...   (requires Metadata-Flavor: Google)
 *   Azure http://169.254.169.254/metadata/instance?api-version=... (requires Metadata: true)
 * The attacker types the SAME URLs they would against a real target; the values are fake lab data.
 * The crown jewel is the AWS IAM temp credentials (the classic SSRF -> cloud-takeover primitive).
 */

/* Normalize a URL host to a dotted-quad IPv4 if it encodes one (decimal / hex / octal / mixed /
 * short form / IPv6-mapped). Returns null if it is not an IPv4 (e.g. a DNS name). Mirrors inet_aton,
 * which is why all the classic SSRF-filter bypasses resolve to the same address. */
function host_canon_ip($host){
  $host = trim($host);
  if($host==='') return null;
  $h = trim($host, '[]');                                  // strip IPv6 brackets
  if(stripos($h,'::ffff:')===0){                            // IPv4-mapped IPv6
    $tail = substr($h,7);
    if(strpos($tail,'.')!==false){ $host=$tail; }           // ::ffff:169.254.169.254
    else {                                                  // ::ffff:a9fe:a9fe  (hex words)
      $w=explode(':',$tail);
      if(count($w)===2 && ctype_xdigit($w[0]) && ctype_xdigit($w[1]))
        return long2ip(((hexdec($w[0])<<16)|hexdec($w[1])) & 0xFFFFFFFF);
      return null;
    }
  }
  if(strpos($host,'.')!==false){
    $parts=explode('.',$host); $n=count($parts);
    if($n<1 || $n>4) return null;
    $vals=[];
    foreach($parts as $p){
      if($p==='') return null;
      if(stripos($p,'0x')===0){ $x=substr($p,2); if($x===''||!ctype_xdigit($x)) return null; $vals[]=hexdec($p); }
      elseif($p[0]==='0' && strlen($p)>1){ if(strspn($p,'01234567')!==strlen($p)) return null; $vals[]=octdec($p); }
      elseif(ctype_digit($p)){ $vals[]=(int)$p; }
      else return null;
    }
    $ip=0;                                                  // inet_aton: last octet fills the rest
    for($i=0;$i<$n-1;$i++){ if($vals[$i]>255) return null; $ip=($ip<<8)|$vals[$i]; }
    $rem=4-($n-1); if($vals[$n-1] > (pow(256,$rem)-1)) return null;
    $ip=($ip<<(8*$rem))|$vals[$n-1];
    return long2ip($ip & 0xFFFFFFFF);
  }
  if(stripos($host,'0x')===0){ $x=substr($host,2); return ($x!==''&&ctype_xdigit($x))?long2ip(hexdec($host)&0xFFFFFFFF):null; }
  if($host[0]==='0' && strlen($host)>1 && strspn($host,'01234567')===strlen($host)) return long2ip(octdec($host)&0xFFFFFFFF);
  if(ctype_digit($host)) return long2ip(((int)$host)&0xFFFFFFFF);
  return null;
}

/* case-insensitive "Name: Value" header check (fetch.php passes an optional &header=) */
function imds_hdr($hdr,$name,$val){
  if($hdr==='') return false;
  foreach(preg_split('/[\r\n]+/',$hdr) as $line){
    $pos=strpos($line,':'); if($pos===false) continue;
    if(strcasecmp(trim(substr($line,0,$pos)),$name)===0 && strcasecmp(trim(substr($line,$pos+1)),$val)===0) return true;
  }
  return false;
}

const IMDS_FLAG = "FLAG{ssrf_reached_the_internal_metadata_service}";

/* Returns the emulated response string if $url is a metadata endpoint we serve, else null. */
function imds_route($url,$hdr){
  $p = @parse_url($url);
  if(!$p || !isset($p['host'])) return null;
  $scheme = strtolower($p['scheme'] ?? '');
  if($scheme!=='http' && $scheme!=='https') return null;
  $host = $p['host']; $path = $p['path'] ?? '/'; $query = $p['query'] ?? '';
  $hl = strtolower($host);

  if($hl==='metadata.google.internal' || $hl==='metadata') return imds_gcp($path,$hdr);

  if(host_canon_ip($host)==='169.254.169.254'){
    if(stripos($path,'/metadata')===0) return imds_azure($path,$query,$hdr);  // Azure
    return imds_aws($path);                                                    // AWS IMDSv1
  }
  return null;
}

/* ---------------- AWS IMDSv1 ---------------- */
function imds_aws($path){
  $path=rtrim($path,'/'); if($path==='') $path='/';
  $role="web01-stg-role";
  $tree=[
    '/'                                        => "latest\n",
    '/latest'                                  => "dynamic\nmeta-data\nuser-data\n",
    '/latest/meta-data'                        => "ami-id\nhostname\niam/\ninstance-id\nlocal-ipv4\nmac\nplacement/\nsecurity-groups\n",
    '/latest/meta-data/ami-id'                 => "ami-0abc1234nexusstg\n",
    '/latest/meta-data/hostname'               => "ip-10-0-7-42.eu-central-1.compute.internal\n",
    '/latest/meta-data/instance-id'            => "i-0a1b2c3d4e5f67890\n",
    '/latest/meta-data/local-ipv4'             => "10.0.7.42\n",
    '/latest/meta-data/security-groups'        => "web-stg-sg\n",
    '/latest/meta-data/iam'                    => "info\nsecurity-credentials/\n",
    '/latest/meta-data/iam/security-credentials'=> $role."\n",
    '/latest/dynamic/instance-identity/document'=>
        "{\n  \"accountId\" : \"334455667788\",\n  \"region\" : \"eu-central-1\",\n".
        "  \"instanceId\" : \"i-0a1b2c3d4e5f67890\",\n  \"instanceType\" : \"t3.small\"\n}\n",
  ];
  if(isset($tree[$path])) return $tree[$path];

  if($path==='/latest/meta-data/iam/security-credentials/'.$role){
    // Temporary IAM credentials — the SSRF crown jewel (would let you call AWS as this instance).
    return
      "{\n".
      "  \"Code\" : \"Success\",\n".
      "  \"LastUpdated\" : \"2026-10-17T09:14:22Z\",\n".
      "  \"Type\" : \"AWS-HMAC\",\n".
      "  \"AccessKeyId\" : \"ASIAZ7NEXUSSTG3F7AQ\",\n".
      "  \"SecretAccessKey\" : \"J8n/0cNexusStg+Kx3Qm9Zr7wP2bVt5uH1aLdE4gF\",\n".
      "  \"Token\" : \"IQoJb3JpZ2luX2VjENX//////////wEaLABfakeSESSIONtokenForLabUseOnly".
                    "0123456789abcdefGHIJKLMNOPqrstuvwxyz+/NexusStg==\",\n".
      "  \"Expiration\" : \"2026-10-17T15:14:22Z\"\n".
      "}\n".
      "\n# ".IMDS_FLAG."  (you reached the instance IAM credentials via SSRF)\n";
  }
  return "404 - Not Found\nTry: /latest/meta-data/  then  /latest/meta-data/iam/security-credentials/\n";
}

/* ---------------- GCP (needs Metadata-Flavor: Google) ---------------- */
function imds_gcp($path,$hdr){
  if(!imds_hdr($hdr,'Metadata-Flavor','Google'))
    return "403 Forbidden — the GCP metadata server requires the request header 'Metadata-Flavor: Google'.\n".
           "A naive SSRF (no custom headers) fails here. If the fetcher lets you set one, add:\n".
           "   &header=Metadata-Flavor:Google\n";
  $path=rtrim($path,'/');
  $tree=[
    '/computeMetadata/v1'          => "instance/\nproject/\n",
    '/computeMetadata/v1/instance' => "hostname\nid\nservice-accounts/\nzone\n",
    '/computeMetadata/v1/instance/hostname' => "nexus-stg-01.c.nexus-lab.internal\n",
    '/computeMetadata/v1/instance/service-accounts' => "default/\nnexus-stg@nexus-lab.iam.gserviceaccount.com/\n",
    '/computeMetadata/v1/instance/service-accounts/default' => "aliases\nemail\nscopes\ntoken\n",
    '/computeMetadata/v1/instance/service-accounts/default/email' => "nexus-stg@nexus-lab.iam.gserviceaccount.com\n",
  ];
  if(isset($tree[$path])) return $tree[$path];
  if($path==='/computeMetadata/v1/instance/service-accounts/default/token')
    return "{\"access_token\":\"ya29.A0ARfakeGcpOauthTokenForLabUseOnly_NexusStg_0123456789\",".
           "\"expires_in\":3599,\"token_type\":\"Bearer\"}\n".
           "\n# ".IMDS_FLAG."  (you reached the GCP service-account OAuth token via SSRF)\n";
  return "404 - Not Found\nTry: /computeMetadata/v1/instance/service-accounts/default/token\n";
}

/* ---------------- Azure (needs Metadata: true + api-version) ---------------- */
function imds_azure($path,$query,$hdr){
  if(!imds_hdr($hdr,'Metadata','true'))
    return "400 - Required metadata header not specified (Azure IMDS requires 'Metadata: true').\n".
           "If the fetcher lets you set a header, add:  &header=Metadata:true\n";
  if(stripos($query,'api-version=')===false)
    return "400 - the api-version query parameter is required (e.g. ?api-version=2021-02-01).\n";
  $path=rtrim($path,'/');
  if($path==='/metadata/instance')
    return "{\n  \"compute\" : {\n    \"name\" : \"nexus-stg-01\",\n    \"location\" : \"westeurope\",\n".
           "    \"resourceGroupName\" : \"nexus-stg-rg\",\n    \"subscriptionId\" : \"d4e3-stg-fake\",\n".
           "    \"vmId\" : \"8f3a1c2b-stg-lab\"\n  }\n}\n";
  if($path==='/metadata/identity/oauth2/token')
    return "{\"access_token\":\"eyJ0eXAiOiFakeAzureMSITokenForLabUseOnly.NexusStg.0123456789\",".
           "\"token_type\":\"Bearer\",\"resource\":\"https://management.azure.com/\"}\n".
           "\n# ".IMDS_FLAG."  (you reached the Azure managed-identity token via SSRF)\n";
  return "404 - Not Found\nTry: /metadata/instance?api-version=2021-02-01  then  /metadata/identity/oauth2/token?api-version=2018-02-01&resource=https://management.azure.com/\n";
}
