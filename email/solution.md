# 📧 Email Forensics — Solution

> 🎯 **Goal:** analyse a phishing email (`phishing.eml`). Confirm from the headers that it's spoofed, trace where it really comes from / leads, and — the main lesson — **recover a secret hidden OUTSIDE the MIME tree that no mail client will ever show you.**

All commands run from the lab folder against the raw file. No Wireshark, no mail client.

## 0 · Validate the artifact first

A truncated download (a mail client "save as", a copy-paste, a parser re-export) silently drops everything after the closing MIME boundary — which is exactly where the secret lives. Always check size and hash before analysing.

```
md5sum phishing.eml
wc -c phishing.eml
```

Expected:

```
51cdac3f55eee4a124babcaca00959c5  phishing.eml
1795 phishing.eml
```

If you have fewer bytes, stop and get the full file — this is a missing-data problem, not a wrong-method problem.

## 🟢 Easy — is this legit? (header / authentication analysis)

**Command**

```
grep -iE '^(from|authentication-results|dkim|received):' phishing.eml
```

**Result**

```
Received: from mail.recipient.local (localhost [127.0.0.1]) by mx.recipient.local with ESMTP id 4F2A1 for <alice@vulncorp.local>; Mon, 02 Jun 2026 08:14:55 +0200
Received: from vulncorp-secure.com (unknown [185.165.240.54]) by mail.recipient.local (Postfix) with ESMTPS id ABCD1 (version=TLS1_2 cipher=ECDHE-RSA-AES128-GCM-SHA256) for <alice@vulncorp.local>; Mon, 02 Jun 2026 08:14:50 +0200
Authentication-Results: mail.recipient.local; spf=fail smtp.mailfrom=vulncorp-secure.com; dkim=none; dmarc=fail
DKIM-Signature: v=1; a=rsa-sha256; d=vulncorp-secure.com; s=sel; t=1780000490; x=1780605290;
From: "VulnCorp IT Support" <it-support@vulncorp-secure.com>
```

**Answer**

| Check | Value | Meaning |
|---|---|---|
| SPF | `spf=fail` | sending IP is not authorised for `vulncorp-secure.com` |
| DKIM | `dkim=none` | a `DKIM-Signature` header is present but no valid signature was verified |
| DMARC | `dmarc=fail` | neither SPF nor DKIM aligned — the domain's policy says reject/quarantine |
| From domain | `vulncorp-secure.com` | **look-alike** of the real `vulncorp.local` (recipient `alice@vulncorp.local`) |

The message fails all three sender checks and the `From:` domain is a typo-squat of the victim's own domain, dressed up as "IT Support" with an `[URGENT]` subject. **Verdict: phishing.**

## 🟡 Medium — where does it really go / come from?

### 1. Where do replies land?

```
grep -i '^reply-to:' phishing.eml
```

```
Reply-To: <credentials@evil-collector.ru>
```

Replies go to `credentials@evil-collector.ru` — an attacker domain, not the `From:` address. A `Reply-To` that differs from `From` is a classic phishing tell; the mailbox name `credentials` says what they are collecting.

### 2. Where does the link really point?

```
grep -oE 'href="[^"]+"' phishing.eml
```

```
href="http://vulncorp-secure.com.evil-collector.ru/login"
```

Read a hostname from the **right**: the registrable domain is `evil-collector.ru`. `vulncorp-secure.com` is just a sub-label the attacker controls inside their own DNS zone, placed on the left so the eye stops reading early. Also plain `http://` — a real login portal would be HTTPS.

### 3. Which IP did it really come from?

`Received` headers are prepended by each hop, so read them **bottom-up**; the last one is the origin hop.

```
grep -i '^received:' phishing.eml | tail -1
grep -oE '\[[0-9.]+\]' phishing.eml
```

```
Received: from vulncorp-secure.com (unknown [185.165.240.54]) by mail.recipient.local (Postfix) with ESMTPS id ABCD1 (version=TLS1_2 cipher=ECDHE-RSA-AES128-GCM-SHA256) ...
[127.0.0.1]
[185.165.240.54]
```

Origin IP = **`185.165.240.54`**. The sender *claimed* (HELO) to be `vulncorp-secure.com`, but Postfix's reverse lookup of the IP returned `unknown` — the name does not resolve back to that address. The hop used TLS 1.2 with an ECDHE cipher (forward secrecy), which says nothing about trust — attackers use TLS too. `127.0.0.1` is just the internal relay.

**Answer:** replies → `credentials@evil-collector.ru`; link → `evil-collector.ru` (`/login` credential harvester); origin IP → `185.165.240.54`.

## 🟠 Hard — don't trust the MIME tree

### Map the structure

