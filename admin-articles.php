<?php
/** บทความ. */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/image.php';

$pdo = db();

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            flash('กรุณากรอกชื่อบทความ', 'error');
            redirect('admin-articles.php');
        }

        $cover = null;
        try {
            $cover = save_simple_image($_FILES['cover'] ?? [], 'articles', 1600);
        } catch (Throwable $e) {
            flash($e->getMessage(), 'error');
            redirect('admin-articles.php');
        }

        $slug    = trim((string) ($_POST['slug'] ?? '')) ?: $title;
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $body    = (string) ($_POST['body'] ?? '');
        $status  = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if ($id > 0) {
            $sql = 'UPDATE articles SET title = ?, slug = ?, excerpt = ?, body = ?, status = ?,
                           published_at = COALESCE(published_at, ?), updated_at = ?'
                 . ($cover ? ', cover = ?' : '') . ' WHERE id = ?';
            $args = [
                $title, unique_slug('articles', $slug, $id), $excerpt, $body, $status,
                $status === 'published' ? date('Y-m-d H:i:s') : null, date('Y-m-d H:i:s'),
            ];
            if ($cover) { $args[] = $cover; }
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
            flash('บันทึกบทความแล้ว');
        } else {
            $pdo->prepare(
                'INSERT INTO articles (title, slug, excerpt, body, cover, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $title, unique_slug('articles', $slug), $excerpt, $body, $cover, $status,
                $status === 'published' ? date('Y-m-d H:i:s') : null,
            ]);
            flash('สร้างบทความใหม่แล้ว');
        }
        redirect('admin-articles.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
        flash('ลบบทความแล้ว');
        redirect('admin-articles.php');
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit   = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}

$articles = $pdo->query('SELECT * FROM articles ORDER BY published_at DESC, id DESC')->fetchAll();

$admin_title  = 'บทความ';
$admin_crumbs = [['หน้าแรก', 'admin.php'], ['บทความ', null]];
include __DIR__ . '/inc/admin-head.php';
?>

<div class="page-head-row">
  <div>
    <h1 class="page-title">บทความ</h1>
    <p class="text-sm text-muted mb-0">เนื้อหาที่ช่วยให้ลูกค้าหาเว็บเจอจาก Google</p>
  </div>
  <?php if ($edit || $editId === -1): ?>
    <a class="btn btn--light" href="<?= e(url('admin-articles.php')) ?>"><?= icon('arrow-left', '', 16) ?> กลับไปที่รายการ</a>
  <?php else: ?>
    <a class="btn btn--primary" href="<?= e(url('admin-articles.php?edit=-1')) ?>"><?= icon('plus', '', 18) ?> เขียนบทความใหม่</a>
  <?php endif; ?>
</div>

