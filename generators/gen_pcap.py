#!/usr/bin/env python3
"""Generate a small PCAP for the Network Analysis labs (no scapy needed).
Contains a cleartext HTTP POST /login with credentials -> practise 'Follow TCP Stream'
in Wireshark / `tshark -z follow,tcp,ascii`. Valid Ethernet/IPv4/TCP with real checksums."""
import os, struct, time

HERE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT  = os.path.join(HERE, "artifacts", "network"); os.makedirs(OUT, exist_ok=True)
PATH = os.path.join(OUT, "http_login_cleartext.pcap")

CMAC = bytes.fromhex("0c0c0c000001"); SMAC = bytes.fromhex("0c0c0c000002")
CIP  = bytes(map(int,"192.168.56.10".split("."))); SIP = bytes(map(int,"192.168.56.20".split(".")))
CPORT, SPORT = 49152, 80

def csum(d):
    if len(d)%2: d+=b"\x00"
    s=sum(struct.unpack("!%dH"%(len(d)//2),d)); s=(s>>16)+(s&0xffff); s+=s>>16
    return (~s)&0xffff

def ip(payload):
    hdr=struct.pack("!BBHHHBBH4s4s",0x45,0,20+len(payload),0x1234,0x4000,64,6,0,CIP if ip.dir else SIP, SIP if ip.dir else CIP)
    chk=csum(hdr); hdr=hdr[:10]+struct.pack("!H",chk)+hdr[12:]
    return hdr+payload
ip.dir=1  # 1 = client->server

def tcp(seq,ack,flags,data=b""):
    src,dst=(CPORT,SPORT) if ip.dir else (SPORT,CPORT)
    sip,dip=(CIP,SIP) if ip.dir else (SIP,CIP)
    off=(5<<4)
    hdr=struct.pack("!HHIIBBHHH",src,dst,seq,ack,off,flags,8192,0,0)
    pseudo=struct.pack("!4s4sBBH",sip,dip,0,6,len(hdr)+len(data))
    chk=csum(pseudo+hdr+data); hdr=hdr[:16]+struct.pack("!H",chk)+hdr[18:]
    return hdr+data

def eth(payload):
    d,s=(CMAC,SMAC) if ip.dir else (SMAC,CMAC)  # wait: dst,src
    return (SMAC if ip.dir else CMAC)+(CMAC if ip.dir else SMAC)+b"\x08\x00"+payload

def frame(seq,ack,flags,data=b""):
    return eth(ip(tcp(seq,ack,flags,data)))

post=(b"POST /login HTTP/1.1\r\nHost: portal.vulncorp.local\r\n"
      b"Content-Type: application/x-www-form-urlencoded\r\nContent-Length: 42\r\n\r\n"
      b"username=admin&password=S3ntinel!Admin&tok=1")
resp=b"HTTP/1.1 200 OK\r\nContent-Length: 2\r\n\r\nOK"

pkts=[]
ip.dir=1; pkts.append(frame(1000,0,0x02))                       # SYN
ip.dir=0; pkts.append(frame(5000,1001,0x12))                    # SYN-ACK
ip.dir=1; pkts.append(frame(1001,5001,0x10))                    # ACK
ip.dir=1; pkts.append(frame(1001,5001,0x18,post))               # PSH-ACK (the creds)
ip.dir=0; pkts.append(frame(5001,1001+len(post),0x18,resp))     # server reply

with open(PATH,"wb") as f:
    f.write(struct.pack("!IHHiIII",0xa1b2c3d4,2,4,0,0,65535,1))  # pcap global header (big-endian)
    t=int(time.time())
    for i,p in enumerate(pkts):
        f.write(struct.pack("!IIII",t,i*1000,len(p),len(p))+p)
print(f"[*] network artifact: artifacts/network/http_login_cleartext.pcap  ({len(pkts)} packets, creds in the POST)")