```
grep -n -iE '^(mime-version|content-type|content-transfer-encoding|content-disposition)' phishing.eml
grep -n -- '--===============LABKIT==' phishing.eml
```

```
11:MIME-Version: 1.0
12:Content-Type: multipart/mixed; boundary="===============LABKIT=="
15:Content-Type: text/html; charset=UTF-8
20:Content-Type: application/pdf; name="invoice.pdf"
21:Content-Transfer-Encoding: base64
22:Content-Disposition: attachment; filename="invoice.pdf"
14:--===============LABKIT==
19:--===============LABKIT==
26:--===============LABKIT==--
```

So: `multipart/mixed` → part 1 `text/html` (the lure), part 2 `application/pdf` attachment `invoice.pdf` (base64). The boundary `--===============LABKIT==` opens each part; the one ending in `--` (line 26) is the **closing boundary** — the formal end of the message.

### Decode the attachment

MIME puts a **blank line** between a part's headers and its body, so grab the line **two** after `Content-Disposition` (`-A2`), not one — `-A1` would hand you that blank line and decode nothing:

```
grep -A2 '^Content-Disposition' phishing.eml | tail -1 | base64 -d ; echo
# robust alternative (the PDF part is the only base64 starting with the %PDF magic "JVBER"):
grep '^JVBER' phishing.eml | base64 -d ; echo
```

```
%PDF-1.4 decoy invoice - nothing useful here
```

It is not even a real PDF — just a magic header and a sentence. **Red herring.** Spending time on PDF object streams or steganography here is exactly the trap the lab sets.

### Look past the closing boundary

```
sed -n '/^--===============LABKIT==--/,$p' phishing.eml
```

```
--===============LABKIT==--


X-Note: archival blob below (not part of the message)
LS0tLS1CRUdJTiBPUEVOU1NIIFBSSVZBVEUgS0VZLS0tLS0KYjNCbGJuTnphQzFy
WlhrdGRqRUFBQUFBQkc1dmJtVUFBQUFFYm05dVpRQUFBQUFBQUFBQkFBQUFNd0FB
QUF0emMyZ3RaVwpReU5UVXhPUUFBQUNCTEFCS0lUdHJhaW5pbmcwa2V5Tk9UcmVh
bERPVGRlY29kZU1FMDAwMDAwMDAwMDAwMDAwMDAwMDAwPQotLS0tLUVORCBPUEVO
U1NIIFBSSVZBVEUgS0VZLS0tLS0K
```

The same thing shows up with `strings -n 20 phishing.eml | tail -8` or simply `tail -n 8 phishing.eml`.

**Answer:** after the closing boundary there is a fake `X-Note:` line (it is *not* a header — headers only exist before the first blank line) and a 5-line base64 blob. RFC 2046 calls anything after the closing boundary an *epilogue* and says MUA/parsers must ignore it. That is why Thunderbird, Outlook, webmail, `python -m email`, `munpack` and every other MIME-aware tool show you a two-part message and nothing else. Only the raw bytes reveal it.

## 🔴 Expert — recover the hidden key

### Prove what the blob is

Base64 is not a cipher: a fixed ASCII prefix always encodes to a fixed base64 prefix (as long as the alignment is the same, which it is for data starting at offset 0 of the blob). So you can grep for the *encoded* form of a known marker without decoding anything.

```
echo -n '-----BEGIN OPENSSH' | base64
```

```
LS0tLS1CRUdJTiBPUEVOU1NI
```

### Locate it by byte offset

`grep -a` treats the file as text even if it contained binary, `-b` prints the byte offset, `-o` prints only the match, `-F` means fixed string.

```
grep -aboF 'LS0tLS1CRUdJTiBPUEVOU1NI' phishing.eml
```

```
1506:LS0tLS1CRUdJTiBPUEVOU1NI
```

The blob starts at byte 1506 (0-based) of a 1795-byte file — i.e. the last 289 bytes are the appended payload.

### Carve and decode

`tail -c +N` is **1-based** ("start at byte N"), so use offset + 1. `base64 -d` happily skips the newlines.

