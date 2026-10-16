#!/usr/bin/env python3
"""Generate a phishing .eml for the Email Investigation labs.
Artifacts: a spoofed/suspicious email with a full Received chain, a decoy PDF part,
and an OpenSSH private key base64-blob APPENDED OUTSIDE the MIME tree (a MIME parser
never sees it) — mirroring the real 'hidden key in raw bytes' exam lesson."""
import base64, os, textwrap

HERE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT  = os.path.join(HERE, "artifacts", "email"); os.makedirs(OUT, exist_ok=True)

boundary = "===============LABKIT=="
headers = f"""\
Return-Path: <it-support@vulncorp-secure.com>
Received: from mail.recipient.local (localhost [127.0.0.1]) by mx.recipient.local with ESMTP id 4F2A1 for <alice@vulncorp.local>; Mon, 02 Jun 2026 08:14:55 +0200
Received: from vulncorp-secure.com (unknown [185.165.240.54]) by mail.recipient.local (Postfix) with ESMTPS id ABCD1 (version=TLS1_2 cipher=ECDHE-RSA-AES128-GCM-SHA256) for <alice@vulncorp.local>; Mon, 02 Jun 2026 08:14:50 +0200
Authentication-Results: mail.recipient.local; spf=fail smtp.mailfrom=vulncorp-secure.com; dkim=none; dmarc=fail
DKIM-Signature: v=1; a=rsa-sha256; d=vulncorp-secure.com; s=sel; t=1780000490; x=1780605290;
From: "VulnCorp IT Support" <it-support@vulncorp-secure.com>
Reply-To: <credentials@evil-collector.ru>
To: alice@vulncorp.local
Subject: [URGENT] Your mailbox will be disabled - reverify now
Date: Mon, 02 Jun 2026 08:14:48 +0200
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="{boundary}"
"""

body = f"""\
--{boundary}
Content-Type: text/html; charset=UTF-8

<p>Dear user, your mailbox has exceeded its quota. <a href="http://vulncorp-secure.com.evil-collector.ru/login">Reverify immediately</a> or access will be suspended.</p>

--{boundary}
Content-Type: application/pdf; name="invoice.pdf"
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="invoice.pdf"

{base64.b64encode(b'%PDF-1.4 decoy invoice - nothing useful here').decode()}

--{boundary}--
"""

# OpenSSH private key, base64-encoded, appended AFTER the closing boundary (outside MIME).
key = ("-----BEGIN OPENSSH PRIVATE KEY-----\n"
       "b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW\n"
       "QyNTUxOQAAACBLABKITtraining0keyNOTrealDOTdecodeME000000000000000000000=\n"
       "-----END OPENSSH PRIVATE KEY-----\n")
b64 = base64.b64encode(key.encode()).decode("ascii")
b64_wrapped = "\n".join(b64[i:i+64] for i in range(0, len(b64), 64))   # clean 64-col base64
appended = "\n\nX-Note: archival blob below (not part of the message)\n" + b64_wrapped

with open(os.path.join(OUT, "phishing.eml"), "w") as f:
    f.write(headers + "\n" + body + appended + "\n")

print("[*] email artifact: artifacts/email/phishing.eml  (hidden OpenSSH key appended outside the MIME tree)")
