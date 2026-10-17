# Network capture — recover secrets from plaintext HTTP

A self-contained **offline** lab: one packet capture from a flat network. No server needed — open it in
**Wireshark** or read it with **`tshark`**. Four escalating levels; work each yourself first, then check
`solution.md`.

**File:** `http_login_cleartext.pcap` — a TCP handshake + one HTTP `POST /login` and its response. It
leaks **three** different secrets, each one level harder to read.

| Level | Goal |
|---|---|
| 🟢 **Easy** | identify the protocol/port and recover the **cleartext form login** |
| 🟡 **Medium** | decode the **`Authorization: Basic`** header → a second credential |
| 🟠 **Hard** | decode the server's **`Set-Cookie` session token** and explain why it's weak |
| 🔴 **Expert** | reconstruct the whole exchange + list every secret + state the impact/fix |

---
## 🟢 Easy — the cleartext form login
What application protocol is this, on which port, and what username/password were submitted in the form body?
<details><summary>💡 hint</summary>

`tshark -r http_login_cleartext.pcap` lists the packets (look at the dest port + the `POST` line).
Then Wireshark → right-click → **Follow → TCP Stream**, or `tshark ... -z follow,tcp,ascii,0 -q`.
The body is `username=…&password=…`.
</details>

## 🟡 Medium — the Basic-auth header
The same request carries an `Authorization: Basic …` header. Recover the credential inside it.
<details><summary>💡 hint</summary>

HTTP **Basic** auth is `base64(user:pass)` — **encoding, not encryption**. Pull the header and
base64-decode it: `… -T fields -e http.authorization` → `echo <b64> | base64 -d`.
</details>

## 🟠 Hard — the session token
The response sets a cookie: `Set-Cookie: session=…`. Decode it. What is inside, and why is this token design insecure?
<details><summary>💡 hint</summary>

The value is base64 too. Decode it (append `==` padding if needed). It's a **JSON blob with no
signature** — so a client can change `role`/`user` and the server has no way to detect tampering.
</details>

## 🔴 Expert — full reconstruction & impact
Produce the complete picture: client IP, server IP, `Host`, URL, **all three** recovered secrets, and a
one-line risk statement + the fix.
<details><summary>💡 hint</summary>

`tshark … -T fields -e ip.src -e ip.dst -e http.host -e http.request.uri`. Everything here crossed the
wire in the clear (or in reversible base64), so anyone on-path has it all — the fix is TLS + a signed/opaque session token.
</details>

## Case studies — 3 more scenarios
Once you've done the levels above, try **[`cases/`](cases/README.md)** — three small, focused captures,
each teaching one classic move:
1. **Busy LAN** — filter past the noise to a cleartext **FTP** login.
2. **DNS exfiltration** — recover a base32 secret smuggled over DNS subdomains.
3. **SSH brute force** — identify the attacker, count attempts, and spot the one that succeeded (pattern analysis — SSH is encrypted).

Scenarios + hints in [`cases/README.md`](cases/README.md); answers in [`cases/solution.md`](cases/solution.md).

## Want more practice? (real-world captures)
This lab's capture is synthetic and tiny. For large, realistic traffic to sharpen your filtering and
analysis, a great free resource is **[malware-traffic-analysis.net](https://malware-traffic-analysis.net/)**
(Brad Duncan's PCAP exercises — infection chains, C2, exfiltration, etc.).

> ⚠️ **Safety — read before you touch it.** That is an **external, third-party** site hosting **real
> malicious traffic and malware samples**. Only open its PCAPs inside an **isolated analysis VM** (no
> internet access, no sensitive data on it); **never execute** any payload you extract, and don't open the
> samples on your normal machine. This kit is not affiliated with that site — it's just a good place to
> practise once you're comfortable with the basics here.

**Skills to practise next (what real captures add over this one):** using **display filters** to cut
through background noise (mDNS/SSDP/NBNS/QUIC/DNS), spotting cleartext creds in **Telnet/FTP/SMTP**, DNS
tunnelling/exfiltration, and recognising C2 beacons.

---
> ⚠️ Training only — synthetic capture with lab credentials.
