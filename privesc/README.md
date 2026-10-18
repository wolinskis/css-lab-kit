---
tags: [css-exam, privesc, metasploit, lab]
---
# Privilege Escalation Lab (Linux) — spot the vectors, then Metasploit

## 🎯 The goal, in one line

You have a **low-privilege shell** — you're the `www-data` web user on a Linux box called `web01`.
**Become `root`** (the admin account) and read the protected file `/root/flag.txt`.

## What this lab is (and what it isn't)

You don't attack a live machine here. In real life, the moment you break into a Linux box you
**enumerate** it — run a few commands to see how it's (mis)configured. That output is already captured
for you in the **`enum/`** folder. **Your only job is to read those files and recognise the
misconfigurations that let a normal user turn into root.** Spotting them quickly is exactly the skill
the exam grades — so this lab trains the *reading*, not the typing.

## The files you're given — and the red flag to hunt in each

| File | The command that produced it | What you're looking for |
|---|---|---|
| `enum/sudo_l.txt` | `sudo -l` | a command you may run **as root with no password** |
| `enum/suid.txt` | `find / -perm -4000` | a program that **runs as its owner (root)** but shouldn't |
| `enum/cron.txt` | `cat /etc/crontab` | a script **root runs on a schedule** that **you can edit** |
| `enum/capabilities.txt` | `getcap -r /` | a binary given a **root-like capability** (e.g. `cap_setuid`) |
| `enum/system.txt` | `id` / `uname -a` | the **OS version** → a known local/kernel exploit (e.g. PwnKit) |

There are **5** independent ways to root hiding in there. Any **one** of them is enough to win.

## Your tasks

1. Read the 5 `enum/` files and **list every path to root** (there are 5).
2. For each, write the **exact one-liner** that drops you a root shell. The site
   [GTFOBins](https://gtfobins.github.io) gives the trick for almost any SUID/sudo/capability binary.
3. Write the **Metasploit** steps to turn a Meterpreter session into root (session → suggester → local exploit).
4. Get the flag: the root-only `FLAG{...}`. Here it lives in `enum/root_flag.txt` — that's what
   `/root/flag.txt` would hold once you're root.

## Stuck, or want to check your answers?

Every vector is worked out — **with the "how did I spot it" for each** — in **`solution.md`**
(or open this lab in the browser at **`/privesc/`**). References: GTFOBins, and the vault's Metasploit
notes (`MSF_Index`).
