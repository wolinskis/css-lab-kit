#!/usr/bin/env bash
# Start VulnNexus (app 2) with its own throwaway MariaDB. http://localhost:8001
set -e; cd "$(dirname "$0")"
WEBPORT="${1:-8001}"
DATADIR="$PWD/.mariadb-data2"; SOCK="/tmp/vulnnexus-$(id -u).sock"; export VN_DB_SOCK="$SOCK"
command -v mariadbd >/dev/null || { echo "[!] need mariadb-server: sudo apt install mariadb-server"; exit 1; }
php -m | grep -qi "^dom$" || echo "[!] XXE lab (import.php) needs php-xml: sudo apt update && sudo apt install -y php-xml (run apt update first, then restart this script)"
[ -d "$DATADIR/mysql" ] || { echo "[*] init throwaway MariaDB..."; mariadb-install-db --datadir="$DATADIR" --auth-root-authentication-method=normal >/dev/null 2>&1; }
mariadbd --datadir="$DATADIR" --socket="$SOCK" --skip-networking --pid-file="$PWD/.mariadb2.pid" >/tmp/vn_mariadb.log 2>&1 &
MPID=$!; trap 'kill "$MPID" 2>/dev/null; wait "$MPID" 2>/dev/null' EXIT INT TERM
for i in $(seq 1 40); do mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1 && break; sleep 0.5; done
echo "[*] VulnNexus → http://localhost:$WEBPORT   (login alice/alice123)  Ctrl+C to stop"
PHP_CLI_SERVER_WORKERS=4 php -S 0.0.0.0:"$WEBPORT" ../labkit_router.php