<?php if ($edit || $editId === -1): ?>
  <form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

    <div class="panel__head">
      <h2 class="panel__title"><?= $edit ? 'แก้ไขบทความ' : 'เขียนบทความใหม่' ?></h2>
    </div>

    <div class="panel__body">
      <div class="grid" style="grid-template-columns: minmax(0,2fr) minmax(0,1fr); gap:0 24px;">
        <div>
          <div class="field">
            <label for="ar-title">ชื่อบทความ <span class="req">*</span></label>
            <input id="ar-title" type="text" name="title" required maxlength="220"
                   value="<?= e($edit['title'] ?? '') ?>" data-slug-source>
          </div>
          <div class="field">
            <label for="ar-excerpt">คำโปรย</label>
            <textarea id="ar-excerpt" name="excerpt" rows="2"><?= e($edit['excerpt'] ?? '') ?></textarea>
          </div>
          <div class="field mb-0">
            <label for="ar-body">เนื้อหา</label>
            <textarea id="ar-body" name="body" rows="20"
                      style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13.5px;"><?= e($edit['body'] ?? '') ?></textarea>
            <p class="hint">ใส่ HTML ได้ตรง ๆ เช่น &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;</p>
          </div>
        </div>

        <div>
          <div class="field">
            <label for="ar-cover">ภาพปก</label>
            <img id="ar-preview" alt=""
                 src="<?= e(!empty($edit['cover']) ? upload_url($edit['cover']) : url('assets/img/placeholder.svg')) ?>"
                 style="width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:var(--r-sm);margin-bottom:8px;">
            <input id="ar-cover" type="file" name="cover" accept="image/*" data-preview="#ar-preview">
          </div>
          <div class="field">
            <label for="ar-slug">slug</label>
            <input id="ar-slug" type="text" name="slug" value="<?= e($edit['slug'] ?? '') ?>" data-slug-target>
          </div>
          <div class="field">
            <label for="ar-status">สถานะ</label>
            <select id="ar-status" name="status">
              <option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>ร่าง</option>
              <option value="published" <?= ($edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
            </select>
          </div>
          <?php if ($edit): ?>
            <div class="card" style="padding:14px;">
              <div class="text-xs text-muted">เผยแพร่เมื่อ <?= e(thai_date($edit['published_at'])) ?></div>
              <div class="text-xs text-muted">อ่านแล้ว <?= fmt_num((int) $edit['views']) ?> ครั้ง</div>
              <a class="btn btn--light btn--sm mt-8" target="_blank" rel="noopener"
                 href="<?= e(url('article.php?slug=' . urlencode($edit['slug']))) ?>">ดูหน้าจริง</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="panel__foot">
      <button class="btn btn--primary" type="submit"><?= icon('save', '', 16) ?> บันทึกบทความ</button>
      <a class="btn btn--light" href="<?= e(url('admin-articles.php')) ?>">ยกเลิก</a>
    </div>
  </form>
<?php endif; ?>

<div class="panel">
  <div class="panel__head">
    <h2 class="panel__title">บทความทั้งหมด</h2>
    <span class="spacer"></span>
    <div class="toolbar__search" style="margin:0;">
      <?= icon('search') ?>
      <input type="search" placeholder="ค้นหาบทความ..." data-filter-table="#ar-table">
    </div>
  </div>
  <div class="table-wrap">
    <table class="tbl" id="ar-table">
      <thead>
        <tr>
          <th style="width:76px;">ปก</th>
          <th>ชื่อบทความ</th>
          <th style="width:140px;">เผยแพร่</th>
          <th style="width:90px;">อ่าน</th>
          <th style="width:100px;">สถานะ</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($articles as $a): ?>
        <tr>
          <td><img class="thumb" src="<?= e($a['cover'] ? upload_url($a['cover']) : url('assets/img/placeholder.svg')) ?>" alt=""></td>
          <td>
            <div class="fw-700"><?= e($a['title']) ?></div>
            <div class="text-xs text-faint"><?= e(excerpt($a['excerpt'] ?: $a['body'], 80)) ?></div>
          </td>
          <td class="text-sm text-muted"><?= e(thai_date($a['published_at'])) ?></td>
          <td class="text-sm"><?= fmt_num((int) $a['views']) ?></td>
          <td>
            <span class="badge badge--<?= $a['status'] === 'published' ? 'ok' : 'muted' ?>">
              <?= $a['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
            </span>
          </td>
          <td>
            <div class="tbl__actions">
              <a class="icon-btn" href="<?= e(url('article.php?slug=' . urlencode($a['slug']))) ?>"
                 target="_blank" title="ดูหน้าจริง"><?= icon('eye') ?></a>
              <a class="icon-btn" href="<?= e(url('admin-articles.php?edit=' . $a['id'])) ?>" title="แก้ไข"><?= icon('edit') ?></a>
              <form method="post" data-confirm-submit="ลบบทความ &quot;<?= e($a['title']) ?>&quot;?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
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

<?php include __DIR__ . '/inc/admin-foot.php'; ?>
