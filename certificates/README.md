# Certificates & PKI — inspection, chain validation, revocation

A self-contained **offline** lab: no server, no internet — just `openssl` against the files here.
You're handed certificates from a lab environment and asked to assess them. Work each task yourself
first; `solution.md` has the exact commands and answers.

**Files**
| File | What it is |
|---|---|
| `bad-webmail.crt` | a certificate pulled from an internal webmail host — something is wrong with it |
| `root_ca.pem` | the LabKit **Root CA** |
| `intermediate_ca.pem` | the LabKit **Issuing (intermediate) CA**, signed by the root |
| `server.pem` | a server **leaf** certificate (`portal.labkit.local`), issued by the intermediate |
| `chain.pem` | `server.pem` + `intermediate_ca.pem` + `root_ca.pem` concatenated |
| `revoked.pem` | another leaf from the same intermediate |
| `intermediate.crl` | the intermediate CA's **Certificate Revocation List** |

*(No private keys are included — none are needed to inspect, verify or check revocation.)*

## Tasks

**1. Assess `bad-webmail.crt` — name every problem (max 4).**
Inspect it and decide whether a browser would trust it, and why not. Think: who signed it? how strong
is the key? is it still valid in time? is the name scoping sane?

<details><summary>💡 hint</summary>

`openssl x509 -in bad-webmail.crt -noout -text` shows everything: compare **Issuer** vs **Subject**,
read **Public-Key** bits, **Not Before/Not After**, and the **Subject Alternative Name**.
</details>

**2. Validate the chain — does `server.pem` chain to the root?**
Prove that the leaf is trusted *through* the intermediate up to the root.

<details><summary>💡 hint</summary>

`openssl verify` takes a trusted root (`-CAfile`) and any intermediates (`-untrusted`).
</details>

**3. Check revocation — is `server.pem` revoked by `intermediate.crl`? Which serial IS revoked?**
A valid chain is not enough; the issuer can still have revoked the cert.

<details><summary>💡 hint</summary>

List the CRL (`openssl crl -in intermediate.crl -noout -text`) and compare the revoked **Serial Number**
against `openssl x509 -in server.pem -noout -serial` and `... -in revoked.pem -serial`. Then verify with
the CRL enforced: `openssl verify -crl_check -CAfile <(cat root_ca.pem intermediate_ca.pem) -CRLfile intermediate.crl`.
</details>

**4. (bonus) Read the leaf's SANs, key usage and CRL distribution point.**
Which hostnames does `server.pem` actually cover, and where does it say to fetch the CRL?

---
> ⚠️ Training only — synthetic certs for a lab. The CA keys are throwaway; don't use any of this in production.
