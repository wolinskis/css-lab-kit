# Network case studies — solution

## 1. Busy LAN — cleartext FTP login
**Command:**
```
tshark -r case1_busy_lan_ftp.pcap -Y ftp.request -T fields -e ftp.request.command -e ftp.request.arg
# or just follow the stream:
tshark -r case1_busy_lan_ftp.pcap -Y ftp -z follow,tcp,ascii,0 -q
```
**Answer:** service = **FTP** (TCP port 21); **username `bmaint` / password `Ftp!Lan2026`**.
**Reason:** FTP's control channel is plaintext — `USER`/`PASS` are visible. The DNS/SSDP traffic is just
noise; filtering (`ftp`, or `tcp.port==21`) cuts straight to it. Same idea works for Telnet (23), SMTP
(25), POP3/IMAP, HTTP — any protocol without TLS.

## 2. DNS exfiltration
**Command:**
```
tshark -r case2_dns_exfil.pcap -Y 'dns.qry.name contains "evil-exfil"' -T fields -e dns.qry.name
#   -> IZGECR33.t.evil-exfil.com  MRXHGX3F.t.evil-exfil.com  ...  (5 labels)
# take the left-most label of each, concatenate, base32-decode:
tshark -r case2_dns_exfil.pcap -Y 'dns.qry.name contains "evil-exfil"' -T fields -e dns.qry.name \
  | sed 's/\.t\.evil-exfil\.com//' | tr -d '\n' \
  | python3 -c 'import sys,base64; s=sys.stdin.read(); s+="="*((8-len(s)%8)%8); print(base64.b32decode(s).decode())'
```
**Answer:** `FLAG{dns_exfil_base32}`.
**Reason:** the payload was base32-encoded and carried out one chunk per DNS **subdomain label** to a
domain the attacker controls — the resolver dutifully forwards each lookup, so data leaves even when only
DNS is allowed out. Tell-tale signs: many high-entropy subdomains, all under one odd domain, A/TXT queries
with no normal browsing pattern.

## 3. SSH brute force
**Command:**
```
# how many connection attempts to :22
tshark -r case3_ssh_bruteforce.pcap -Y 'tcp.dstport==22 && tcp.flags.syn==1 && tcp.flags.ack==0' | wc -l   # -> 15
# the source
tshark -r case3_ssh_bruteforce.pcap -Y 'tcp.dstport==22 && tcp.flags.syn==1 && tcp.flags.ack==0' -T fields -e ip.src | sort -u   # -> 10.0.0.66
# which one succeeded -> conversation stats, find the outlier that carried real data
tshark -r case3_ssh_bruteforce.pcap -q -z conv,tcp
```
**Answer:** source **`10.0.0.66`**, **15** connections to the server's port 22; **14 fail fast** (tiny,
each reset after the version banner) and **1 — client port `40014` — carries far more data and closes
cleanly** → the successful login.
**Reason:** SSH is encrypted, so you can't read the password — but brute force has an unmistakable
**pattern**: many short, near-identical connections from one source, then one that behaves differently
(stays up, transfers data). Encrypted ≠ invisible; volume, timing and the odd-one-out tell the story.
Defenses: rate-limit/lockout (fail2ban), keys-only auth, move/limit SSH exposure.
