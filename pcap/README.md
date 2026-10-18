# Network capture — four protocols, one Wireshark skill

> 🎯 **Goal:** practise **network forensics in Wireshark** — one capture per level, each a *different protocol*, so the real skill is choosing the right **display filter** to pull the secret out of a busy capture. Recover the credential/flag at each level.

A self-contained **offline** lab: four small packet captures, no server needed. Open each one in **Wireshark** (`File ▸ Open`) and work it with the display-filter bar, **right-click packet ▸ Follow ▸ TCP/HTTP Stream** and the **Statistics** menu. Work each level yourself first, then check `solution.md`.

| Level | Capture | Protocol | What you recover |
|---|---|---|---|
| 🟢 **Easy** | `http_login_cleartext.pcap` | HTTP | the form username/password (bonus: a Basic-auth header and a decodable session cookie) |
| 🟡 **Medium** | `cases/case1_busy_lan_ftp.pcap` | FTP | one cleartext login hidden in ~81 frames of LAN noise |
| 🟠 **Hard** | `cases/case2_dns_exfil.pcap` | DNS | a flag smuggled out as base32 subdomain labels |
| 🔴 **Expert** | `cases/case3_ssh_bruteforce.pcap` | SSH | attacker IP, attempt count and the one successful connection — from the pattern, not the password |

**Start every capture with** **Statistics ▸ Protocol Hierarchy** to see the protocol mix, then narrow with a filter.

---

## 🟢 Easy — cleartext HTTP login
Recover the username and password submitted to `portal.vulncorp.local`.

<details><summary>💡 hint</summary>

Filter `http`, then right-click the `POST /login` ▸ **Follow ▸ HTTP Stream** — the form body is the last line of the request. Bonus: `http.authorization` and `http.set_cookie` each hide another base64 secret; decode them outside Wireshark (CyberChef or `base64 -d`).
</details>

## 🟡 Medium — FTP login in a busy LAN
Find the FTP server and the username/password among DNS, mDNS, NBNS, SSDP, ARP and HTTP chatter. `http` is a decoy.

<details><summary>💡 hint</summary>

Filter `ftp` (or `tcp.port==21`), then `ftp.request.command` to see only `USER` / `PASS` — or **Follow ▸ TCP Stream** on any FTP packet.
</details>

## 🟠 Hard — DNS exfiltration
Spot the attacker domain among ~51 DNS queries, extract the data from the subdomain labels and decode the flag.

<details><summary>💡 hint</summary>

Filter `dns` and look for high-entropy labels under one domain, then `dns.qry.name contains "evil-exfil"`. Join the left-most labels in order; uppercase + digits 2–7 is **base32** — decode with CyberChef or `base32 -d`.
</details>

## 🔴 Expert — SSH brute force
SSH is encrypted. Identify the attacker, count the attempts, and find the one connection that succeeded.

<details><summary>💡 hint</summary>

Filter `tcp.port==22 && tcp.flags.syn==1 && tcp.flags.ack==0` to count attempts and read the source. Then **Statistics ▸ Conversations ▸ TCP**, sort by Bytes — the failed attempts are tiny and reset after the banner; the odd-one-out with far more data is the successful login. *Encrypted ≠ invisible.*
</details>

---

## Want more practice? (real-world captures)
These captures are synthetic and tiny. For large, realistic traffic, see **[malware-traffic-analysis.net](https://malware-traffic-analysis.net/)**.

> ⚠️ **Safety.** That is an **external, third-party** site hosting **real malicious traffic and malware samples**. Only open its PCAPs inside an **isolated analysis VM** (no internet, no sensitive data); **never execute** any payload you extract. This kit is not affiliated with that site.

---
> ⚠️ Training only — intentionally vulnerable, synthetic captures with fictional hosts and lab credentials.
