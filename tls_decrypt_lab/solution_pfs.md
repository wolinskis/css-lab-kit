---
tags: [css-exam, tls, pcap, decryption, pfs, solution]
---
# Solution — TLS Decryption, Part 2 (PFS / ECDHE)

Run from this lab folder. Same server, same `server.key` — but this capture uses **forward secrecy**,
so the key is useless and you must use the TLS key log instead.

## 1. Cipher suite / key exchange
**Answer:** `TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256` (id `0xc02f`). Key exchange = **ECDHE** → **forward secrecy**.
```bash
tshark -r portal_pfs.pcap -Y "tls.handshake.type==2" -T fields -e tls.handshake.ciphersuite
# -> 0xc02f
```
The session keys come from an **ephemeral** elliptic-curve Diffie–Hellman exchange. The private parts
never travel on the wire, so the RSA private key cannot reconstruct them.

## 2. The RSA private key does NOT work here
```bash
tshark -r portal_pfs.pcap -o 'uat:rsa_keys:"server.key",""' -q -z follow,tls,ascii,0
# -> still encrypted (no HTTP plaintext). Contrast with Part 1, where this exact key decrypted everything.
```

## 3. Decrypt with the SSLKEYLOGFILE
In a real engagement you obtain the key log by running the **client** with `SSLKEYLOGFILE` set
(`SSLKEYLOGFILE=keys.log curl ...` / the same env var works for Chrome & Firefox). Here it is provided
as `portal_pfs.keys`.
```bash
tshark -r portal_pfs.pcap -o 'tls.keylog_file:portal_pfs.keys' -q -z follow,tls,ascii,0
```
**Wireshark GUI:** *Edit → Preferences → Protocols → TLS → **(Pre)-Master-Secret log filename*** →
`portal_pfs.keys` → right-click a packet → **Follow → TLS Stream**.

**Request:**
```
GET /portal.html HTTP/1.1
Authorization: Basic YWRtaW46V2ludGVyIzIwMjY=
Cookie: SESSIONID=aa11bb22; role=admin
```

## 4. Credentials + flag
```bash
echo YWRtaW46V2ludGVyIzIwMjY= | base64 -d      # -> admin:Winter#2026
```
**Credentials:** `admin` / `Winter#2026`.
**Flag:** `FLAG{ecdhe_pfs_needs_the_sslkeylogfile}`
Also in the response: `Server=db02;Uid=svc_portal;Pwd=Winter#2026;`.

## Recap — the whole point of the two parts
| | Part 1 (`portal_tls.pcap`) | Part 2 (`portal_pfs.pcap`) |
|---|---|---|
| Key exchange | static RSA (`TLS_RSA_*`) | ECDHE (`TLS_ECDHE_*`) |
| Forward secrecy | no | **yes** |
| Decrypts with `server.key` | **yes** | no |
| Decrypts with `SSLKEYLOGFILE` | yes | **yes (only option)** |

A leaked server key compromises every past **static-RSA** session ever captured. With **ECDHE** that
same leak buys nothing — you need key material from an endpoint at capture time. This is why modern TLS
is ECDHE-only, and why `SSLKEYLOGFILE` (not the private key) is how you decrypt your own traffic for
debugging.
