#!/usr/bin/env bash
# Generates certificate artifacts for the Certificates & PKI labs.
set -e; cd "$(dirname "$0")/.."; OUT="artifacts/certs"; mkdir -p "$OUT"; cd "$OUT"
echo "[*] certs -> $PWD"

# 1) A deliberately BAD cert: self-signed, 1024-bit RSA, EXPIRED, SAN mismatch (cert-analysis lab)
openssl req -x509 -newkey rsa:1024 -nodes -keyout bad-webmail.key -out bad-webmail.crt \
  -days 1 -not_before 20220101000000Z -not_after 20230101000000Z \
  -subj "/C=CH/ST=Zurich/L=Zurich/O=Lab Widgets AG/OU=IT/CN=webmail.lab.local" \
  -addext "subjectAltName=DNS:webmail.lab.local,DNS:mail.lab.local,DNS:*.portal.lab.local,IP:10.10.10.25" 2>/dev/null
# (notBefore/notAfter flags need openssl 3.2+; if unsupported the -days 1 already makes it expire — regenerate dates below)

# 2) A valid chain: Root CA -> Intermediate CA -> server leaf  (chain / CRL labs)
openssl req -x509 -newkey rsa:4096 -nodes -keyout root_ca.key -out root_ca.pem -days 3650 \
  -subj "/C=CH/O=LabKit/CN=LabKit Root CA" 2>/dev/null
openssl req -newkey rsa:2048 -nodes -keyout intermediate_ca.key -out intermediate_ca.csr \
  -subj "/C=CH/O=LabKit/CN=LabKit Issuing CA" 2>/dev/null
openssl x509 -req -in intermediate_ca.csr -CA root_ca.pem -CAkey root_ca.key -CAcreateserial \
  -out intermediate_ca.pem -days 1825 -extfile <(printf "basicConstraints=critical,CA:TRUE,pathlen:0\nkeyUsage=critical,keyCertSign,cRLSign\ncrlDistributionPoints=URI:http://crl.labkit.local/int.crl") 2>/dev/null
openssl req -newkey rsa:2048 -nodes -keyout server.key -out server.csr \
  -subj "/C=CH/O=LabKit/CN=portal.labkit.local" 2>/dev/null
openssl x509 -req -in server.csr -CA intermediate_ca.pem -CAkey intermediate_ca.key -CAcreateserial \
  -out server.pem -days 365 -extfile <(printf "subjectAltName=DNS:portal.labkit.local,DNS:www.labkit.local\ncrlDistributionPoints=URI:http://crl.labkit.local/int.crl") 2>/dev/null
cat server.pem intermediate_ca.pem root_ca.pem > chain.pem

# 3) A CRL from the intermediate that REVOKES a throwaway cert (so 'server.pem' is NOT revoked)
openssl req -newkey rsa:2048 -nodes -keyout revoked.key -out revoked.csr -subj "/CN=revoked.labkit.local" 2>/dev/null
openssl x509 -req -in revoked.csr -CA intermediate_ca.pem -CAkey intermediate_ca.key -CAcreateserial -out revoked.pem -days 365 2>/dev/null
touch index.txt; echo 01 > crlnumber
cat > crl.cnf <<CNF
[ca]
default_ca=CA
[CA]
database=index.txt
crlnumber=crlnumber
default_crl_days=30
default_md=sha256
CNF
openssl ca -config crl.cnf -cert intermediate_ca.pem -keyfile intermediate_ca.key -revoke revoked.pem 2>/dev/null || true
openssl ca -config crl.cnf -cert intermediate_ca.pem -keyfile intermediate_ca.key -gencrl -out intermediate.crl 2>/dev/null || true

rm -f *.csr *.srl crlnumber* index.txt* crl.cnf 2>/dev/null
echo "[*] cert artifacts: $(ls | tr '\n' ' ')"
