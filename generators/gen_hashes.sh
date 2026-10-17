#!/usr/bin/env bash
# Hash artifacts for the cracking labs. The point is to practice HASH IDENTIFICATION + the right
# cracking mode, across several algorithms with a realistic effort gradient (legacy = instant,
# bcrypt = slow by design). Every password is in rockyou, so nothing is impossible — just give the
# slow ones time. Identify first (`hashid '<hash>'` / `hash-identifier`), then crack:
#
#   file                 algorithm            hashcat        john                 password
#   md5.txt              MD5 (unsalted)       -m 0           raw-md5              password
#   sha1.txt             SHA-1 (unsalted)     -m 100         raw-sha1             dragon
#   sha256.txt           SHA-256 (unsalted)   -m 1400        raw-sha256           princess
#   md5_salted.txt       md5(pass.salt)       -m 10 (h:salt) dynamic              baseball
#   ntlm.txt             NTLM (MD4,UTF-16LE)  -m 1000        nt                   welcome
#   bcrypt.txt           bcrypt (cost 8)      -m 3200        bcrypt               football   <- the slow one
#   apr1_htpasswd.txt    apr1 (md5crypt)      -m 1600        md5crypt             summer
#   sha512_shadow.txt    sha512crypt ($6$)    -m 1800        sha512crypt          hunter2
#   mystery.txt          UNLABELED mix        (identify them yourself — that's the drill)
#
set -e; cd "$(dirname "$0")/.."; OUT="artifacts/hashes"; mkdir -p "$OUT"; cd "$OUT"

md5()    { printf '%s' "$1" | md5sum  | awk '{print $1}'; }
sha1()   { printf '%s' "$1" | sha1sum | awk '{print $1}'; }
sha256() { printf '%s' "$1" | sha256sum | awk '{print $1}'; }
# NTLM = MD4(UTF-16LE(password)); openssl md4 works on Kali (fallback: python hashlib md4)
ntlm()   { printf '%s' "$1" | iconv -t utf-16le | openssl dgst -md4 | awk '{print $NF}'; }
# bcrypt via PHP (lab already depends on php); cost controls how slow it is to crack
bcrypt() { php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT, ["cost"=>(int)$argv[2]]);' "$1" "$2"; }

printf 'user1:%s\n'  "$(md5 password)"                 > md5.txt
printf 'user2:%s\n'  "$(sha1 dragon)"                  > sha1.txt
printf 'user3:%s\n'  "$(sha256 princess)"              > sha256.txt
printf 'user4:%s:%s\n' "$(md5 'baseballKx7p')" "Kx7p"  > md5_salted.txt     # md5(pass.salt), format hash:salt
printf 'user5:%s\n'  "$(ntlm welcome)"                 > ntlm.txt
printf 'svc:%s\n'    "$(bcrypt football 8)"            > bcrypt.txt
printf 'boss:%s\n'   "$(openssl passwd -apr1 -salt F8WGX6n9 summer)" > apr1_htpasswd.txt
printf 'admin:%s\n'  "$(openssl passwd -6 -salt labsalt hunter2)"    > sha512_shadow.txt

# a hashid drill: the same algorithms with NO labels and NO usernames — identify each one yourself
{ md5 monkey; sha256 iloveyou; ntlm sunshine; sha1 superman; } > mystery.txt

echo "[*] hash artifacts: $(ls | tr '\n' ' ')"
echo "    passwords: password dragon princess baseball welcome football summer hunter2  (mystery: monkey iloveyou sunshine superman)"
