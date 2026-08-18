#!/bin/bash
#
# Fallback deploy for saybaewstudio.com.
#
# The normal route is now Cloudways pulling from GitHub — see DEPLOY.md. This
# script stays for the times that is not an option: the panel is down, a change
# has to go out before it is committed, or a git deployment needs undoing fast.
#
#   ./deploy.sh --dry-run   show what would be sent, send nothing
#   ./deploy.sh             lint, rsync, verify
#
# Set APP_ID first. It is deliberately blank so a mistyped run cannot overwrite
# one of the other sites sharing this server:
#
#   xaxvhfthsr = tobwai      hrzbghjjrd = sorndekcoding
#   dxmjbjbqrf = laaklaai    xpbtsfxngq = maeranie2022
#
set -euo pipefail

APP_ID=""
SSH_HOST="sorndek"
LIVE_URL="https://saybaewstudio.com/"

cd "$(dirname "$0")"
DRY=""
[[ "${1:-}" == "--dry-run" ]] && DRY="--dry-run"

if [[ -z "$APP_ID" ]]; then
  echo "STOP: APP_ID ยังว่างอยู่"
  echo "เปิด deploy.sh แล้วใส่ Cloudways application id ของ saybaewstudio ก่อน"
  echo "ห้ามเดา — id ผิดหมายถึงการทับเว็บไซต์อื่นที่อยู่บนเซิร์ฟเวอร์เดียวกัน"
  exit 1
fi

TARGET="applications/$APP_ID/public_html"

# ---------------------------------------------------------------- 1. lint ---
echo "→ ตรวจไวยากรณ์ PHP ทุกไฟล์..."
fail=0
while IFS= read -r f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "   ✗ $f"
    php -l "$f" 2>&1 | head -3
    fail=1
  fi
done < <(find . -name '*.php' -not -path './.git/*' -not -path './.claude/*')
[[ $fail -eq 1 ]] && { echo "STOP: มีไฟล์ PHP ที่ syntax ผิด ยังไม่ deploy"; exit 1; }
echo "   ok"

# ----------------------------------------------- 2. confirm the target app ---
echo "→ ยืนยันว่าปลายทางคือ saybaewstudio..."
if ! ssh "$SSH_HOST" "test -d $TARGET"; then
  echo "STOP: ไม่พบโฟลเดอร์ $TARGET บนเซิร์ฟเวอร์"
  exit 1
fi
if ssh "$SSH_HOST" "test -f $TARGET/public/index.php"; then
  if ! ssh "$SSH_HOST" "grep -q -m1 saybaew $TARGET/public/index.php"; then
    echo "STOP: $TARGET/public/index.php ไม่มีคำว่า saybaew — อาจเป็นแอปของเว็บอื่น"
    exit 1
  fi
  echo "   ok — ปลายทางเป็น saybaewstudio"
else
  echo "   (ยังไม่มี public/index.php บนเซิร์ฟเวอร์ — ถือว่าเป็นการ deploy ครั้งแรก)"
fi

# ---------------------------------------------------------------- 3. rsync ---
# The whole repository goes up, including docs/ and *.md. That is safe now: the
# app's webroot is public_html/public, so nginx cannot reach anything above it.
# Only two things must never be overwritten from a laptop — the production
# credentials and the customers' photographs.
echo "→ ส่งไฟล์ขึ้นเซิร์ฟเวอร์..."
rsync -az $DRY --itemize-changes \
  --exclude '.git' \
  --exclude '.claude' \
  --exclude 'config.php' \
  --exclude 'public/uploads' \
  --exclude '*.sqlite' \
  --exclude '*.sqlite-wal' \
  --exclude '*.sqlite-shm' \
  --exclude '.DS_Store' \
  ./ "$SSH_HOST:$TARGET/"

[[ -n "$DRY" ]] && { echo "(dry run — ไม่ได้ส่งอะไรขึ้นจริง)"; exit 0; }

# --------------------------------------------------------------- 4. verify ---
# The webroot boundary is the only thing keeping docs off the web, so check it
# rather than trusting it.
echo "→ ตรวจว่าเอกสารเปิดจากภายนอกไม่ได้..."
leak=0
for path in CLAUDE.md README.md deploy.sh config.php lib.php docs/ICON-PROMPTS.md; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "${LIVE_URL}${path}?cb=$RANDOM" || echo 000)
  if [[ "$code" == "200" ]]; then
    echo "   ⚠ /$path เปิดอ่านได้จากภายนอก (HTTP 200)"
    leak=1
  fi
done
if [[ $leak -eq 1 ]]; then
  echo "   STOP: webroot ของแอปยังไม่ได้ชี้ไปที่ public/"
  echo "   แก้ที่ Cloudways → Application Settings → Webroot = public"
  exit 1
fi
echo "   ok — เอกสารอยู่นอก webroot"

echo "→ ตรวจว่าเว็บยังตอบ 200..."
code=$(curl -s -o /tmp/sbs-deploy-check -w '%{http_code}' "${LIVE_URL}?cachebust=$RANDOM" || echo 000)
echo "   HTTP $code"
if [[ "$code" == "200" ]]; then
  # A page that throws still returns 200, so look at the body too.
  if grep -qiE 'Fatal error|Parse error|Uncaught' /tmp/sbs-deploy-check; then
    echo "   ⚠ ได้ 200 แต่ในหน้ามี PHP fatal error — เปิดดูเว็บทันที"
    exit 1
  fi
  echo "   ok — เว็บทำงานปกติ"
else
  echo "   ⚠ เว็บไม่ตอบ 200 — ตรวจสอบทันที"
  exit 1
fi

rm -f /tmp/sbs-deploy-check
echo
echo "เสร็จแล้ว — $LIVE_URL"
