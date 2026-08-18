# Prompt สำหรับให้ ChatGPT ออกแบบไอคอนของ saybaewstudio.com

เว็บมีชุดไอคอน SVG ที่วาดไว้แล้วครบทุกตัวใน [`inc/icons.php`](../inc/icons.php)
ใช้งานได้ทันทีโดยไม่ต้องพึ่งไฟล์ภายนอก เอกสารนี้มีไว้สำหรับกรณีที่อยากได้
**ชุดไอคอนที่วาดใหม่ให้เป็นลายเส้นเฉพาะของแบรนด์** — ก๊อป prompt ไปวางใน ChatGPT ได้เลย

วิธีใช้: ส่ง **Prompt 0 (สไตล์หลัก) ก่อนเสมอ** แล้วค่อยส่ง prompt ของไอคอนแต่ละกลุ่มตามหลัง
การส่งสไตล์ก่อนคือสิ่งที่ทำให้ไอคอนทุกตัวออกมาเป็นชุดเดียวกันจริง ๆ ไม่ใช่ไอคอนคนละอารมณ์มากองรวมกัน

เมื่อได้ไฟล์มาแล้ว วางทับ path ใน `icon_paths()` ของ `inc/icons.php`
โดยไม่ต้องแก้ชื่อไอคอน — ทั้งเว็บจะเปลี่ยนตามทันทีทุกหน้า

---

## Prompt 0 — สไตล์หลัก (ส่งอันนี้ก่อนเสมอ)

```
You are designing a single, coherent icon system for a Thai children's
photography studio called "สายแบ้วสตูดิโอ" (saybaewstudio). The brand is warm,
gentle and premium — not childish, not corporate. Parents in their 30s and
school administrators are the audience.

Follow these constraints for EVERY icon you produce, without exception:

CANVAS AND GEOMETRY
- 24 × 24 viewBox, all artwork inside a 20 × 20 live area (2px padding all round)
- Align to a whole-pixel grid; use half-pixel offsets only for 1px-wide strokes
- Key shapes built from circles, rounded rectangles and arcs — no freehand curves

LINE STYLE
- Outline only, stroke-width 1.8, no fills except where noted
- stroke-linecap="round", stroke-linejoin="round"
- Corner radius on rectangles: 2 units for small shapes, 3 for large ones
- Never mix stroke weights inside one icon

COLOUR
- Monochrome. Use stroke="currentColor" and fill="none" — no hard-coded hex values,
  no gradients, no shadows. The icon must inherit its colour from CSS.
- Where a solid glyph is genuinely required (social logos, dots), use
  fill="currentColor" and stroke="none" instead, and say so.

OUTPUT FORMAT
- Return ONLY the inner SVG markup — the <path>, <circle>, <rect> elements —
  with no <svg> wrapper, no XML declaration, no comments, no width/height
  attributes, and no id or class attributes.
- One icon per code block, with the icon's name as a plain-text heading above it.
- Keep each icon under 6 path elements. Simplicity beats detail at 20px.

TONE
- Rounded, soft, friendly. Slightly generous corner radii.
- Optically balanced: an icon should look the same visual weight as the others
  when placed in a row, even if its bounding box differs.

Reply "พร้อมแล้วค่ะ" and wait — I will send the icon list in the next message.
```

---

## Prompt 1 — ไอคอนแบรนด์และการนำทาง

```
Design these icons following the style rules I gave you.

1. camera — a compact camera body with a rounded rectangular shell, a small
   viewfinder bump on the top-left, and a lens circle centred slightly below
   the middle. This is the studio's logo mark, so it must be the most refined
   icon in the set.
2. aperture — a circle with four short radial lines suggesting a lens iris.
3. menu — three horizontal lines, equal length, evenly spaced.
4. close — a clean X, two strokes crossing at the exact centre.
5. search — a magnifier: circle upper-left, handle running to the lower-right.
6. chevron-down, chevron-up, chevron-left, chevron-right — a single angled
   stroke each, identical angle, just rotated.
7. arrow-right, arrow-left — a horizontal shaft with a chevron head.
8. external — a square with its top-right corner opened, plus an arrow escaping
   diagonally out of that corner.
9. sun — a filled-outline circle with eight short rays at 45° intervals.
10. moon — a crescent formed by one continuous stroke, opening to the upper-right.
11. plus, minus, check — the simplest possible forms.
12. more-vertical, more-horizontal — three dots, fill="currentColor".
```

---

## Prompt 2 — ไอคอนจุดขายบนหน้าแรก

