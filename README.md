# CSS Lab Kit — hands-on web & forensics training

A **self-contained** kit to practise security-testing techniques against real targets on your own
machine: two deliberately vulnerable web apps + generator scripts that produce real file artifacts
(certificates, keys, PCAP, phishing `.eml`, datasets, hashes). Everything is reproducible — nothing to
download. Great for exam prep and teaching.

> 🟢 **New here? Read [`START_HERE.md`](START_HERE.md) first** — the two apps explained + when you need a login.

---

## ⚠️ Disclaimer & safety — read this first
These applications are **intentionally vulnerable** for education. Command injection, file upload and
LFI give **real code/file access on the machine running them**. Use **only** for authorized, ethical,
educational training.

- **Never expose it to the internet** or an untrusted network.
- Run it on a **VM / isolated lab box**, not a machine with anything sensitive.
- **Local (`localhost`) or an isolated classroom LAN only.**

Use at your own risk; the authors accept no liability for misuse.

---

## Quickstart
```bash
# prerequisites (Kali): php, mariadb-server, openssl, ssh-keygen, python3, tshark  (+ php-xml for XXE)
git clone https://github.com/wolinskis/css-lab-kit.git
cd css-lab-kit
./build_all.sh          # generate all file artifacts into artifacts/
./webapp/run.sh         # app 1 (VulnCorp)         -> http://localhost:8000
./webapp2/run.sh        # app 2 (VulnNexus, harder)-> http://localhost:8001
```
Each `run.sh` spins up its own throwaway MariaDB (no system service, no root) and the PHP app together;
Ctrl+C stops both. Reset a target: `rm -rf webapp/.mariadb-data webapp/uploads/*`.

> **Run detached** (keep the apps up after the terminal closes): `nohup ./webapp/run.sh >/tmp/vc.log 2>&1 &`
> and `nohup ./webapp2/run.sh >/tmp/vn.log 2>&1 &`. Stop a detached app with `pkill -f 'php -S 0.0.0.0:8000'`
> (or `:8001`) — its MariaDB shuts down via `run.sh`'s trap. Restart an app after installing `php-xml` so PHP loads it.

> App 2's XXE lab (`import.php`) needs PHP's XML extension: `sudo apt update && sudo apt install -y php-xml`,
> then restart `run.sh`. Run `apt update` **first** — without it a stale package index can 404 on the
> current `php-xml` build. `run.sh` prints a warning if it's missing; every other exercise works without it.

---

## Sharing with colleagues
1. **Everyone hosts locally** — each person runs it on their own machine (`localhost`). Cleanest / safest.
2. **One person hosts, others connect over a LAN** — `run.sh` already binds `0.0.0.0`, so colleagues
   browse `http://<your-lab-ip>:8000`. Only do this on a **trusted, isolated lab network**.

---

## The two web apps
| App | Folder | Port | Level | Vulnerabilities |
|---|---|---|---|---|
| **VulnCorp** | `webapp/` | 8000 | easy / foundations | SQLi (auth-bypass + UNION), reflected & stored XSS, IDOR, command injection, LFI/path traversal, insecure file upload, weak CSP, information disclosure, weak credentials |
| **VulnNexus** | `webapp2/` | 8001 | medium → hard | UNION SQLi, **JWT forgery** (`alg:none` + weak secret), **SSRF**, **XXE**, path traversal, filter-bypass reflected XSS |

Start with **app 1** for fundamentals, then **app 2** to level up. Default creds only matter for the
weak-creds and JWT labs — everything else you attack unauthenticated. Flags are `FLAG{...}`.

## Two ways to use each app
Same vulnerable code behind both views — pick whichever fits:

- 🎯 **Hub** (`index.php`, the landing page) — the fancy, guided console: pick a card, choose a level
  (Easy → Expert), click **💡 hint → use this → Run**, and the answer shows in the panel. A live URL
  preview shows exactly where your payload lands, and switching levels re-fills the input so you can
  compare techniques side by side. Best for **learning and comparing levels**.
- 🏠 **Home** (`home.php`, plus the raw endpoints like `login.php`, `profile.php`, `upload.php`) — the
  plain app. Less pretty, but more direct/functional: you drive the real pages and craft payloads in
  the URL yourself, like a real target. Use it when the hub feels fiddly.

Work out the payload yourself; stuck → reveal the **💡 hint**. Full walkthroughs in
[`SOLUTIONS.md`](SOLUTIONS.md).

