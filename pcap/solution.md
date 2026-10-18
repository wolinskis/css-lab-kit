# Network capture — solution (Wireshark)

> 🎯 **Goal:** practise **network forensics in Wireshark** — one capture per level, each a *different protocol*, so the real skill is choosing the right **display filter** to pull the secret out of a busy capture. Recover the credential/flag at each level.

Everything below is done in the Wireshark GUI: type the filter into the **display-filter bar** and press Enter, use **right-click packet ▸ Follow ▸ TCP/HTTP Stream** to read a conversation, and **Statistics ▸ Conversations** / **Statistics ▸ Protocol Hierarchy** for the big picture. Base64/base32 decoding is the only step done outside Wireshark (CyberChef or a one-liner).

| Level | Capture | Protocol | Answer |
|---|---|---|---|
| 🟢 Easy | `http_login_cleartext.pcap` | HTTP | `admin / S3ntinel!Admin` (+ `svc_portal / Sup3rS3cret!`, unsigned cookie) |
| 🟡 Medium | `cases/case1_busy_lan_ftp.pcap` | FTP | `bmaint / Ftp!Lan2026` |
| 🟠 Hard | `cases/case2_dns_exfil.pcap` | DNS | `FLAG{dns_exfil_base32}` |
| 🔴 Expert | `cases/case3_ssh_bruteforce.pcap` | SSH | attacker `10.0.0.66`, 15 attempts, success = client port `40014` |

---

## 🟢 Easy — cleartext HTTP login (`http_login_cleartext.pcap`)

**Filter:**

```
http
```

**Steps:**
1. **File ▸ Open** the capture. Type `http` in the display-filter bar — only the `POST /login` request and its `200 OK` response remain.
2. Right-click the `POST /login` packet ▸ **Follow ▸ HTTP Stream**. The request is shown in red, the response in blue.
3. The last line of the request is the form body.

**Result:**

```
POST /login HTTP/1.1
Host: portal.vulncorp.local
...
username=admin&password=S3ntinel!Admin&tok=1j
```

**Answer:** cleartext **HTTP on port 80**, client **192.168.56.10** → server **192.168.56.20**, `Host: portal.vulncorp.local` → **admin / S3ntinel!Admin**.

**Reason:** HTTP is plaintext, so the POST body is directly readable on the wire.

### Bonus 1 — the Basic-auth header (same capture)

**Filter:**

```
http.authorization
```

Select the matching packet, expand **Hypertext Transfer Protocol** in the packet-details pane and read the `Authorization` line (Wireshark also shows the decoded credential under it):

```
Authorization: Basic c3ZjX3BvcnRhbDpTdXAzclMzY3JldCE=
```

Decode outside Wireshark — CyberChef "From Base64", or:

```
echo 'c3ZjX3BvcnRhbDpTdXAzclMzY3JldCE=' | base64 -d
```

**Result:** `svc_portal:Sup3rS3cret!` → **svc_portal / Sup3rS3cret!** — a second credential.

**Reason:** HTTP Basic auth is `base64("user:pass")` — **encoding, not encryption**.

### Bonus 2 — the session cookie (same capture)

**Filter:**

```
http.set_cookie
```

Select the `200 OK` response, expand **Hypertext Transfer Protocol** and read the `Set-Cookie` line:

```
Set-Cookie: session=eyJ1c2VyIjoiYWRtaW4iLCJyb2xlIjoidXNlciJ9; Path=/; HttpOnly
```

Decode the value (CyberChef, or `echo '…' | base64 -d`):

```
{"user":"admin","role":"user"}
```

**Result:** the session token is **base64 JSON with no signature**.

**Reason:** anyone can change `"role":"user"` → `"role":"admin"`, re-encode it and send it back — the server has no HMAC/signature to detect the tampering → **privilege escalation**. `HttpOnly` only stops JavaScript reading it; it does nothing about the missing integrity protection.

**Fix:** serve logins over **HTTPS/TLS**; use **signed or opaque** session tokens (random server-side id, or a signed JWT).

---

## 🟡 Medium — FTP login hidden in a busy LAN (`cases/case1_busy_lan_ftp.pcap`)

The capture is ~81 frames of DNS, mDNS, NBNS, SSDP, ARP and HTTP background noise. The skill is to filter down to the **one** plaintext login protocol — and not to be distracted by the HTTP noise, which is a decoy here.

