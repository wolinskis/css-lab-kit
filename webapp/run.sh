#!/usr/bin/env bash
# Start the VulnCorp training app with its own throwaway MariaDB (no system service,
# no root config). Visit http://localhost:8000 . Ctrl+C stops both.
set -e
cd "$(dirname "$0")"
WEBPORT="${1:-8000}"
DATADIR="$PWD/.mariadb-data"; SOCK="/tmp/vulncorp-$(id -u).sock"; export VC_DB_SOCK="$SOCK"

command -v mariadbd >/dev/null || { echo "[!] mariadb-server not found: sudo apt install mariadb-server"; exit 1; }

if [ ! -d "$DATADIR/mysql" ]; then
  echo "[*] initialising throwaway MariaDB..."
  mariadb-install-db --datadir="$DATADIR" --auth-root-authentication-method=normal >/dev/null 2>&1
fi
echo "[*] starting MariaDB (socket only)..."
mariadbd --datadir="$DATADIR" --socket="$SOCK" --skip-networking \
         --pid-file="$PWD/.mariadb.pid" >/tmp/vc_mariadb.log 2>&1 &
MPID=$!
trap 'kill "$MPID" 2>/dev/null; wait "$MPID" 2>/dev/null' EXIT INT TERM
for i in $(seq 1 40); do mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1 && break; sleep 0.5; done

echo "[*] VulnCorp Intranet → http://localhost:$WEBPORT   (admin / S3ntinel!Admin)  Ctrl+C to stop"
PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:"$WEBPORT" ../labkit_router.php