```
These four appear as large icons in the hero strip of the homepage, at 26px.
They need to read instantly at that size and should feel like a matched set of
four — same visual density, same amount of internal detail.

1. folder-organised — a folder with two short horizontal lines inside it,
   suggesting sorted contents. Meaning: "อัลบั้มถูกจัดเป็นระบบ".
2. video-frames — a wide rectangle with film-strip perforations on the left and
   right edges and a small play triangle centred. Meaning: "แทรกรูปและวิดีโอ".
3. download-cloud — a soft cloud outline with a downward arrow passing through
   its lower edge. Meaning: "ดาวน์โหลดไฟล์ขนาดจริง".
4. mobile — an upright phone with rounded corners, a speaker slot at the top and
   a single dot at the bottom. Meaning: "รองรับมือถือ".

Then four more, used in the "ทำไมหลายครอบครัวเลือกเรา" cards at 28px:

5. image-sharp — a picture frame containing a small sun circle and two mountain
   peaks. Meaning: "ภาพคมชัด ดูดี".
6. moment-star — a five-pointed star with softly rounded points, drawn as a
   single continuous outline. Meaning: "เก็บครบทุกช่วงสำคัญ".
7. share-link — two interlocking rounded link segments on a diagonal.
   Meaning: "แชร์ลิงก์ได้ทันที".
8. chat-fast — a speech bubble with a tail at the lower-left and three dots
   inside. Meaning: "ตอบเร็วผ่าน LINE / Facebook".
```

---

## Prompt 3 — ไอคอนเมนูหลังบ้าน

```
These sit in a dark vertical sidebar at 19px, each next to a Thai label.
They must be distinguishable from one another at a glance — no two icons in
this group may share a silhouette.

1.  home — a house with a pitched roof and a doorway.
2.  chart — an L-shaped axis with three bars of different heights.
3.  images — two stacked picture frames, the front one overlapping the back.
4.  video — a rectangle with a lens-shaped triangle protruding from its right side.
5.  pages — a browser window: a rectangle with a header bar and a sidebar divider.
6.  article — a rectangle with three text lines of decreasing length.
7.  star — a five-pointed star outline (same drawing as moment-star).
8.  help — a circle with a question mark inside.
9.  banner — a wide rectangle with a small sun and a hill inside it.
10. nav-menu — a solid-outlined bar on top with two shorter lines beneath.
11. folder — a plain folder with a tab on its upper-left.
12. inbox — a tray with an opening in the middle of its top edge.
13. robot — a rounded rectangular head with two eye dots, a mouth line, and a
    short antenna with a dot on top.
14. mail — an envelope with a V-shaped flap.
15. settings — a gear with eight rounded teeth and a circular hub.
16. phone — a handset tilted 45°, classic receiver shape.
17. users — one full figure in front, a partial second figure behind its shoulder.
18. server — two stacked rounded rectangles, each with a small status dot on the left.
```

---

## Prompt 4 — ไอคอนเครื่องมือจัดการรูป

```
These appear on toolbars and in the photo grid at 15–18px. They are the icons
the studio clicks hundreds of times a day, so clarity beats personality here.

1.  upload — an upward arrow rising out of a horizontal baseline.
2.  download — a downward arrow landing on a horizontal baseline.
3.  trash — a bin with a lid, a handle notch, and two vertical lines inside.
4.  edit — a pencil on a 45° diagonal with a small nib and a base line.
5.  copy — two overlapping rounded rectangles, offset up-left and down-right.
6.  link — same as share-link.
7.  folder-plus — a folder with a small plus sign in its lower-right area.
8.  folder-move — a folder with a short right-pointing arrow inside.
9.  filter — a funnel: a wide top tapering to a narrow stem.
10. sort — two vertical arrows side by side, one pointing up, one pointing down.
11. grid-lg — four equal rounded squares in a 2 × 2 layout.
12. grid-sm — nine smaller rounded squares in a 3 × 3 layout.
13. list — three horizontal lines each preceded by a small filled bullet.
14. select-all — a rounded square containing a checkmark.
15. select-none — a rounded square containing an X.
16. save — a floppy-disk silhouette: outer square, a shutter at the top and a
    label panel at the bottom.
17. refresh — a near-complete circular arrow with an arrowhead at its opening.
18. zoom — a magnifier with a plus sign inside the lens.
19. drag — six dots in a 2 × 3 arrangement, fill="currentColor".
20. eye — an almond outline with a circular pupil.
21. lock — a padlock: rounded body with a shackle arc above it.
22. key — a circular bow on the left with a toothed shaft running up-right.
23. shield — a shield outline containing a checkmark.
24. bell — a bell body with a small clapper arc beneath it.
25. bookmark — a ribbon with a V-notch cut into its bottom edge.
26. logout — a doorway with an arrow leaving it to the right.
27. clock — a circle with hour and minute hands showing roughly 10:10.
28. calendar — a rectangle with a header band and two short posts on top.
29. map-pin — a teardrop with a circular hole at its centre.
30. check-circle — a circle containing a checkmark.
31. trend-up — a rising zig-zag line with a small arrowhead at its top-right.
32. database — a cylinder: an ellipse on top with two curved bands below it.
33. play — a circle containing a right-pointing triangle.
34. image — a frame with a small sun and one mountain peak.
```

