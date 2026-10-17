# Certificates & PKI — solution

## 1. What's wrong with `bad-webmail.crt` (4 flaws)
**Command:**
```
openssl x509 -in bad-webmail.crt -noout -text
# or the quick view:
openssl x509 -in bad-webmail.crt -noout -subject -issuer -dates -ext subjectAltName
openssl x509 -in bad-webmail.crt -noout -text | grep -i 'Public-Key'
```
**Result / Answer — four problems:**
1. **Self-signed** — `Issuer` == `Subject` (`CN=webmail.lab.local`). It chains to no CA → not trusted.
2. **Weak key** — `Public-Key: (1024 bit)` RSA. 1024-bit is below the 2048-bit minimum; considered broken.
3. **Expired** — `notAfter = Jan  1 00:00:00 2023 GMT` (already in the past). Outside its validity window.
4. **Over-broad / risky SAN** — `*.portal.lab.local` (wildcard) **and** `IP:10.10.10.25`. A wildcard plus a
   raw IP in the SAN is poor scoping for a single webmail host.

**Reason:** a browser rejects it on *any* of these alone (untrusted self-signed + expired are hard fails).

## 2. Validate the chain
**Command:**
```
openssl verify -CAfile root_ca.pem -untrusted intermediate_ca.pem server.pem
```
**Result:** `server.pem: OK` — the leaf is signed by the intermediate, which is signed by the root.
**Reason:** trust is transitive: root (trust anchor) → intermediate → leaf. `-untrusted` supplies the
intermediate so openssl can build the path; `-CAfile` is the anchor you trust.

## 3. Revocation
**Command:**
```
openssl crl -in intermediate.crl -noout -text | grep -A1 'Serial Number'
openssl x509 -in server.pem  -noout -serial
openssl x509 -in revoked.pem -noout -serial
# enforce the CRL during verification:
openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl server.pem
openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl revoked.pem
```
**Result / Answer:**
- The CRL revokes serial **`4D2BF1BD…477C`** — that is **`revoked.pem`**.
- `server.pem` is serial **`…477B`** → **NOT revoked**: `server.pem: OK`.
- `revoked.pem` → `error 23 ... certificate revoked`.

**Reason:** a cert can be cryptographically valid *and* revoked. CRL (or OCSP) is the issuer saying
"trust this no longer." Always check revocation in addition to building the chain.

## 4. (bonus) SANs / key usage / CRL distribution point
**Command:**
```
openssl x509 -in server.pem -noout -ext subjectAltName,keyUsage,crlDistributionPoints
```
**Result:** SAN = `DNS:portal.labkit.local, DNS:www.labkit.local`; CRL DP =
`URI:http://crl.labkit.local/int.crl` (where a client fetches the CRL). The intermediate carries
`keyUsage = keyCertSign, cRLSign` and `CA:TRUE, pathlen:0` (may only sign leaves, not further CAs).
