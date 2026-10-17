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

---
> ⚠️ Training only — synthetic capture with lab credentials.
