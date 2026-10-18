#!/usr/bin/env python3
"""Augment the Medium (FTP) and Hard (DNS-exfil) case captures with realistic
mixed-protocol background noise, so the challenge is to pick the RIGHT Wireshark
display filter (ftp / dns) out of a busy capture — not to read a 5-packet file.

Builds noise with scapy (valid packets, correct checksums) and mergecaps it into
the existing signal captures. Re-runnable; the FTP creds and DNS exfil are left
untouched. Solve filters stay: Medium -> `ftp`  ·  Hard -> `dns`.
"""
import os, subprocess, tempfile, random
from scapy.all import (Ether, IP, UDP, TCP, DNS, DNSQR, DNSRR, ARP, Raw, wrpcap)

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CASES = os.path.join(ROOT, "pcap", "cases")
CM, SM = "0c:0c:0c:00:00:11", "0c:0c:0c:00:00:22"   # same client/server MACs the cases use
random.seed(1337)

def merge(signal, noise_pkts, t0):
    """Write noise to a temp pcap (timestamps overlapping the signal window) and
    mergecap it into `signal`, so Wireshark shows the two interleaved by time."""
    for i, p in enumerate(noise_pkts):
        p.time = t0 + 0.0004 * i            # spread across the same ~second as the signal
    nf = tempfile.NamedTemporaryFile(suffix=".pcap", delete=False); nf.close()
    wrpcap(nf.name, noise_pkts)
    out = signal + ".tmp"
    subprocess.run(["mergecap", "-w", out, signal, nf.name], check=True)
    os.replace(out, signal); os.unlink(nf.name)

def sig_t0(path):
    """First packet timestamp of the signal capture, to line noise up with it."""
    from scapy.all import rdpcap
    return float(rdpcap(path)[0].time)

# ---------------- Medium: busy LAN around the FTP login ----------------
def noise_lan():
    C, RES, WEB, GW = "192.168.10.50", "192.168.10.1", "192.168.10.80", "192.168.10.1"
    BCAST, MDNS, LLMNR, SSDP = "192.168.10.255", "224.0.0.251", "224.0.0.252", "239.255.255.250"
    domains = ["teams.microsoft.com","login.microsoftonline.com","windowsupdate.com","ctldl.windowsupdate.com",
               "fonts.googleapis.com","edge.microsoft.com","go.trouter.io","settings-win.data.microsoft.com",
               "outlook.office365.com","clients4.google.com","pool.ntp.org","connectivitycheck.gstatic.com",
               "wpad.lan","fileserver.lan","printer.lan","intranet.lan","update.lan","vcenter.lan"]
    pk = []
    for i, d in enumerate(domains):                              # normal recursive DNS
        sp = 40000 + i
        pk.append(Ether(src=CM,dst=SM)/IP(src=C,dst=RES)/UDP(sport=sp,dport=53)/DNS(id=0x3000+i,rd=1,qd=DNSQR(qname=d)))
        pk.append(Ether(src=SM,dst=CM)/IP(src=RES,dst=C)/UDP(sport=53,dport=sp)/DNS(id=0x3000+i,qr=1,rd=1,ra=1,qd=DNSQR(qname=d),an=DNSRR(rrname=d,ttl=60,rdata="192.168.10.%d"%(20+i))))
    for i, h in enumerate(["DESKTOP-7F2A","WPAD","FILESRV","PRINTER01"]):     # NBNS name lookups (:137)
        pk.append(Ether(src=CM,dst="ff:ff:ff:ff:ff:ff")/IP(src=C,dst=BCAST)/UDP(sport=137,dport=137)/DNS(id=0x4000+i,rd=1,qd=DNSQR(qname=h,qtype=32)))
    for i, h in enumerate(["_companion-link._tcp.local","_airplay._tcp.local","hp-printer.local"]):  # mDNS
        pk.append(Ether(src=CM,dst="01:00:5e:00:00:fb")/IP(src=C,dst=MDNS)/UDP(sport=5353,dport=5353)/DNS(rd=0,qd=DNSQR(qname=h,qtype="PTR")))
    for i, h in enumerate(["fileserver","intranet"]):                         # LLMNR
        pk.append(Ether(src=CM,dst="01:00:5e:00:00:fc")/IP(src=C,dst=LLMNR)/UDP(sport=5355,dport=5355)/DNS(id=0x5000+i,rd=0,qd=DNSQR(qname=h)))
    for _ in range(3):                                                        # SSDP discovery
        pk.append(Ether(src=CM,dst="01:00:5e:7f:ff:fa")/IP(src=C,dst=SSDP)/UDP(sport=51000,dport=1900)/Raw(load=b"M-SEARCH * HTTP/1.1\r\nHOST:239.255.255.250:1900\r\nMAN:\"ssdp:discover\"\r\nMX:1\r\nST:ssdp:all\r\n\r\n"))
    for i in range(4):                                                        # ARP who-has
        pk.append(Ether(src=CM,dst="ff:ff:ff:ff:ff:ff")/ARP(op=1,psrc=C,pdst="192.168.10.%d"%(100+i)))
    # a couple of HTTP requests to a WEB host on :80 (so `http` alone is NOT the FTP answer)
    for i,(host,uri) in enumerate([("intranet.lan","/news"),("update.lan","/latest.json")]):
        sp = 55000 + i
        pk.append(Ether(src=CM,dst=SM)/IP(src=C,dst=WEB)/TCP(sport=sp,dport=80,flags="S",seq=100))
        pk.append(Ether(src=SM,dst=CM)/IP(src=WEB,dst=C)/TCP(sport=80,dport=sp,flags="SA",seq=500,ack=101))
        pk.append(Ether(src=CM,dst=SM)/IP(src=C,dst=WEB)/TCP(sport=sp,dport=80,flags="A",seq=101,ack=501))
        req=("GET %s HTTP/1.1\r\nHost: %s\r\nUser-Agent: Mozilla/5.0\r\nAccept: */*\r\n\r\n"%(uri,host)).encode()
        pk.append(Ether(src=CM,dst=SM)/IP(src=C,dst=WEB)/TCP(sport=sp,dport=80,flags="PA",seq=101,ack=501)/Raw(load=req))
        pk.append(Ether(src=SM,dst=CM)/IP(src=WEB,dst=C)/TCP(sport=80,dport=sp,flags="PA",seq=501,ack=101+len(req))/Raw(load=b"HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nok"))
    random.shuffle(pk)
    return pk