**Big picture first:** **Statistics ▸ Protocol Hierarchy** — among all the discovery protocols there is a small **File Transfer Protocol (FTP)** branch. That is the target.

**Filter:**

```
ftp
```

(`tcp.port==21` gives the same result including the TCP handshake.)

Only the FTP control channel remains: server greeting, `USER`, `PASS`, `230 Login successful`. To show only the client commands:

```
ftp.request.command
```

**Result:**

```
Request: USER bmaint
Request: PASS Ftp!Lan2026
```

Alternative: right-click any FTP packet ▸ **Follow ▸ TCP Stream** — the whole login dialogue is readable in one window.

**Answer:** FTP server **192.168.10.9:21**, credential **bmaint / Ftp!Lan2026**.

**Reason:** FTP sends `USER`/`PASS` in cleartext; one protocol filter removes all the LAN chatter.

---

## 🟠 Hard — data exfiltrated over DNS (`cases/case2_dns_exfil.pcap`)

About 51 DNS frames of normal browsing — plus a handful of queries that are not browsing at all.

**Filter:**

```
dns
```

Scroll the **Info** column: most names are readable (`www.`, `cdn.`, `mail.` …), but a few are high-entropy gibberish labels all under the **same** parent domain, `t.evil-exfil.com`. Many odd, random-looking subdomains under one attacker domain is the classic DNS-tunnelling tell-tale.

Narrow to that domain:

```
dns.qry.name contains "evil-exfil"
```

Five queries remain. Read the **left-most label** of each query name, in packet order:

```
IZGECR33.t.evil-exfil.com
MRXHGX3F.t.evil-exfil.com
PBTGS3C7.t.evil-exfil.com
MJQXGZJT.t.evil-exfil.com
GJ6Q.t.evil-exfil.com
```

Join the labels into one string: `IZGECR33MRXHGX3FPBTGS3C7MJQXGZJTGJ6Q`. Upper-case letters plus digits 2–7 (and `=` padding) means **base32**. Decode outside Wireshark — CyberChef "From Base32", or:

```
printf 'IZGECR33MRXHGX3FPBTGS3C7MJQXGZJTGJ6Q' | base32 -d
```

**Result:**

```
FLAG{dns_exfil_base32}
```

**Answer:** **`FLAG{dns_exfil_base32}`**, exfiltrated as base32 chunks in the subdomains of `t.evil-exfil.com`.

**Reason:** DNS is almost always allowed outbound, so attackers smuggle data in query names. The name's *content* is the payload; the resolver happily forwards it to the attacker's authoritative server.

---

## 🔴 Expert — SSH brute force (`cases/case3_ssh_bruteforce.pcap`)

SSH is encrypted, so the password is unreadable — you analyse the **pattern** instead: who, how many, and which attempt is the odd one out.

**Filter — one row per connection attempt (SYN only, no ACK):**

```
tcp.port==22 && tcp.flags.syn==1 && tcp.flags.ack==0
```

**Result:** **15** packets, all **Source 10.0.0.66** → **Destination 10.0.0.10**, port 22. One client, 15 new connections to the same SSH server in a short window = brute force.

**Find the successful one:** **Statistics ▸ Conversations ▸ TCP** tab, sort by **Bytes** (or by **Packets**):

- **14** conversations are tiny — a handful of packets each, the client is reset (RST) right after the SSH version banner exchange = authentication failed.
- **1** conversation, client port **40014**, transfers much more data and closes cleanly with FIN = the **successful** login.

To confirm, filter on that port and read it:

```
tcp.port==40014
```

Right-click a packet ▸ **Follow ▸ TCP Stream** — you see the version banners and key exchange, then only encrypted payload — a real session, not a rejected attempt. Compare with `tcp.flags.reset==1 && tcp.port==22` to see the 14 rejections.

**Answer:**
- Attacker **10.0.0.66**, victim **10.0.0.10:22**.
- **15** connection attempts; **14** failed, **1** succeeded (client port **40014**).

**Lesson:** *encrypted ≠ invisible*. You cannot read the password, but volume, timing and the odd-one-out conversation expose the break-in.

**Defences:** fail2ban / account lockout, **key-only** authentication (disable passwords), limit SSH exposure (firewall, VPN, non-public interface).

---

> ⚠️ Training only — intentionally vulnerable, synthetic captures with fictional hosts and lab credentials.
