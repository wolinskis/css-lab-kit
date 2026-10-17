# Network case studies — three quick scenarios

Small, self-contained captures that each teach one real traffic-analysis move. Open in **Wireshark** or
read with **`tshark`**; work each yourself, answers in `solution.md`. These are deliberately simple — to go
deeper on real-world traffic see **[malware-traffic-analysis.net](https://malware-traffic-analysis.net/)**
(read the safety note in the parent [`../README.md`](../README.md) first).

| # | File | Scenario | Skill |
|---|---|---|---|
| 1 | `case1_busy_lan_ftp.pcap` | a flat office LAN capture — find the cleartext login in the noise | **display filters** |
| 2 | `case2_dns_exfil.pcap` | odd DNS lookups to an unknown domain — data is leaving over DNS | **DNS exfiltration** |
| 3 | `case3_ssh_bruteforce.pcap` | an SSH alert fired — who, how many, did it work? | **pattern analysis** |

---
## 1. Busy LAN — find the cleartext login
A capture off a flat office network: lots of background chatter (DNS, SSDP). One device logged into an
internal service **in the clear**. What service, and what username/password?
<details><summary>💡 hint</summary>

Don't scroll 2994 packets — **filter**. Try the protocol name directly (`ftp`) or
`tshark -r case1_busy_lan_ftp.pcap -Y ftp`, or in Wireshark right-click the stream → Follow → TCP Stream.
</details>

## 2. DNS exfiltration — recover the smuggled secret
A host keeps resolving long, random-looking subdomains of one domain it shouldn't be talking to. Data is
being **tunnelled out over DNS**. Recover what was exfiltrated.
<details><summary>💡 hint</summary>

Pull every query name for that domain, take the **left-most label** of each (in order), concatenate them,
and **base32-decode** (re-pad with `=` to a multiple of 8). A stream of high-entropy subdomains to one
domain = classic DNS tunnelling.
</details>

## 3. SSH brute force — who, how many, did it succeed?
An IDS alerted on SSH. One source is hammering the server on port 22. You **can't read the credentials**
(SSH is encrypted) — answer from the **traffic pattern**: which source IP, how many connection attempts,
and which connection likely **succeeded**?
<details><summary>💡 hint</summary>

Count the connection attempts with a SYN filter (`tcp.dstport==22 && tcp.flags.syn==1 && tcp.flags.ack==0`).
Then look at the **conversations** (`-z conv,tcp` / Statistics → Conversations): the failed tries are tiny
and near-identical; the **outlier** that carried far more data is the one that got in.
</details>

---
> ⚠️ Training only — synthetic captures with lab data.
