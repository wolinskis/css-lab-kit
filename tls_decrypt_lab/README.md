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