## File artifacts (`artifacts/`, after `build_all.sh`)
- **certs/** — a flawed cert (self-signed / 1024-bit / expired / SAN mismatch) + a `root→intermediate→leaf` chain + `chain.pem` + a **CRL**
- **keys/** — ed25519, RSA, and a passphrase-protected `encrypted_rsa`
- **email/phishing.eml** — header/SPF/DKIM analysis + an OpenSSH key appended outside the MIME tree
- **network/*.pcap** — cleartext HTTP login (Follow TCP Stream)
- **tls_decrypt_lab/** — an **encrypted** HTTPS pcap + the server **private key**: decrypt it (static-RSA, no forward secrecy) to recover the login + flag
- **data/** — a re-identification CSV pair + a KPI-audit SQLite DB
- **hashes/** — md5 / sha1 / apr1 / sha512 (all crack from rockyou)


## Privilege escalation lab (`privesc/`)
From a low-priv shell to **root**: analyse the provided enumeration (`privesc/enum/`), find all 5
privesc vectors (GTFOBins), and turn a Meterpreter session into root via `local_exploit_suggester`.
See `privesc/README.md` + `solution.md`.

## TLS decryption lab (`tls_decrypt_lab/`)
A captured HTTPS session + the server's recovered **RSA private key**. Add the key in Wireshark
(*Preferences → Protocols → TLS → RSA keys list*) or
`tshark -o 'uat:rsa_keys:"artifacts/server.key",""' -q -z follow,tls,ascii,0`, read the plaintext,
and recover the login + flag. Teaches why static-RSA (no forward secrecy) is decryptable but ECDHE is not.

## Progress tracker (`progress.html`)
Open it in a browser (no server needed) — one **gamified checklist across every lab**: the web-app
flags, the file-artifact recovered values (cracked passwords, the hidden OpenSSH key, the KPI finding…),
TLS, and privesc. Progress is saved in your browser.

## Cracking cheat-sheet (identify → mode)
1. **Identify** the hash: `hashid '<hash>'`, `hashcat --identify h.txt`, `name-that-hash`, or by shape —
   32 hex = MD5/NTLM · 40 = SHA-1 · 64 = SHA-256 · `$2y$…` = bcrypt · `$6$…` = sha512crypt · `$apr1$…` = md5crypt.
2. **Pick the hashcat mode** (`-m`):

   | algorithm | raw | salt **after** `h($pass.$salt)` | salt **before** `h($salt.$pass)` |
   |---|---|---|---|
   | MD5 | `-m 0` | `-m 10` | `-m 20` |
   | SHA-1 | `-m 100` | `-m 110` | `-m 120` |
   | SHA-256 | `-m 1400` | `-m 1410` | `-m 1420` |
   | NTLM | `-m 1000` | — | — |
   | bcrypt `$2y$` | `-m 3200` | salt is built-in | |
   | md5crypt `$apr1$` | `-m 1600` | built-in | |
   | sha512crypt `$6$` | `-m 1800` | built-in | |

3. **File format = what the mode expects:** raw → `hash`; salted → `hash:salt` (one per line). A dump line
   `user:hash[:salt]` → for **hashcat** strip the user (`cut -d: -f2- dump > h.txt`) or add `--username`;
   **john** keeps `user:hash`.
4. **Salt position unknown?** It's cheap — try the `…10` mode then the `…20` mode. Homegrown PHP usually
   **appends** the salt, so try `-m 10/110/1410` first. (If you can read the app source, it states the exact
   construction — no guessing.)
5. **Run it:** `hashcat -m <n> h.txt /usr/share/wordlists/rockyou.txt` (add `-a 0`), or
   `john --format=<fmt> --wordlist=/usr/share/wordlists/rockyou.txt h.txt`. Slow algos (bcrypt/`$6$`) take
   longer by design; raw MD5/SHA fall in seconds.

## Solutions
Full **Answer / Command / Result** walkthroughs: [`webapp/SOLUTIONS.md`](webapp/SOLUTIONS.md) and
[`webapp2/SOLUTIONS.md`](webapp2/SOLUTIONS.md). Try each vuln yourself first, then check.

---

## License
MIT with an **educational / authorized-use clause** — see [LICENSE](LICENSE). This software is
vulnerable by design; use it only for lawful, authorized, isolated training.

## Credits
Built for Cyber Security Specialist (CSS) exam prep. **Special thanks to Mat.** 🙏
