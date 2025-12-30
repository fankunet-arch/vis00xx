-- =====================================================
-- Fix Admin Password
-- Updates the admin user password to: admin123
-- =====================================================

USE vis00xx_dms;

UPDATE `dms_users`
SET `password_hash` = '$2y$10$0gbrPzoIXWlgSD6a0jEAOOHLDFtPCM36lpMg0jwk/gkzwKSiws5SG'
WHERE `username` = 'admin';

SELECT 'Admin password updated successfully!' AS message;
SELECT username, email, full_name, role, is_active
FROM dms_users
WHERE username = 'admin';
