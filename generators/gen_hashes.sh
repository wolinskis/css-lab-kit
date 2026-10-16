#!/usr/bin/env bash
# Hash artifacts for the cracking labs (all crack from rockyou).
set -e; cd "$(dirname "$0")/.."; OUT="artifacts/hashes"; mkdir -p "$OUT"; cd "$OUT"
# md5 of 'password', sha1 of 'dragon', bcrypt + apr1 of 'summer' (openssl), a shadow-style sha512
printf 'user1:%s\n' "$(printf 'password' | md5sum | awk '{print $1}')" > md5.txt
printf 'user2:%s\n' "$(printf 'dragon' | sha1sum | awk '{print $1}')" > sha1.txt
printf 'boss:%s\n' "$(openssl passwd -apr1 -salt F8WGX6n9 summer)" > apr1_htpasswd.txt
printf 'admin:%s\n' "$(openssl passwd -6 -salt labsalt hunter2)" > sha512_shadow.txt
echo "[*] hash artifacts: $(ls | tr '\n' ' ')  (passwords: password, dragon, summer, hunter2)"
