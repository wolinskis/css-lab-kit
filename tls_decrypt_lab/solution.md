---
tags: [css-exam, tls, pcap, decryption, solution]
---
# Solution — TLS Decryption

Run from this lab folder.

## 1. Cipher suite / key exchange
**Answer:** `TLS_RSA_WITH_AES_128_CBC_SHA256` (id `0x003c`). Key exchange = **static RSA**, **no forward secrecy**.
```bash
tshark -r portal_tls.pcap -Y "tls.handshake.type==2" -T fields -e tls.handshake.ciphersuite
# -> 0x003c
```
**Why it matters:** with static RSA the client encrypts the pre-master secret with the server's
**public** key. The matching **private** key decrypts it → session keys → full plaintext. With
`ECDHE_*`/`DHE_*` the keys come from an ephemeral DH exchange never sent on the wire, so the private
key alone cannot decrypt — you'd need a TLS key log (`SSLKEYLOGFILE`).

## 2. Decrypt + the HTTP request
```bash
tshark -r portal_tls.pcap -o 'uat:rsa_keys:"server.key",""' \
       -q -z follow,tls,ascii,0
```
**Request:**
```
GET /portal.html HTTP/1.0
Host: portal.lab.local
Authorization: Basic YWRtaW46QXV0dW1uIzIwMjU=
Cookie: SESSIONID=9f2c1a7b4e; role=admin
```

## 3. Credentials
```bash
echo YWRtaW46QXV0dW1uIzIwMjU= | base64 -d      # -> admin:Autumn#2025
```
**Answer:** `admin` / `Autumn#2025`.

## 4. Flag + secret in the response
**Flag:** `FLAG{tls_rsa_no_pfs_is_decryptable}`
Also leaked in the response body: `Server=db01;Uid=svc_portal;Pwd=Autumn#2025;` (DB connection string).

## Recap
Static-RSA TLS = decryptable forever with the private key. Always prefer forward-secret suites
(`ECDHE`), and treat a leaked server key as a full compromise of every past session captured on the wire.
