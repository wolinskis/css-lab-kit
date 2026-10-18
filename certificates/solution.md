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

## 🟡 Medium — validate the chain (see it FAIL first, then fix it)
**Commands — run in order so you see *why* it ends in `OK`:**

```
openssl verify server.pem                                                     # 1. FAILS: error 20 (no issuer at all)
openssl verify -CAfile root_ca.pem server.pem                                 # 2. STILL FAILS: error 20 (root didn't sign the leaf — the intermediate did)
openssl verify -CAfile root_ca.pem -untrusted intermediate_ca.pem server.pem  # 3. OK: -untrusted supplies the missing intermediate
```

**Result:** steps 1–2 → `error 20 ... unable to get local issuer certificate` → `verification failed`; step 3 → `server.pem: OK`.

**See the chain you are building (subject ← issuer):**

```
openssl x509 -in server.pem          -noout -subject -issuer
openssl x509 -in intermediate_ca.pem -noout -subject -issuer
openssl x509 -in root_ca.pem         -noout -subject -issuer
```

→ `server.pem` (CN=portal.labkit.local) is issued by **LabKit Issuing CA** (= `intermediate_ca.pem`), issued by **LabKit Root CA** (= `root_ca.pem`, the self-signed anchor).

**Reason / where the problem is:** Medium is **not** a flaw-hunt — the deliverable is a *valid* chain (`OK`). The lesson is the failure in steps 1–2: `error 20 — unable to get local issuer certificate` is what you get when the **intermediate is missing** — one of the most common real-world TLS misconfigurations (a server presenting only its leaf cert). `-untrusted` supplies the missing intermediate so openssl can build leaf → intermediate → root; `-CAfile` is the anchor you trust. In production the fix is to serve the full bundle (`chain.pem`).

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
