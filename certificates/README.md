# Certificates & PKI — inspect, validate, revoke

A self-contained **offline** lab: no server, no internet — just `openssl` against the files here. Four
escalating levels; work each yourself first, then check `solution.md`.

**Files**
| File | What it is |
|---|---|
| `bad-webmail.crt` | a cert pulled from an internal webmail host — several things are wrong with it |
| `root_ca.pem` | the LabKit **Root CA** (trust anchor) |
| `intermediate_ca.pem` | the LabKit **Issuing CA**, signed by the root |
| `server.pem` | a server **leaf** (`portal.labkit.local`), issued by the intermediate |
| `chain.pem` | `server.pem` + `intermediate_ca.pem` + `root_ca.pem` concatenated |
| `revoked.pem` | another leaf from the same intermediate |
| `intermediate.crl` | the intermediate CA's **Certificate Revocation List** |

*(No private keys are included — none are needed to inspect, validate or check revocation.)*

| Level | Goal |
|---|---|
| 🟢 **Easy** | inspect `bad-webmail.crt` and name every flaw (max 4) |
| 🟡 **Medium** | validate that `server.pem` chains to the root |
| 🟠 **Hard** | check the CRL — which cert is revoked? is `server.pem`? |
| 🔴 **Expert** | CA constraints, leaf fingerprint, and a hostname-matching gotcha |

---
## 🟢 Easy — assess the bad certificate
Inspect `bad-webmail.crt` and decide whether a browser would trust it, and why not (there are 4 problems).
<details><summary>💡 hint</summary>

`openssl x509 -in bad-webmail.crt -noout -text` — compare **Issuer** vs **Subject**, read the
**Public-Key** bits, the **Not Before/Not After** dates, and the **Subject Alternative Name**.
</details>

## 🟡 Medium — validate the chain
Prove `server.pem` is trusted *through* the intermediate up to the root.
<details><summary>💡 hint</summary>

`openssl verify` takes the trusted root with `-CAfile` and any intermediates with `-untrusted`.
</details>

## 🟠 Hard — check revocation
Using `intermediate.crl`: which certificate's serial is revoked, and is `server.pem` among them? Then
re-run validation with the CRL **enforced**.
<details><summary>💡 hint</summary>

`openssl crl -in intermediate.crl -noout -text` lists revoked serials; compare against
`openssl x509 -in server.pem -noout -serial` and `... -in revoked.pem -serial`. Enforce with
`openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl <cert>`.
</details>

## 🔴 Expert — constraints, fingerprint, hostname matching
1. Read the intermediate's **Basic Constraints** and **Key Usage** — why can't it issue another sub-CA?
2. Produce the **SHA-256 fingerprint** of `server.pem` (what you'd pin / compare out-of-band).
3. Would `bad-webmail.crt` be valid for the hostname **`portal.lab.local`**? Look carefully at its SANs.
<details><summary>💡 hint</summary>

`-ext basicConstraints,keyUsage` on the intermediate; `-fingerprint -sha256` on the leaf. For (3):
a wildcard like `*.portal.lab.local` matches **one** left-most label (`a.portal.lab.local`) — **not**
`portal.lab.local` itself, and CNs are ignored for matching (SAN only).
</details>

---
> ⚠️ Training only — synthetic certs for a lab. The CA keys are throwaway; never use any of this in production.
