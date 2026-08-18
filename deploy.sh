#!/bin/bash
#
# Deploy saybaewstudio.com to its Cloudways app.
#
#   ./deploy.sh --dry-run   show what would be sent, send nothing
#   ./deploy.sh             lint, rsync, verify
#
# This script is never itself deployed — *.sh is in the exclude list below.
#
# BEFORE THE FIRST RUN, set APP_ID to the Cloudways application id for
# saybaewstudio. It is deliberately left blank so a mistyped run cannot
# overwrite one of the other four sites on the same server.
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
if ssh "$SSH_HOST" "test -f $TARGET/index.php"; then
  if ! ssh "$SSH_HOST" "grep -q -m1 saybaew $TARGET/index.php"; then
    echo "STOP: $TARGET/index.php ไม่มีคำว่า saybaew — อาจเป็นแอปของเว็บอื่น"
    exit 1
  fi
  echo "   ok — ปลายทางเป็น saybaewstudio"
else
  echo "   (ยังไม่มี index.php บนเซิร์ฟเวอร์ — ถือว่าเป็นการ deploy ครั้งแรก)"
fi

# ---------------------------------------------------------------- 3. rsync ---
# config.php holds the production database credentials and uploads/ holds the
# customers' photographs — neither may ever be overwritten from a laptop.
# *.md / *.sh / docs / tools stay out of the web root because nginx serves them
# to anyone who asks, and .htaccess cannot stop it on Cloudways.
echo "→ ส่งไฟล์ขึ้นเซิร์ฟเวอร์..."
rsync -az $DRY --itemize-changes \
  --exclude '.git' \
  --exclude '.claude' \
  --exclude 'config.php' \
  --exclude 'uploads' \
  --exclude '*.sqlite' \
  --exclude '*.sqlite-wal' \
  --exclude '*.sqlite-shm' \
  --exclude '.DS_Store' \
  --exclude '*.md' \
  --exclude 'docs' \
  --exclude '*.sh' \
  --exclude 'tools' \
  ./ "$SSH_HOST:$TARGET/"

[[ -n "$DRY" ]] && { echo "(dry run — ไม่ได้ส่งอะไรขึ้นจริง)"; exit 0; }

# --------------------------------------------------------------- 4. verify ---
echo "→ ตรวจว่าเอกสารไม่หลุดขึ้นเว็บ..."
leaked=$(ssh "$SSH_HOST" "ls $TARGET/*.md $TARGET/*.sh $TARGET/docs 2>/dev/null | head -5" || true)
if [[ -n "$leaked" ]]; then
  echo "   ⚠ พบไฟล์ที่ไม่ควรอยู่บนเซิร์ฟเวอร์:"
  echo "$leaked"
  echo "   ลบด้วย: ssh $SSH_HOST 'rm -rf $TARGET/docs $TARGET/*.md $TARGET/*.sh'"
else
  echo "   ok"
fi

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
