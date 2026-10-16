#!/usr/bin/env bash
# Materialise every lab artifact into artifacts/ . Run once after cloning.
set -e; cd "$(dirname "$0")"
echo "[*] Generating lab artifacts..."
bash   generators/gen_certs.sh
bash   generators/gen_keys.sh
bash   generators/gen_hashes.sh
python3 generators/gen_email.py
python3 generators/gen_data.py
python3 generators/gen_pcap.py
echo; echo "[✓] Artifacts ready under artifacts/ :"; find artifacts -type f | sed 's|^|    |' | sort
echo; echo "Start the vulnerable web app:  ./webapp/run.sh   →  http://localhost:8000  (admin / S3ntinel!Admin)"
