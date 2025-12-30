<?php
/**
 * DMS Archive System - Footer Component
 */

defined('DMS_ENTRY') or exit;
?>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; <?= date('Y') ?> DMS 档案管理系统 v1.0</p>
        <p><small>时区：<?= dms_escape($DMS_CONFIG['timezone_display']) ?></small></p>
    </div>
</footer>

<script src="js/main.js"></script>
