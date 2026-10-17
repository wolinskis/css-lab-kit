#!/usr/bin/env bash
# Regenerate the PFS/ECDHE variant of the TLS decryption lab:
#   portal_pfs.pcap  — a real TLS 1.2 ECDHE session (forward secrecy) on loopback
#   portal_pfs.keys  — the client's SSLKEYLOGFILE (the ONLY thing that decrypts it)
# It reuses the lab's existing server.crt/server.key on purpose: the SAME key that cracks the
# static-RSA capture is useless here, because ECDHE never puts the key material on the wire.
# Needs: openssl, curl (OpenSSL backend), dumpcap with cap_net_raw (default on Kali). No install.
set -e
LAB="$(cd "$(dirname "$0")/.."/tls_decrypt_lab && pwd)"
PORT="${1:-8444}"
WORK="$(mktemp -d)"; trap 'rm -rf "$WORK"' EXIT
cp "$LAB/server.crt" "$LAB/server.key" "$WORK/"
cd "$WORK"

cat > portal.html <<'HTML'
HTTP/1.0 200 OK
Content-Type: text/html

<html><body>
<h1>Lab Widgets AG - Internal Portal (TLS 1.2, ECDHE)</h1>
<p>Welcome back, administrator.</p>
<!-- VPN recovery code: FLAG{ecdhe_pfs_needs_the_sslkeylogfile} -->
<p>Database connection string: Server=db02;Uid=svc_portal;Pwd=Winter#2026;</p>
</body></html>
HTML

dumpcap -i lo -f "tcp port $PORT" -w "$WORK/portal_pfs.pcap" >/dev/null 2>&1 &
CAP=$!; sleep 1
openssl s_server -quiet -accept "$PORT" -cert server.crt -key server.key \
  -tls1_2 -cipher ECDHE-RSA-AES128-GCM-SHA256 -HTTP >/dev/null 2>&1 &
SRV=$!; sleep 1
SSLKEYLOGFILE="$WORK/portal_pfs.keys" curl -s -k --tlsv1.2 \
  --ciphers ECDHE-RSA-AES128-GCM-SHA256 \
  -H "Authorization: Basic $(printf 'admin:Winter#2026' | base64)" \
  -H "Cookie: SESSIONID=aa11bb22; role=admin" \
  "https://localhost:$PORT/portal.html" >/dev/null 2>&1 || true
sleep 1
kill "$SRV" 2>/dev/null || true; sleep 0.5; kill -INT "$CAP" 2>/dev/null || true; wait 2>/dev/null || true

install -m 0644 "$WORK/portal_pfs.pcap" "$LAB/portal_pfs.pcap"
install -m 0644 "$WORK/portal_pfs.keys" "$LAB/portal_pfs.keys"
cipher=$(tshark -r "$LAB/portal_pfs.pcap" -Y "tls.handshake.type==2" -T fields -e tls.handshake.ciphersuite 2>/dev/null)
echo "[*] wrote portal_pfs.pcap (cipher $cipher = ECDHE) + portal_pfs.keys"
echo "    verify:  tshark -r portal_pfs.pcap -o 'tls.keylog_file:portal_pfs.keys' -q -z follow,tls,ascii,0"
