# 📧 Email Forensics — Lab Kit

> 🎯 **Goal:** analyse a phishing email (`phishing.eml`). Confirm from the headers that it's spoofed, trace where it really comes from / leads, and — the main lesson — **recover a secret hidden OUTSIDE the MIME tree that no mail client will ever show you.**

Command-line only: `grep`, `base64`, `strings`, `head`/`tail`, `sed` and an editor on the raw file. **Not** Wireshark, not a mail client. Work from this folder.

## Files

| File | What it is |
|---|---|
| `phishing.eml` | the artifact — 1795 bytes, md5 `51cdac3f55eee4a124babcaca00959c5` |
| `README.md` | this task sheet |
| `solution.md` | full walkthrough with every command and its output |
| `index.html` | landing page (hints per level) |

Check the file before you start — a truncated copy hides the whole point of level 4:

```
md5sum phishing.eml
wc -c phishing.eml
```

## Levels

### 🟢 Easy — is this legit? (header / authentication analysis)

Read the headers only. Does the message pass SPF / DKIM / DMARC? Compare the `From:` domain with the recipient's real domain (`vulncorp.local`). Give a verdict.

```
grep -iE '^(from|authentication-results|dkim|received):' phishing.eml
```

### 🟡 Medium — where does it really go / come from?

1. Where do replies actually land?
2. Where does the "Reverify" link really point — what is the registrable domain?
3. Which IP handed the message to our mail server (first external hop in the `Received` chain)?

### 🟠 Hard — don't trust the MIME tree

Map the MIME structure (parts, content types), decode the attachment and say what it really is. Then look **past the closing boundary**: is the file really finished where the message ends?

### 🔴 Expert — recover the hidden key

Prove what the appended blob is, find it by byte offset, carve it out and decode it. There is no flag: **recovering the key is the objective.** State the lesson in one sentence.

## Triage order (the thing to remember)

For a hidden artifact in an email: **validate the file → walk the boundaries → grep the raw bytes → read the headers**. Steganography on images and PDF internals are last resorts, not first moves — here the PDF is a decoy.

Answers: [`solution.md`](solution.md) · Hub: [`../index.html`](../index.html)

⚠️ **Intentionally vulnerable — training only.** Synthetic headers, fake domains/IPs, placeholder key.
