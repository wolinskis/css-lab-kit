# Network capture — recover a cleartext login

A self-contained **offline** lab: a small packet capture from a flat network. No server needed — open
it in **Wireshark** or read it with **`tshark`**. Work each task first; `solution.md` has the commands.

**File:** `http_login_cleartext.pcap` (5 packets: TCP handshake → HTTP `POST /login` → response).

## Tasks

**1. What application protocol carries the login, and on which port?**
<details><summary>💡 hint</summary>

`tshark -r http_login_cleartext.pcap` lists the packets; look at the destination port and the HTTP line.
</details>

**2. Recover the submitted username and password.**
The credentials are sent in the clear in the request body — reassemble the conversation and read them.
<details><summary>💡 hint</summary>

Wireshark: right-click a packet → **Follow → TCP Stream**. Or `tshark ... -z follow,tcp,ascii,0`.
The body looks like `username=…&password=…`.
</details>

**3. (bonus) Which host/endpoint was targeted, and from which client IP?**
<details><summary>💡 hint</summary>

The `Host:` header and the IP/TCP layer give you server name/IP, client IP, and the URL path.
</details>

**Lesson:** HTTP (port 80) is plaintext — anyone on-path (same LAN, a rogue AP, a span port) reads the
credentials. This is why login must be over **HTTPS/TLS**.

---
> ⚠️ Training only — synthetic capture with lab credentials.