# ---------------- Hard: legit DNS browsing hiding the exfil ----------------
def noise_dns():
    C, RES = "10.1.1.33", "10.1.1.1"
    domains = ["www.google.com","maps.gstatic.com","api.github.com","cdn.jsdelivr.net",
               "www.cloudflare.com","yt3.ggpht.com","i.ytimg.com","www.wikipedia.org","registry.npmjs.org",
               "pypi.org","files.pythonhosted.org","ocsp.digicert.com","clientservices.googleapis.com",
               "safebrowsing.googleapis.com","www.bing.com","login.live.com","graph.microsoft.com",
               "aka.ms","dc.services.visualstudio.com","telemetry.lan","mirror.lan","proxy.lan"]
    pk = []
    for i, d in enumerate(domains):
        sp = 42000 + i
        pk.append(Ether(src=CM,dst=SM)/IP(src=C,dst=RES)/UDP(sport=sp,dport=53)/DNS(id=0x6000+i,rd=1,qd=DNSQR(qname=d)))
        pk.append(Ether(src=SM,dst=CM)/IP(src=RES,dst=C)/UDP(sport=53,dport=sp)/DNS(id=0x6000+i,qr=1,rd=1,ra=1,qd=DNSQR(qname=d),an=DNSRR(rrname=d,ttl=120,rdata="93.184.216.%d"%(1+i))))
    random.shuffle(pk)
    return pk

if __name__ == "__main__":
    c1 = os.path.join(CASES, "case1_busy_lan_ftp.pcap")
    c2 = os.path.join(CASES, "case2_dns_exfil.pcap")
    merge(c1, noise_lan(), sig_t0(c1))
    merge(c2, noise_dns(), sig_t0(c2))
    print("[*] augmented case1 (FTP) and case2 (DNS-exfil) with background noise")
