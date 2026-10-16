---
tags: [css-exam, privesc, metasploit, lab]
---
# Privilege Escalation Lab (Linux) — identify the vectors, then Metasploit

**Scenario:** you landed a low-privilege shell as `www-data` on `web01` (e.g. via the webshell
upload or command injection in app 1). Escalate to **root** and read `/root/flag.txt`.

This lab is **self-contained for the identification half**: `enum/` holds real-shaped enumeration
output (sudo, SUID, cron, capabilities, system). Your job is to spot **every** privesc vector in it
and write the exact command that exploits each — that spotting skill is what the exam tests. The
**Metasploit half** (turning a Meterpreter session into root) is a documented workflow you run
against a practice VM.

## Tasks
1. From `enum/sudo_l.txt`, `enum/suid.txt`, `enum/cron.txt`, `enum/capabilities.txt`, `enum/system.txt`,
   list **every** path to root you can find (there are 5).
2. For each, write the **exact one-liner** that gives you a root shell (use GTFOBins).
3. Write the **Metasploit** steps to go from a Meterpreter session to root
   (session → suggester → local exploit).
4. Flag: the root-only `FLAG{...}` in `enum/root_flag.txt` (what `/root/flag.txt` would hold).

Check yourself against `solution.md`. Reference: GTFOBins (gtfobins.github.io), the vault's
Metasploit notes (`MSF_Index`).
