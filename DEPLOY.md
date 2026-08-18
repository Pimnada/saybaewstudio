# วิธี deploy saybaewstudio.com

**push ขึ้น GitHub → Cloudways ดึงจาก GitHub** — ไม่ต้อง rsync จากเครื่องอีก

---

## ทำไมโครงสร้างต้องเป็น `public/`

Cloudways git deployment จะ **clone ทั้ง repo** ลงไปที่ `public_html` ถ้า webroot
ชี้ที่นั่นตรง ๆ ไฟล์อย่าง `CLAUDE.md`, `README.md`, `deploy.sh`, `docs/` จะเปิดอ่าน
ได้จากอินเทอร์เน็ตทันที — และ `.htaccess` ห้ามไม่ได้ เพราะ Cloudways ตั้ง
`AllowOverride None`

เรื่องนี้เกิดขึ้นจริงกับ tobwai.com เมื่อ 11 ส.ค. 2569 (`/CLAUDE.md`, `/docs/*.md`,
`/cron-backup.sh` อ่านได้จากภายนอก) และตอนตรวจวันที่ 18 ส.ค.
`maeranie2022.com/README.md` ก็ยังเปิดอ่านได้อยู่

โครงสร้างของ repo นี้จึงแยกเป็นสองชั้น

```
saybaewstudio/            ← Cloudways clone มาลงที่นี่ (public_html)
├── public/               ← webroot จริง — nginx เห็นแค่ในนี้
│   ├── index.php, admin*.php, api-*.php, dl.php, sitemap.php, robots.txt
│   ├── assets/           ← css, js, ไอคอน
│   ├── uploads/          ← รูปลูกค้า
│   └── .user.ini         ← ลิมิตอัปโหลดของ PHP-FPM
├── lib.php, auth.php, db.php, image.php, mailer.php, seed.php
├── inc/, emails/         ← include เท่านั้น ไม่ถูกเรียกตรง
├── docs/, tools/, *.md, deploy.sh
├── config.php            ← gitignored — สร้างบนเซิร์ฟเวอร์
└── dev.sqlite            ← gitignored — เครื่องพัฒนาเท่านั้น
```

nginx ชี้ที่ `public/` เท่านั้น จึงไม่มีทางเสิร์ฟไฟล์ที่อยู่สูงกว่านั้นได้
และ `../` ใน URL ถูก nginx normalize ก่อนแปลงเป็น path อยู่แล้ว

> **หมายเหตุตอนทดสอบบนเครื่อง:** `php -S` จะ fallback ไปเรนเดอร์ `index.php`
> เมื่อหาไฟล์ไม่เจอ ทำให้ทุก URL ตอบ 200 เหมือนหน้าแรก — **อย่าใช้ dev server
> ตัดสินว่ามีไฟล์หลุดหรือไม่** ให้ตรวจกับเว็บจริงหลัง deploy (`deploy.sh` ตรวจให้อยู่แล้ว)

---

## ตั้งค่าใน Cloudways ครั้งเดียว

### 1. Webroot — สำคัญที่สุด ต้องทำก่อน
**Application Management → Application Settings → Webroot** ใส่ `public`

ถ้าข้ามขั้นนี้ เอกสารทั้ง repo จะเปิดอ่านได้จากอินเทอร์เน็ตทันทีที่ deploy ครั้งแรก

### 2. Deployment via Git
**Application Management → Deployment via Git**

| ช่อง | ค่า |
|---|---|
| Git Remote Address | `git@github.com:Pimnada/saybaewstudio.git` |
| Branch | `main` |
| Deployment Path | เว้นว่าง |

กด **Generate SSH Keys** แล้วคัดลอก public key ที่ได้ ไปใส่ที่
GitHub repo → **Settings → Deploy keys → Add deploy key**
ไม่ต้องติ๊ก "Allow write access" เพราะเซิร์ฟเวอร์แค่ดึงลงมา

### 3. PHP limits
`public/.user.ini` ตั้ง 64M/72M มาให้แล้ว PHP-FPM อ่านเอง ไม่ต้องตั้งใน panel

แต่ถ้าค่าในเซิร์ฟเวอร์ต่ำกว่านี้ก็ยังชนะ — เช็คที่
**Server Management → Settings & Packages → Basic → Upload Size** ให้ไม่น้อยกว่า 64

### 4. config.php
ไฟล์นี้ gitignored จึงไม่มาพร้อม git ต้องสร้างบนเซิร์ฟเวอร์ครั้งเดียว
คัดลอกจาก `config.sample.php` แล้วตั้ง

```
DB_DRIVER   = 'mysql'
DB_NAME / DB_USER / DB_PASS = ค่าจริงจาก Cloudways
SITE_URL    = 'https://saybaewstudio.com'
APP_DEBUG   = false
MAIL_LOG_ONLY = true   ← เปลี่ยนเป็น false เมื่อพร้อมส่งอีเมลจริง
```

วางไว้ที่ **app root** (ข้างนอก `public/`) ไม่ใช่ใน `public/`

---

## deploy ครั้งต่อ ๆ ไป

```bash
cd ~/Sites/saybaewstudio && git push
```

แล้วกด **Start Deployment** ใน Cloudways (หรือเปิด auto-deploy ให้ดึงเองเมื่อมี push ใหม่)

ตารางฐานข้อมูลสร้างและอัปเดตเองในคำขอแรกผ่าน `db.php` — ไม่มีขั้นตอน migrate

---

## ตรวจหลัง deploy ทุกครั้ง

```bash
curl -s -o /dev/null -w '%{http_code}\n' "https://saybaewstudio.com/CLAUDE.md?cb=1"
```

**ต้องได้ 404** ถ้าได้ 200 หมายถึง webroot ยังไม่ได้ชี้ไป `public/` ให้กลับไปทำขั้นที่ 1

---

## ถ้า git deploy ใช้ไม่ได้

`./deploy.sh` ยังใช้ rsync ได้เหมือนเดิม (ต้องใส่ `APP_ID` ก่อน) — มันจะ lint,
ยืนยันปลายทาง, rsync โดยไม่แตะ `config.php` กับ `public/uploads/`
แล้วยิง curl ตรวจว่าเอกสารไม่หลุดและเว็บไม่มี fatal ซ่อนอยู่
