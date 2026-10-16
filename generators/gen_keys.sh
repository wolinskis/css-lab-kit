#!/usr/bin/env bash
# SSH / private-key artifacts (key-handling + email-hidden-key labs).
set -e; cd "$(dirname "$0")/.."; OUT="artifacts/keys"; mkdir -p "$OUT"; cd "$OUT"
rm -f id_rsa* id_ed25519* encrypted_rsa*
ssh-keygen -t ed25519 -N "" -C "kristin@labkit" -f id_ed25519 >/dev/null
ssh-keygen -t rsa -b 2048 -N "" -C "svc@labkit" -f id_rsa >/dev/null
ssh-keygen -t rsa -b 2048 -N "Summer2024!" -C "locked@labkit" -f encrypted_rsa >/dev/null
echo "[*] key artifacts: $(ls | tr '\n' ' ')"
