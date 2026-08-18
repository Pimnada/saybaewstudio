<?php
/** จัดการหน้าเว็บ — the static pages (about, privacy, terms, and any new ones). */

require_once __DIR__ . '/../auth.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug  = slugify(trim((string) ($_POST['slug'] ?? $title)));
        $body  = (string) ($_POST['body'] ?? '');
        $meta  = trim((string) ($_POST['meta_description'] ?? ''));
        $status= ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';

        if ($title === '') {
            flash('กรุณากรอกชื่อหน้า', 'error');
            redirect('admin-pages.php');
        }

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE pages SET title = ?, slug = ?, body = ?, meta_description = ?, status = ?, updated_at = ?
                  WHERE id = ?'
            )->execute([$title, $slug, $body, $meta, $status, date('Y-m-d H:i:s'), $id]);
            flash('บันทึกหน้า "' . $title . '" แล้ว');
        } else {
            $pdo->prepare(
                'INSERT INTO pages (title, slug, body, meta_description, status) VALUES (?, ?, ?, ?, ?)'
            )->execute([$title, $slug, $body, $meta, $status]);
            flash('สร้างหน้าใหม่แล้ว');
        }
        redirect('admin-pages.php');
    }

    if ($action === 'delete') {
        $st = $pdo->prepare('SELECT slug FROM pages WHERE id = ?');
        $st->execute([$id]);
        if (in_array($st->fetchColumn(), ['about', 'privacy', 'terms'], true)) {
            flash('หน้านี้ถูกลิงก์อยู่ในเมนูและฟุตเตอร์ ลบไม่ได้ — ปรับเป็นร่างแทนได้', 'error');
            redirect('admin-pages.php');
        }
        $pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
        flash('ลบหน้าแล้ว');
        redirect('admin-pages.php');
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit   = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM pages WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}

$pages = $pdo->query('SELECT * FROM pages ORDER BY id')->fetchAll();

$admin_title  = 'จัดการหน้าเว็บ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['จัดการหน้าเว็บ', null]];
include __DIR__ . '/../inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">จัดการหน้าเว็บ</h1>
    <p class="text-sm text-muted mb-0">หน้าเนื้อหาคงที่ เช่น เกี่ยวกับเรา นโยบาย และข้อกำหนด</p>
  </div>
  <?php if ($edit): ?>
    <a class="btn btn--light" href="<?= e(url('admin-pages.php')) ?>"><?= icon('arrow-left', '', 16) ?> กลับไปที่รายการ</a>
  <?php else: ?>
    <a class="btn btn--primary" href="<?= e(url('admin-pages.php?edit=-1')) ?>"><?= icon('plus', '', 18) ?> สร้างหน้าใหม่</a>
  <?php endif; ?>
</div>

<?php if ($edit || $editId === -1): ?>
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

    <div class="panel__head">
      <h2 class="panel__title"><?= $edit ? 'แก้ไขหน้า: ' . e($edit['title']) : 'สร้างหน้าใหม่' ?></h2>
      <span class="spacer"></span>
      <?php if ($edit): ?>
        <a class="btn btn--light btn--sm" target="_blank" rel="noopener"
           href="<?= e(url('page.php?slug=' . urlencode($edit['slug']))) ?>">
          <?= icon('external', '', 16) ?> ดูหน้าจริง
        </a>
      <?php endif; ?>
    </div>

    <div class="panel__body">
      <div class="grid grid-2" style="gap:0 16px;">
        <div class="field">
          <label for="pg-title">ชื่อหน้า <span class="req">*</span></label>
          <input id="pg-title" type="text" name="title" required maxlength="220"
                 value="<?= e($edit['title'] ?? '') ?>" data-slug-source>
        </div>
        <div class="field">
          <label for="pg-slug">slug (ใช้ในลิงก์)</label>
          <input id="pg-slug" type="text" name="slug" maxlength="120"
                 value="<?= e($edit['slug'] ?? '') ?>" data-slug-target>
          <p class="hint">ลิงก์จะเป็น page.php?slug=<span id="pg-slug-echo"><?= e($edit['slug'] ?? '') ?></span></p>
        </div>
      </div>

      <div class="field">
        <label for="pg-meta">คำอธิบายสำหรับ Google</label>
        <input id="pg-meta" type="text" name="meta_description" maxlength="300"
               value="<?= e($edit['meta_description'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="pg-body">เนื้อหา</label>
        <textarea id="pg-body" name="body" rows="18" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13.5px;"><?= e($edit['body'] ?? '') ?></textarea>
        <p class="hint">
          ใส่ HTML ได้ตรง ๆ — แท็กที่ใช้บ่อย: &lt;p&gt; ย่อหน้า, &lt;h3&gt; หัวข้อย่อย,
          &lt;ul&gt;&lt;li&gt; รายการ, &lt;strong&gt; ตัวหนา
        </p>
      </div>

      <div class="field mb-0" style="max-width:240px;">
        <label for="pg-status">สถานะ</label>
        <select id="pg-status" name="status">
          <option value="published" <?= ($edit['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
          <option value="draft" <?= ($edit['status'] ?? '') === 'draft' ? 'selected' : '' ?>>ร่าง</option>
        </select>
      </div>
    </div>

    <div class="panel__foot">
      <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกหน้า</button>
      <a class="btn btn--light" href="<?= e(url('admin-pages.php')) ?>">ยกเลิก</a>
    </div>
  </form>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2 class="panel__title">หน้าทั้งหมด</h2></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>ชื่อหน้า</th>
          <th style="width:170px;">slug</th>
          <th style="width:150px;">แก้ไขล่าสุด</th>
          <th style="width:100px;">สถานะ</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pages as $p): ?>
        <tr>
          <td>
            <div class="fw-700"><?= e($p['title']) ?></div>
            <div class="text-xs text-faint"><?= e(excerpt($p['body'], 70)) ?></div>
          </td>
          <td class="text-sm text-muted"><?= e($p['slug']) ?></td>
          <td class="text-sm text-muted"><?= e(thai_date($p['updated_at'])) ?></td>
          <td>
            <span class="badge badge--<?= $p['status'] === 'published' ? 'ok' : 'muted' ?>">
              <?= $p['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
            </span>
          </td>
          <td>
            <div class="tbl__actions">
              <a class="icon-btn" href="<?= e(url('page.php?slug=' . urlencode($p['slug']))) ?>"
                 target="_blank" title="ดูหน้าจริง"><?= icon('eye') ?></a>
              <a class="icon-btn" href="<?= e(url('admin-pages.php?edit=' . $p['id'])) ?>" title="แก้ไข"><?= icon('edit') ?></a>
              <form method="post" data-confirm-submit="ลบหน้า &quot;<?= e($p['title']) ?>&quot;?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="icon-btn" type="submit" title="ลบ"><?= icon('trash') ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function () {
  var slug = document.getElementById('pg-slug');
  var echo = document.getElementById('pg-slug-echo');
  if (slug && echo) {
    slug.addEventListener('input', function () { echo.textContent = slug.value; });
  }
})();
</script>

<?php include __DIR__ . '/../inc/admin-foot.php'; ?>
