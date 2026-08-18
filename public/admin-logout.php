<?php
require_once __DIR__ . '/../auth.php';

if (current_user()) {
    log_activity('logout');
}
logout();
redirect('admin-login.php');
