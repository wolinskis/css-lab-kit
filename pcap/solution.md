# Network capture — solution

## 🟢 Easy — protocol/port + cleartext form login
**Command:**
```
tshark -r http_login_cleartext.pcap                              # overview: TCP :80, a POST /login
tshark -r http_login_cleartext.pcap -z follow,tcp,ascii,0 -q     # read the whole conversation
```
**Result:** cleartext **HTTP on port 80**; the form body is
`username=admin&password=S3ntinel!Admin&tok=1j` → **admin / S3ntinel!Admin**.
**Reason:** HTTP is plaintext, so the POST body is directly visible on the wire.

## 🟡 Medium — Basic-auth header
**Command:**
```
tshark -r http_login_cleartext.pcap -Y http.request -T fields -e http.authorization
# -> Basic c3ZjX3BvcnRhbDpTdXAzclMzY3JldCE=
echo 'c3ZjX3BvcnRhbDpTdXAzclMzY3JldCE=' | base64 -d; echo
```
**Result:** `svc_portal:Sup3rS3cret!` → a **second** credential (different user + password).
**Reason:** HTTP Basic auth is just `base64("user:pass")` — **encoding, not encryption**. On plaintext
HTTP it's recovered instantly.

## 🟠 Hard — the session token
**Command:**
```
tshark -r http_login_cleartext.pcap -Y http.response -T fields -e http.set_cookie
# -> session=eyJ1c2VyIjoiYWRtaW4iLCJyb2xlIjoidXNlciJ9; Path=/; HttpOnly
echo 'eyJ1c2VyIjoiYWRtaW4iLCJyb2xlIjoidXNlciJ9' | base64 -d; echo
```
**Result:** `{"user":"admin","role":"user"}` — the session token is **base64-encoded JSON with no
signature**.
**Reason:** anyone can base64-decode it, change `"role":"user"` → `"role":"admin"`, re-encode, and the
server can't tell it was tampered with (no HMAC/signature). `HttpOnly` stops JS reading it, but not an
on-path attacker — and it does nothing about the missing integrity protection.

## 🔴 Expert — full reconstruction & impact
**Command:**
```
tshark -r http_login_cleartext.pcap -Y http.request -T fields -e ip.src -e ip.dst -e http.host -e http.request.uri
```
**Result / Answer:**
- client **192.168.56.10** → server **192.168.56.20**, `Host: portal.vulncorp.local`, `POST /login`.
- Secrets recovered: form `admin:S3ntinel!Admin`; Basic `svc_portal:Sup3rS3cret!`; session cookie
  `{"user":"admin","role":"user"}` (base64, unsigned).
- **Impact:** every credential and the session identity crossed the wire in cleartext or reversible
  base64 → full account compromise for anyone on-path (same LAN, rogue AP, span port), plus privilege
  escalation by tampering the unsigned token.
- **Fix:** serve logins over **HTTPS/TLS**; use **signed/opaque** session tokens (random id server-side,
  or a signed JWT) — never a plain base64 blob the client controls.
