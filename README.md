# saybaewstudio.com — สายแบ้วสตูดิโอ

เว็บไซต์ช่างภาพเด็กและงานกิจกรรม พร้อมระบบหลังบ้านจัดการอัลบั้มภาพ
เขียนด้วย PHP + PDO ล้วน ไม่มีเฟรมเวิร์ก ไม่มี build step

---

## เริ่มใช้งานบนเครื่อง

```bash
cd ~/Sites/saybaewstudio && php -S localhost:8210
```

เปิด http://localhost:8210 — ตารางฐานข้อมูลและเนื้อหาตั้งต้นถูกสร้างเองในคำขอแรก

**เข้าหลังบ้าน:** http://localhost:8210/admin-login.php

| | |
|---|---|
| อีเมล | `admin@saybaewstudio.com` |
| รหัสผ่าน | `saybaew@2569` |

> เปลี่ยนรหัสผ่านทันทีหลังขึ้นเซิร์ฟเวอร์จริง ที่ **โปรไฟล์ของฉัน**

สร้างรูปตัวอย่างให้อัลบั้มที่ยังว่าง:

```bash
php tools/demo-photos.php
```

---

## โครงสร้าง

ไฟล์ PHP วางแบนที่ root หนึ่งไฟล์ต่อหนึ่งหน้า ไม่มี router ไม่มี autoloader

| | |
|---|---|
| หน้าเว็บสาธารณะ | `index.php`, `albums.php`, `album.php`, `services.php`, `about.php`, `reviews.php`, `blog.php`, `article.php`, `contact.php`, `page.php` |
| หลังบ้าน | `admin*.php` — 20 หน้า |
| API (เรียกจาก JS) | `api-upload.php`, `api-photos.php`, `api-sort.php` |
| แกนกลาง | `db.php` (schema + migration), `lib.php` (helper), `auth.php` (session + CSRF), `image.php` (ย่อรูป), `mailer.php` |
| ส่วนที่ใช้ร่วมกัน | `inc/header.php`, `inc/footer.php`, `inc/admin-head.php`, `inc/admin-foot.php`, `inc/icons.php`, `inc/contact-band.php` |
| ดีไซน์ | `assets/css/base.css` = ตัวแปรสีและคอมโพเนนต์ที่ใช้ร่วมกันทั้งเว็บ · `site.css` = หน้าสาธารณะ · `admin.css` = หลังบ้าน |
| อีเมล | `emails/` — layout เดียว 6 เทมเพลต |
| ไม่ deploy | `docs/`, `tools/`, `*.sh`, `*.md` |

**สีทั้งหมดอยู่ใน `base.css` ที่เดียว** ทั้งหน้าเว็บและหลังบ้านใช้ตัวแปรชุดเดียวกัน
แก้สีที่นั่นแล้วเปลี่ยนทั้งเว็บ รวมถึงโหมดกลางคืน — ไม่มีรายการ override แยกต่อหน้า

---

## เรื่องที่ต้องรู้ก่อนแก้โค้ด

**เซสชันต้องเปิดก่อนพ่น HTML** — `inc/header.php` เรียก `boot_session()` เป็นบรรทัดแรก
ถ้าหน้าไหน echo อะไรออกไปก่อน include header เซสชันจะเริ่มไม่ทัน CSRF พังเงียบ ๆ ทั้งหน้า

**ฝังค่า PHP ลงใน `<script>` ใช้ `ejs()` เท่านั้น** ห้ามใช้ `e(json_encode(...))`
เพราะเบราว์เซอร์ไม่แปลง HTML entity ข้างใน `<script>` — จะได้ `&quot;` ติดมาแล้ว SyntaxError
ส่วนใน **attribute** ให้ใช้ `e(json_encode(...))` ตามเดิม

**สคริปต์ inline ในหน้าแอดมินต้องรออยู่ใน `DOMContentLoaded`**
เพราะ `admin.js` โหลดแบบ `defer` ซึ่งรันทีหลัง — เรียก `window.SBSAdmin` ตรง ๆ จะได้ undefined

**รูปเก็บ 3 ขนาดเสมอ** — `orig/` คือไฟล์จากกล้องที่ไม่ถูกแตะ (ลูกค้าดาวน์โหลดอันนี้),
`preview/` 2048px สำหรับดูเต็มจอ, `thumb/` 600px สำหรับกริด
สร้างโดย Imagick ถ้ามี ไม่งั้นถอยไปใช้ GD — ไม่มีการเรียก process ภายนอกเลย
เพราะ `exec()` ถูกปิดบน Cloudways

**อัปโหลดส่งทีละไฟล์** ไม่ใช่ POST ก้อนเดียว เพราะ nginx ตัด request ที่ราว 128 MiB
`assets/js/uploader.js` ยิงพร้อมกัน 3 ไฟล์ มี retry 2 ครั้ง และเตือนถ้าปิดหน้าระหว่างอัปโหลด

**อีเมลอยู่ในโหมดทดสอบ** — `MAIL_LOG_ONLY` ใน `config.php` เป็น `true`
จดหมายทุกฉบับถูกสร้างและเก็บไว้ที่ **หลังบ้าน → อีเมลที่ส่งออก** แต่ยังไม่ส่งออกจริง

---

## ขึ้นเซิร์ฟเวอร์จริง

ยังไม่ได้ทำ — ต้องมีสองอย่างก่อน

1. **จดโดเมน `saybaewstudio.com`** (ตรวจแล้วว่างอยู่ ณ 18 ส.ค. 2569)
2. **สร้าง Cloudways application ใหม่** สำหรับเว็บนี้ แล้วเอา application id
   ไปใส่ในตัวแปร `APP_ID` ที่หัวไฟล์ `deploy.sh`

จากนั้น สร้าง `config.php` บนเซิร์ฟเวอร์ (คัดลอกจาก `config.sample.php`) โดยตั้ง
`DB_DRIVER` เป็น `mysql`, ใส่รหัสฐานข้อมูลจริง, `SITE_URL` เป็น `https://saybaewstudio.com`,
`APP_DEBUG` เป็น `false` แล้วรัน

```bash
cd ~/Sites/saybaewstudio && ./deploy.sh --dry-run
```

ดูว่าจะส่งอะไรบ้าง ถ้าถูกต้องค่อยรันโดยไม่ใส่ `--dry-run`

`deploy.sh` จะ lint ไฟล์ PHP ทุกไฟล์, ยืนยันว่าปลายทางเป็น saybaewstudio จริง,
rsync โดย **ไม่แตะ `config.php` และ `uploads/`**, แล้วตรวจว่าไม่มี `.md`/`.sh`/`docs/`
หลุดขึ้น web root และเว็บยังตอบ 200 พร้อมไม่มี PHP fatal ในหน้า

ตารางฐานข้อมูลสร้างเองในคำขอแรกผ่าน `db.php` — ไม่มีขั้นตอน migrate แยก