```
tail -c +1507 phishing.eml | base64 -d
```

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1r...   (two lines of key body)
...
-----END OPENSSH PRIVATE KEY-----
```

A full `-----BEGIN OPENSSH PRIVATE KEY-----` … `-----END OPENSSH PRIVATE KEY-----` block, 4 lines. (The body is a training placeholder: it contains the string `training` and is not a usable key — there is no flag; **recovering this block is the objective.**)

Boundary-driven alternative, no offset arithmetic:

```
sed -n '/^--===============LABKIT==--/,$p' phishing.eml | grep -v -e '^--' -e '^X-Note' | base64 -d
```

Save it to a file and validate the carve:

```
tail -c +1507 phishing.eml | base64 -d > recovered_key.pem
head -1 recovered_key.pem ; tail -1 recovered_key.pem ; wc -l recovered_key.pem
```

```
-----BEGIN OPENSSH PRIVATE KEY-----
-----END OPENSSH PRIVATE KEY-----
4 recovered_key.pem
```

Clean BEGIN/END markers and `base64 -d` exiting 0 confirm the carve started on the right byte; an off-by-one would have produced garbage or an "invalid input" error.

### What you'd do with a recovered key (and why it fails here)

On a real case you would now **use** the key, so these are the reflex next steps:

```
chmod 600 recovered_key.pem            # ssh refuses a world/group-readable key
ssh-keygen -l -f recovered_key.pem     # fingerprint it (match against authorized_keys / IOC lists)
ssh-keygen -y -f recovered_key.pem     # derive the public key
# then: ssh -i recovered_key.pem user@host
```

On **this** lab the last two deliberately fail:

```
ssh-keygen -l -f recovered_key.pem  ->  is not a key file.
ssh-keygen -y -f recovered_key.pem  ->  error in libcrypto: unsupported
```

That is expected, not a mistake. The block is correctly *shaped* (so `base64 -d` succeeds and it looks real), but the key material is the placeholder `KITtraining0keyNOTrealDOTdecodeME…`, so OpenSSH will not load it. There is nothing to connect to — recovering the block was the whole objective.

### The lesson

**Data appended outside the MIME tree is invisible to parsers and mail clients.** The rendered view is what the *attacker* wants you to see; the raw bytes are what is actually there. Always `grep -a` / `strings` / `tail` the raw file, and check that the file size matches what you were given.

Triage order for a hidden artifact (key, payload, flag, IOC) in an email — cheap and structural first:

1. **Validate completeness** — size, hash, expected marker. A truncated copy hides everything.
2. **Walk the boundaries** — count openers vs the closer, check what lies after the closing boundary.
3. **Grep the raw bytes** — `-----BEGIN`, its base64 `LS0tLS1CRUdJTi…`, `MZ`, `PK`, `ssh-rsa`/`ssh-ed25519`, long base64 runs (`grep -aboE '[A-Za-z0-9+/=]{60,}'`).
4. **Read the headers** — `Received` chain (origin IP, per-hop TLS), `Authentication-Results`, `Reply-To`, link targets; check each part's declared `Content-Type` against its real magic bytes (`file`).
5. **Last resort, only if asked** — steganography on images, PDF object internals. Here the PDF is a decoy, and that is the point.

## Gotchas you'll actually hit

- **`sed -n '/…/,$p'` won't run → use single quotes.** In `,$p` the `$` means "last line" to `sed`, but inside *double* quotes the shell expands `$p` to nothing and `sed` errors with ``unexpected `,'``. Single quotes `'…'` pass it through untouched. (A copy-paste that turns `'` into a curly `'` breaks it the same way — retype plain quotes.) To skip the ugly boundary string entirely, carve by offset instead: `tail -c +1507 phishing.eml | base64 -d`.
- **Decoding the attachment returns nothing → use `-A2`, not `-A1`.** MIME puts a blank line between a part's headers and its body, so `grep -A1 '^Content-Disposition' | tail -1` lands on that blank line and decodes nothing. `-A2` reaches the base64 — or grab it by its magic prefix: `grep '^JVBER' phishing.eml | base64 -d`.
- **"No such file" → you're in the wrong directory.** `cd` to where `phishing.eml` lives and confirm with `ls phishing.eml` before running anything.

## Answer sheet

| # | Question | Answer |
|---|---|---|
| 🟢 | Legit? | No — `spf=fail`, `dkim=none`, `dmarc=fail`; `From` domain `vulncorp-secure.com` is a look-alike of `vulncorp.local` |
| 🟡 | Replies go to | `credentials@evil-collector.ru` |
| 🟡 | Link really points to | `evil-collector.ru` (`http://vulncorp-secure.com.evil-collector.ru/login`) |
| 🟡 | Origin IP | `185.165.240.54` (reverse DNS `unknown`) |
| 🟠 | Attachment | `invoice.pdf` = `%PDF-1.4 decoy invoice - nothing useful here` — red herring |
| 🟠 | After closing boundary | fake `X-Note:` + 5-line base64 blob (MIME epilogue, never rendered) |
| 🔴 | Hidden secret | OpenSSH private key block at byte offset 1506: `tail -c +1507 phishing.eml \| base64 -d` |
| 🔴 | Lesson | parsers stop at the closing boundary — inspect raw bytes, never trust the rendered view |

Back to [`README.md`](README.md) · [`index.html`](index.html) · Hub: [`../index.html`](../index.html)

⚠️ **Intentionally vulnerable — training only.** Synthetic headers, fake domains/IPs, placeholder key.
