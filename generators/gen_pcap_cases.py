#!/usr/bin/env python3
"""Generate three SIMPLE network case-study PCAPs for the network lab (no scapy).
  case1_busy_lan_ftp.pcap  — a cleartext FTP login hidden among DNS/UDP noise (practise filtering)
  case2_dns_exfil.pcap     — a secret base32-exfiltrated over DNS subdomain labels
  case3_ssh_bruteforce.pcap— one host hammering :22 (traffic-pattern analysis, not cred reading)
Valid Ethernet/IPv4/TCP|UDP with correct checksums; readable in Wireshark / tshark."""
import struct, os, base64, time

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT  = os.path.join(ROOT, "pcap", "cases"); os.makedirs(OUT, exist_ok=True)

def mac(s): return bytes.fromhex(s.replace(":", ""))
def ipb(s): return bytes(int(x) for x in s.split("."))

def csum(d):
    if len(d) % 2: d += b"\x00"
    s = sum(struct.unpack("!%dH" % (len(d)//2), d)); s = (s>>16)+(s&0xffff); s += s>>16
    return (~s) & 0xffff

def ipv4(src, dst, proto, payload, ipid=0x4000):
    hdr = struct.pack("!BBHHHBBH4s4s", 0x45, 0, 20+len(payload), ipid, 0x4000, 64, proto, 0, src, dst)
    hdr = hdr[:10] + struct.pack("!H", csum(hdr)) + hdr[12:]
    return hdr + payload

def tcp(src, dst, sport, dport, seq, ack, flags, data=b""):
    off = (5 << 4)
    hdr = struct.pack("!HHIIBBHHH", sport, dport, seq, ack, off, flags, 8192, 0, 0)
    pseudo = struct.pack("!4s4sBBH", src, dst, 0, 6, len(hdr)+len(data))
    hdr = hdr[:16] + struct.pack("!H", csum(pseudo+hdr+data)) + hdr[18:]
    return hdr + data

def udp(src, dst, sport, dport, data):
    hdr = struct.pack("!HHHH", sport, dport, 8+len(data), 0)
    pseudo = struct.pack("!4s4sBBH", src, dst, 0, 17, 8+len(data))
    hdr = hdr[:6] + struct.pack("!H", csum(pseudo+hdr+data)) + hdr[8:]   # csum occupies bytes 6-7
    return hdr + data

CM, SM = mac("0c0c0c000011"), mac("0c0c0c000022")   # client/server MACs
def eth(to_srv, payload):
    d, s = (SM, CM) if to_srv else (CM, SM)
    return d + s + b"\x08\x00" + payload

class Pcap:
    def __init__(self): self.pkts=[]; self.t=int(time.time()); self.n=0
    def add(self, raw): self.pkts.append((self.t, self.n*1000, raw)); self.n+=1
    def write(self, path):
        with open(path, "wb") as f:
            f.write(struct.pack("!IHHiIII", 0xa1b2c3d4, 2, 4, 0, 0, 65535, 1))
            for ts, us, p in self.pkts:
                f.write(struct.pack("!IIII", ts, us, len(p), len(p)) + p)

def qname(name):
    out = b""
    for lbl in name.split("."):
        out += bytes([len(lbl)]) + lbl.encode()
    return out + b"\x00"

def dns_query(txid, name):
    return struct.pack("!HHHHHH", txid, 0x0100, 1, 0, 0, 0) + qname(name) + struct.pack("!HH", 1, 1)

# ---------------------------------------------------------------- case 1: busy LAN + FTP login
def case1():
    p = Pcap()
    C, S = ipb("192.168.10.50"), ipb("192.168.10.9")       # client, FTP server
    RES = ipb("192.168.10.1")                              # DNS resolver (noise)
    # background DNS noise
    for i, nm in enumerate(["wpad.lan", "connectivity-check.lan", "ntp.pool.lan", "updates.lan"]):
        p.add(eth(True,  ipv4(C, RES, 17, udp(C, RES, 50000+i, 53, dns_query(0x1000+i, nm)))))
        p.add(eth(False, ipv4(RES, C, 17, udp(RES, C, 53, 50000+i, dns_query(0x1000+i, nm) + b"\xc0\x0c\x00\x01\x00\x01\x00\x00\x00\x3c\x00\x04\xc0\xa8\x0a\x14"))))
    # a bit of noisy UDP broadcast (SSDP-ish) — just filler on :1900
    p.add(eth(True, ipv4(C, ipb("239.255.255.250"), 17, udp(C, ipb("239.255.255.250"), 51000, 1900, b"M-SEARCH * HTTP/1.1\r\nHOST:239.255.255.250:1900\r\n\r\n"))))
    # the FTP control stream (port 21) — cleartext USER/PASS
    cp, cseq, sseq = 49200, 1000, 7000
    p.add(eth(True,  ipv4(C, S, 6, tcp(C, S, cp, 21, cseq, 0, 0x02))));            cseq+=1          # SYN
    p.add(eth(False, ipv4(S, C, 6, tcp(S, C, 21, cp, sseq, cseq, 0x12))));        sseq+=1          # SYN-ACK
    p.add(eth(True,  ipv4(C, S, 6, tcp(C, S, cp, 21, cseq, sseq, 0x10))))                           # ACK
    def s2c(d):
        nonlocal sseq
        p.add(eth(False, ipv4(S, C, 6, tcp(S, C, 21, cp, sseq, cseq, 0x18, d)))); sseq+=len(d)
    def c2s(d):
        nonlocal cseq
        p.add(eth(True,  ipv4(C, S, 6, tcp(C, S, cp, 21, cseq, sseq, 0x18, d)))); cseq+=len(d)
    s2c(b"220 ProFTPD 1.3.5 Server (LAN FTP) [192.168.10.9]\r\n")
    c2s(b"USER bmaint\r\n")
    s2c(b"331 Password required for bmaint\r\n")
    c2s(b"PASS Ftp!Lan2026\r\n")
    s2c(b"230 User bmaint logged in\r\n")
    c2s(b"SYST\r\n"); s2c(b"215 UNIX Type: L8\r\n")
    p.write(os.path.join(OUT, "case1_busy_lan_ftp.pcap"))
    return "case1_busy_lan_ftp.pcap", "FTP bmaint / Ftp!Lan2026 (port 21), amid DNS/SSDP noise"

# ---------------------------------------------------------------- case 2: DNS exfiltration
def case2():
    p = Pcap()
    C, RES = ipb("10.1.1.33"), ipb("10.1.1.1")
    secret = b"FLAG{dns_exfil_base32}"
    b32 = base64.b32encode(secret).decode().rstrip("=")     # unpadded base32
    chunks = [b32[i:i+8] for i in range(0, len(b32), 8)]     # 8-char labels
    # a little legit DNS noise first
    for i, nm in enumerate(["www.google.com", "time.cloudflare.com"]):
        p.add(eth(True, ipv4(C, RES, 17, udp(C, RES, 40000+i, 53, dns_query(0x2000+i, nm)))))
    # the exfil: one A query per chunk -> <chunk>.t.evil-exfil.com
    for i, ch in enumerate(chunks):
        name = f"{ch}.t.evil-exfil.com"
        p.add(eth(True, ipv4(C, RES, 17, udp(C, RES, 41000+i, 53, dns_query(0x2100+i, name)))))
    p.write(os.path.join(OUT, "case2_dns_exfil.pcap"))
    return "case2_dns_exfil.pcap", f"secret={secret.decode()} via base32 labels of *.t.evil-exfil.com ({len(chunks)} chunks)"

# ---------------------------------------------------------------- case 3: SSH brute force
def case3():
    p = Pcap()
    A, S = ipb("10.0.0.66"), ipb("10.0.0.10")               # attacker, server
    def attempt(cport, success=False):
        cseq, sseq = 1000, 9000
        p.add(eth(True,  ipv4(A, S, 6, tcp(A, S, cport, 22, cseq, 0, 0x02)))); cseq+=1       # SYN
        p.add(eth(False, ipv4(S, A, 6, tcp(S, A, 22, cport, sseq, cseq, 0x12)))); sseq+=1    # SYN-ACK
        p.add(eth(True,  ipv4(A, S, 6, tcp(A, S, cport, 22, cseq, sseq, 0x10))))             # ACK
        b1 = b"SSH-2.0-OpenSSH_8.9\r\n"; b2 = b"SSH-2.0-libssh2_1.10.0\r\n"
        p.add(eth(False, ipv4(S, A, 6, tcp(S, A, 22, cport, sseq, cseq, 0x18, b1)))); sseq+=len(b1)
        p.add(eth(True,  ipv4(A, S, 6, tcp(A, S, cport, 22, cseq, sseq, 0x18, b2)))); cseq+=len(b2)
        if success:
            # the one that gets in: a longer session (a few more encrypted-looking exchanges) then clean FIN
            for _ in range(6):
                blob = os.urandom(48)
                p.add(eth(True,  ipv4(A, S, 6, tcp(A, S, cport, 22, cseq, sseq, 0x18, blob)))); cseq+=len(blob)
                p.add(eth(False, ipv4(S, A, 6, tcp(S, A, 22, cport, sseq, cseq, 0x18, os.urandom(48))))); sseq+=48
            p.add(eth(True,  ipv4(A, S, 6, tcp(A, S, cport, 22, cseq, sseq, 0x11))))          # FIN-ACK
        else:
            p.add(eth(False, ipv4(S, A, 6, tcp(S, A, 22, cport, sseq, cseq, 0x04))))          # RST (auth fail -> drop)
    n_fail = 14
    for i in range(n_fail):
        attempt(40000 + i)
    attempt(40000 + n_fail, success=True)                    # the 15th connection succeeds
    p.write(os.path.join(OUT, "case3_ssh_bruteforce.pcap"))
    return "case3_ssh_bruteforce.pcap", f"{n_fail+1} connections from 10.0.0.66 to :22; the last (sport {40000+n_fail}) succeeded"

if __name__ == "__main__":
    for fn in (case1, case2, case3):
        name, note = fn()
        print(f"[*] {name}  — {note}")
    print(f"[✓] written to {OUT}")
