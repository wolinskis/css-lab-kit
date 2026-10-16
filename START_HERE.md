---
tags: [css-exam, labs, index, start-here]
---
# START HERE — how to run the Lab Kit
> ↑ Lab Kit · full details: `README.md`

## 1. The two web apps — what's the difference?
They are **two separate practice targets**, not connected. Pick whichever skill you want to drill.

| App | Folder | Port | Level | What it teaches | Start |
|---|---|---|---|---|---|
| **VulnCorp** | `webapp/` | **8000** | easy / foundations | the core web vulns everyone must know: SQLi, XSS (reflected+stored), LFI/traversal, command injection, file upload, IDOR, weak CSP, info disclosure, weak creds | `./webapp/run.sh` |
| **VulnNexus** | `webapp2/` | **8001** | medium → hard | the advanced vulns: **SSRF, XXE, JWT forgery**, UNION SQLi, path traversal, **filter-bypass** XSS | `./webapp2/run.sh` |

**Use case:** start with **app 1 (VulnCorp)** to learn the fundamentals, then move to **app 2 (VulnNexus)** when you want harder challenges (token forging, server-side request forgery, filter bypasses). You can run them one at a time or both at once (different ports).

## 2. Do I need a password / to log in?
**For almost every lab: NO.** You attack the endpoint **directly, unauthenticated** — just like a real external attacker hitting the URL. The vulnerable pages are deliberately exposed; there is no login wall in front of them.

Credentials matter in only **two** situations:
- **The "weak/default creds" lab** — the point *is* to guess or crack the password (so you don't get it for free).
- **The JWT lab (app 2)** — you log in with a *normal* account to receive a token, then **forge** it to become admin.

| Lab | Login needed? | How |
|---|---|---|
| app1: SQLi, XSS (both), cmd injection, LFI, upload, IDOR, info-disclosure | **No** | hit the URL / form directly |
| app1: weak-creds / brute force | yes — that's the challenge | guess `admin:S3ntinel!Admin`, or Hydra the form |
| app2: SQLi, SSRF, XXE, traversal, XSS | **No** | hit the URL directly |
| app2: JWT forgery | log in first (to get a token), **then forge** | `alice:alice123` → forge `role=admin` |

## 3. Credentials reference (only if a lab asks for them)
- **app 1 (VulnCorp):** `admin:S3ntinel!Admin` · `alice:password123` · `bob:letmein` · `svc_backup:Backup2024!`
- **app 2 (VulnNexus):** `alice:alice123` · `bob:bob123` — *admin's password is deliberately unknown; you forge the JWT instead.*

## 4. The file-analysis labs — no server, no login at all
`./build_all.sh` drops files in `artifacts/`; you analyse them locally with `openssl` / `tshark` / `sqlite3` / `john`. The only "passwords" here are the ones you **crack as the exercise**: `encrypted_rsa` passphrase = `Summer2024!`, hash passwords = `password` / `dragon` / `summer` / `hunter2`.

## 5. Simple execution flow
```bash
cd ~/Cyber && git pull
cd Exercises/Lab_Kit
./build_all.sh              # 1) generate the file artifacts (once)
./webapp/run.sh            # 2) start a target (app 1) -> open http://localhost:8000
#    (or ./webapp2/run.sh  for app 2 -> http://localhost:8001)
```
3. Pick a vuln from the table in `README.md`, open its page, and attack it **without logging in**.
4. Stuck? The answer (exact payload + command) is in `webapp/SOLUTIONS.md` / `webapp2/SOLUTIONS.md`.
5. Reset a target anytime: `rm -rf webapp/.mariadb-data webapp/uploads/*` (or `webapp2/.mariadb-data2`).

> Prereqs on Kali: `php`, `mariadb-server`, `openssl`, `ssh-keygen`, `python3`, `tshark` (+ `php-xml` for app 2's XXE). Install any missing: `sudo apt install <pkg>`.
