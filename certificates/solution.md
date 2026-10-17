# Certificates & PKI — solution

## 🟢 Easy — what's wrong with `bad-webmail.crt` (4 flaws)
**Command:**
```
openssl x509 -in bad-webmail.crt -noout -subject -issuer -dates -ext subjectAltName
openssl x509 -in bad-webmail.crt -noout -text | grep -i 'Public-Key'
```
**Answer — four problems:**
1. **Self-signed** — `Issuer` == `Subject` (`CN=webmail.lab.local`). Chains to no CA → untrusted.
2. **Weak key** — `Public-Key: (1024 bit)` RSA, below the 2048-bit minimum (considered broken).
3. **Expired** — `notAfter = Jan 1 00:00:00 2023 GMT` (in the past).
4. **Over-broad / risky SAN** — `*.portal.lab.local` (wildcard) **and** `IP:10.10.10.25` for a single host.

**Reason:** a browser hard-fails on *any* of self-signed / expired alone.

## 🟡 Medium — validate the chain
**Command:**
```
openssl verify -CAfile root_ca.pem -untrusted intermediate_ca.pem server.pem
```
**Result:** `server.pem: OK`. **Reason:** trust is transitive — root (anchor) → intermediate → leaf.
`-untrusted` supplies the intermediate so openssl can build the path; `-CAfile` is the anchor you trust.

## 🟠 Hard — revocation
**Command:**
```
openssl crl -in intermediate.crl -noout -text | grep -A1 'Serial Number'
openssl x509 -in server.pem  -noout -serial
openssl x509 -in revoked.pem -noout -serial
openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl server.pem
openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl revoked.pem
```
**Answer:**
- The CRL revokes serial **`…477C`** — that is **`revoked.pem`** → `error 23 … certificate revoked`.
- `server.pem` is serial **`…477B`** → **NOT revoked**: `server.pem: OK`.

**Reason:** a cert can be cryptographically valid *and* revoked; always check the CRL/OCSP in addition to
building the chain.

## 🔴 Expert — constraints, fingerprint, hostname matching
**Command:**
```
openssl x509 -in intermediate_ca.pem -noout -ext basicConstraints,keyUsage
openssl x509 -in server.pem -noout -fingerprint -sha256
openssl x509 -in bad-webmail.crt -noout -ext subjectAltName
```
**Answers:**
1. Intermediate: `Basic Constraints: CA:TRUE, pathlen:0` + `Key Usage: Certificate Sign, CRL Sign`.
   **`pathlen:0`** means it may sign **leaf** certs only — **zero** further CAs beneath it; and `keyUsage`
   limits it to signing certs/CRLs (not TLS itself).
2. `SHA256 Fingerprint = 9C:38:C9:1B:…:78` — the value you'd **pin** or compare out-of-band to detect a
   swapped/MITM cert (your regenerated copy will differ; it's a hash of that exact cert).
3. **No** — `bad-webmail.crt` is **not** valid for `portal.lab.local`. Its SANs are
   `webmail.lab.local`, `mail.lab.local`, `*.portal.lab.local`, `IP:10.10.10.25`. A wildcard matches
   exactly **one** left-most label, so `*.portal.lab.local` covers `a.portal.lab.local` but **not**
   `portal.lab.local` itself — and the CN is ignored for matching (SAN only). None match.
