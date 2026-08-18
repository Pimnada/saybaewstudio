<?php /** Closes the admin shell opened by inc/admin-head.php. */ ?>
  </div><!-- /.content -->
</div><!-- /.shell -->

<div class="modal" data-confirm-modal>
  <div class="modal__box" style="max-width:420px;">
    <div class="modal__head">
      <h3 class="modal__title" data-confirm-title>ยืนยันการทำรายการ</h3>
      <button class="icon-btn" type="button" data-modal-close aria-label="ปิด"><?= icon('close') ?></button>
    </div>
    <div class="modal__body"><p data-confirm-text style="margin:0;"></p></div>
    <div class="modal__foot">
      <button class="btn btn--light" type="button" data-modal-close>ยกเลิก</button>
      <button class="btn btn--danger" type="button" data-confirm-ok>ยืนยัน</button>
    </div>
  </div>
</div>

<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
<?php if (!empty($admin_scripts)): ?>
  <?php foreach ($admin_scripts as $src): ?>
    <script src="<?= asset($src) ?>" defer></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
