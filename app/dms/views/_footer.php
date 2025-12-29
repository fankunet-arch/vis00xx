<?php
/**
 * DMS Archive System - Footer Component
 */

defined('DMS_ENTRY') or exit;
?>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; <?= date('Y') ?> DMS Archive System v1.0</p>
        <p><small>Timezone: <?= dms_escape($DMS_CONFIG['timezone_display']) ?></small></p>
    </div>
</footer>
