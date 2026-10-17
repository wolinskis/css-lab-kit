# Network capture — solution

## 1. Protocol & port
**Command:**
```
tshark -r http_login_cleartext.pcap
```
**Result:** TCP to port **80** carrying **HTTP** — a `POST /login HTTP/1.1`.
**Reason:** port 80 + an HTTP request line = cleartext HTTP.

## 2. Recover the credentials
**Command (either):**
```
# follow the whole TCP conversation as text (easiest — body is readable):
tshark -r http_login_cleartext.pcap -z follow,tcp,ascii,0 -q
# or pull just the HTTP form body (it prints as hex -> decode it):
tshark -r http_login_cleartext.pcap -Y http.request -T fields -e http.file_data | xxd -r -p; echo
```
Wireshark GUI: **File → Open** the pcap → right-click the POST packet → **Follow → TCP Stream**.
**Result:** the request body is
```
username=admin&password=S3ntinel!Admin&tok=1j
```
→ **username `admin`, password `S3ntinel!Admin`**.
**Reason:** the form is submitted over plain HTTP, so the body is visible in the capture.

## 3. (bonus) Target & client
**Command:**
```
tshark -r http_login_cleartext.pcap -Y http.request -T fields -e ip.src -e ip.dst -e http.host -e http.request.uri
```
**Result:** client **192.168.56.10** → server **192.168.56.20**, `Host: portal.vulncorp.local`, URI `/login`.
**Reason:** the IP layer gives the endpoints; the `Host` header + request line give the site and path.

**Fix:** serve logins over HTTPS (TLS); never transmit credentials over HTTP. On a network you don't fully
control, treat any plaintext protocol (HTTP, FTP, Telnet, SMTP without STARTTLS) as compromised.