---

## Prompt 5 — โลโก้โซเชียล (ต้องแม่นยำตามแบรนด์ต้นทาง)

```
These five are third-party brand marks, so brand accuracy overrides my house
style. Use fill="currentColor" and stroke="none" for all of them, and keep each
inside the same 24 × 24 viewBox so they line up with the rest of the set.

1. line     — the LINE messenger speech-bubble mark with the LINE wordmark inside
2. facebook — the lowercase "f" glyph
3. instagram — the rounded-square camera outline with lens circle and corner dot
              (this one may use stroke="currentColor" since it is an outline mark)
4. youtube  — the rounded rectangle with a play triangle
5. tiktok   — the musical-note mark

Do not restyle these to match the rest of the set — a recoloured or re-drawn
platform logo reads as fake and some platforms' brand guidelines forbid it.
```

---

## Prompt 6 — favicon และภาพสำหรับแชร์ลิงก์

```
Two brand assets, not part of the 24 × 24 icon set:

FAVICON (64 × 64 SVG)
- Dark rounded square background, #17140F, corner radius 14
- A gold circle outline, #B0803A, stroke-width 3.5, centred, radius 21
- Inside it the camera mark from icon #1, drawn in #D0A55E at stroke-width 2.6
- Must stay readable when scaled down to 16 × 16 — if the camera becomes mud at
  that size, simplify it to just the lens circle inside the gold ring.

OPEN GRAPH IMAGE (1200 × 630 PNG)
- Background: a very dark warm charcoal, #17140F
- Left two-thirds: the Thai text "สายแบ้วสตูดิโอ" in white, 72px, bold, with
  "รับถ่ายภาพเด็กและกิจกรรมทุกช่วงวัย" beneath it in #A79C8C at 32px, and the
  wordmark "SAYBAEWSTUDIO" in #B0803A, 20px, letter-spaced 4px, above the title
- Right third: a 2 × 2 grid of rounded photo placeholders with 12px gaps
- A 6px gold bar, #B0803A, running the full width along the bottom edge
- No stock photography of real children — use abstract warm-toned placeholders
```

---

## หลังจากได้ไฟล์มาแล้ว

1. เปิด `inc/icons.php` หา `icon_paths()`
2. แทนที่ค่าของไอคอนตัวนั้น โดยรูปแบบคือ `'ชื่อไอคอน' => ['<path .../>', 'stroke']`
   - ตัวสุดท้ายใส่ `'stroke'` สำหรับไอคอนลายเส้น และ `'fill'` สำหรับไอคอนทึบ
3. **ห้ามเปลี่ยนชื่อไอคอน** — ชื่อถูกอ้างถึงกระจายอยู่ทั่วทั้งเว็บ
   เปลี่ยนชื่อแล้วไอคอนจะกลายเป็นจุดกลม ๆ (ตัว fallback) ทันทีโดยไม่มี error
4. ตรวจว่าไอคอนใหม่ไม่มี `width`, `height`, `fill="#xxxxxx"` หรือ `stroke="#xxxxxx"`
   ติดมาด้วย เพราะจะทำให้ไอคอนไม่เปลี่ยนสีตามพื้นหลังในโหมดกลางคืน
5. เปิด `http://localhost:8210/admin.php` แล้วดูไล่ทีละหน้า — ทุกหน้าใช้ชุดเดียวกัน
   ถ้าตัวไหนเพี้ยนจะเห็นทันที

---

## เทมเพลตอีเมลที่มีอยู่แล้ว

หน้าแอดมินส่งอีเมล 6 แบบ ทุกฉบับใช้กรอบเดียวกันจาก `emails/layout.php`
(หัวจดหมายสีเข้ม โลโก้ทอง เนื้อในพื้นขาว ท้ายจดหมายสีครีม) และไม่ใช้อิโมจิเลย

| เทมเพลต | ใช้เมื่อไหร่ |
|---|---|
| `new-message` | ลูกค้ากรอกฟอร์มติดต่อ → แจ้งทีมงาน |
| `message-received` | ลูกค้ากรอกฟอร์มติดต่อ → ตอบรับอัตโนมัติถึงลูกค้า |
| `album-ready` | กดส่งลิงก์อัลบั้มจากหน้าอัลบั้ม |
| `album-reminder` | เตือนลูกค้าที่ยังไม่ได้ดาวน์โหลด |
| `reply` | ตอบกลับลูกค้าด้วยข้อความอิสระจากหน้าข้อความ |
| `user-invite` | เพิ่มทีมงานใหม่ในหน้าผู้ใช้งาน |

ดูตัวอย่างจริงทุกฉบับได้ที่ **หลังบ้าน → อีเมลที่ส่งออก → ดูตัวอย่าง**
