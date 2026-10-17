---
tags: [css-exam, tls, pcap, decryption, lab]
---
# TLS Decryption Lab — decrypt HTTPS with the server private key

**Scenario:** an incident-response capture of a user reaching an internal HTTPS portal
(`portal.lab.local`) was taken. The server's **RSA private key** was later recovered from a backup.
Your job: decrypt the "encrypted" session, read what was actually sent, and explain *why* this was
possible — then find the flag.

This is the exact flow you asked for: **a pcap you must decrypt → add the private key → read the
plaintext → find the flag.**

## Artifacts
- `portal_tls.pcap` — the captured TLS 1.2 session (encrypted on the wire)
- `server.key` — the server's RSA private key (recovered)
- `server.crt` — the matching certificate (for reference)

## Tasks
1. Which cipher suite / key exchange did the session use? Why does that matter for decryption?
2. Add the private key and decrypt the session. What **HTTP request** was made?
3. Recover the **credentials** sent in the request.
4. Find the **flag** (and any other secret) in the **response**.

## Quick start

**Wireshark (GUI):**
1. Open `portal_tls.pcap`.
2. *Edit → Preferences → Protocols → TLS → **RSA keys list** → Edit → +*
   and add: **Key File** = `server.key` (IP/Port/Protocol can be left blank / `http`).
3. Right-click a packet → **Follow → TLS Stream**. The plaintext HTTP request **and** response appear.

**tshark (CLI), from this folder:**
```bash
tshark -r portal_tls.pcap -o 'uat:rsa_keys:"server.key",""' \
       -q -z follow,tls,ascii,0
```

Try it first; then check `solution.md`.

> **Why it works:** this session uses **static-RSA** key exchange (`TLS_RSA_WITH_*`) — no forward
> secrecy. The private key recovers the pre-master secret and therefore the whole session. Against
> `ECDHE_*`/`DHE_*` (forward secrecy) the private key is useless; you'd need an `SSLKEYLOGFILE`.
> That contrast is the point of the lab.

---

## Part 2 — the PFS variant (when the key is NOT enough)

**Scenario:** a second capture of the *same* portal, `portal_pfs.pcap`. You have the same recovered
`server.key` — but this time it **won't** decrypt anything. Figure out why, then decrypt it the way
you actually have to against modern TLS.

### Artifacts
- `portal_pfs.pcap` — a TLS 1.2 session using **ECDHE** (forward secrecy)
- `portal_pfs.keys` — a TLS key log (`SSLKEYLOGFILE`) captured from the client at handshake time
- `server.key` / `server.crt` — the same key/cert as Part 1 (deliberately useless here)

### Tasks
1. What key exchange did this session use, and why does `server.key` no longer work?
2. Decrypt it with the key log instead. What request/credentials/flag come out?

### Quick start
**Wireshark (GUI):** open `portal_pfs.pcap` → *Edit → Preferences → Protocols → TLS →
**(Pre)-Master-Secret log filename*** = `portal_pfs.keys` → right-click → **Follow → TLS Stream**.
(Try the RSA keys list with `server.key` first and watch it fail.)

**tshark (CLI):**
```bash
# fails (ECDHE, forward secrecy):
tshark -r portal_pfs.pcap -o 'uat:rsa_keys:"server.key",""'       -q -z follow,tls,ascii,0
# works (key log):
tshark -r portal_pfs.pcap -o 'tls.keylog_file:portal_pfs.keys'    -q -z follow,tls,ascii,0
```

Try it first; then check `solution_pfs.md`. Regenerate both labs' PFS files with
`../generators/gen_tls_pfs.sh`.

> **The lesson:** a leaked server key decrypts every past **static-RSA** session (Part 1) but **nothing**
> forward-secret (Part 2). ECDHE is why. To decrypt forward-secret TLS you need key material from an
> endpoint — the `SSLKEYLOGFILE` your browser/curl can emit — not the server's private key.
