-- ============================================
-- 方案B 最终迁移脚本 (Step 5/5)
-- 目的：删除 vis_videos.series_id 冗余字段
-- ⚠️  警告：这是不可逆操作！请确保：
--    1. 已执行 migration_01_validate_data.sql 并通过所有检查
--    2. 已执行 migration_02_optimize_indexes.sql 确保索引完备
--    3. 已执行 migration_03_update_view.sql 更新视图
--    4. 已部署新代码并测试通过
--    5. 已全量备份数据库
-- ============================================

-- 强制切换到目标数据库（解决 #1109 错误）
USE `mhdlmskp2kpxguj`;

SET NAMES utf8mb4;

-- ============================================
-- 前置检查（必须全部通过才能继续）
-- ============================================

-- 检查1: 确认没有幽灵数据
SELECT
    '【前置检查1】幽灵数据' AS check_name,
    COUNT(*) AS orphan_count,
    CASE
        WHEN COUNT(*) = 0 THEN '✓ 通过'
        ELSE '❌ 失败：存在幽灵数据，请先执行 migration_01 中的迁移脚本'
    END AS result
FROM `mhdlmskp2kpxguj`.`vis_videos v
WHERE v.series_id IS NOT NULL
  AND v.status = 'active'
  AND NOT EXISTS (
      SELECT 1
      FROM `mhdlmskp2kpxguj`.`vis_video_series_rel vsr
      WHERE vsr.video_id = v.id
        AND vsr.series_id = v.series_id
  );

-- 检查2: 确认索引完备
SELECT
    '【前置检查2】索引完备性' AS check_name,
    COUNT(*) AS index_count,
    CASE
        WHEN COUNT(*) >= 2 THEN '✓ 通过'
        ELSE '❌ 失败：索引缺失，请先执行 migration_02'
    END AS result
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'mhdlmskp2kpxguj'
  AND TABLE_NAME = 'vis_video_series_rel'
  AND INDEX_NAME IN ('PRIMARY', 'idx_series_id');

-- 检查3: 确认视图已更新
SELECT
    '【前置检查3】视图定义' AS check_name,
    CASE
        WHEN VIEW_DEFINITION LIKE '%GROUP_CONCAT%' THEN '✓ 通过：视图已更新为 GROUP_CONCAT 版本'
        ELSE '⚠️  警告：视图可能还是旧版本，请检查'
    END AS result
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = 'mhdlmskp2kpxguj'
  AND TABLE_NAME = 'vis_video_details_view';

-- ============================================
-- 手动确认提示
-- ============================================
SELECT
    '【重要提示】' AS warning,
    CONCAT(
        '在执行删除操作之前，请手动确认：\n',
        '1. 新代码已部署到生产环境\n',
        '2. 前台和后台功能已全面测试通过\n',
        '3. 已全量备份数据库（包括 schema 和 data）\n',
        '4. 已在测试环境成功执行此脚本\n',
        '5. 已通知相关人员即将进行架构变更\n\n',
        '如果以上条件都满足，请取消注释下面的 ALTER TABLE 语句并执行。'
    ) AS message;

-- ============================================
-- 备份建议
-- ============================================
-- 在执行 ALTER TABLE 之前，建议执行以下命令备份：
-- mysqldump -u [user] -p [database] > backup_before_drop_series_id_$(date +%Y%m%d_%H%M%S).sql

-- ============================================
-- 第1步：删除外键约束
-- ============================================
-- 注意：必须先删除外键约束，才能删除被引用的列

/*
ALTER TABLE vis_videos
DROP FOREIGN KEY fk_video_series;
*/

SELECT
    '【操作1】删除外键约束 fk_video_series' AS step,
    'ALTER TABLE vis_videos DROP FOREIGN KEY fk_video_series;' AS sql_command;

-- ============================================
-- 第2步：删除索引
-- ============================================
-- 删除 series_id 列上的索引（如果存在）

/*
ALTER TABLE vis_videos
DROP INDEX idx_series_id;
*/

SELECT
    '【操作2】删除索引 idx_series_id' AS step,
    'ALTER TABLE vis_videos DROP INDEX idx_series_id;' AS sql_command;

-- ============================================
-- 第3步：删除 series_id 列
-- ============================================
-- ⚠️  不可逆操作！

/*
ALTER TABLE vis_videos
DROP COLUMN series_id;
*/

SELECT
    '【操作3】删除列 series_id（不可逆）' AS step,
    'ALTER TABLE vis_videos DROP COLUMN series_id;' AS sql_command;

-- ============================================
-- 第4步：验证删除结果
-- ============================================

-- 验证1: 确认列已删除
SELECT
    '【验证1】series_id 列是否存在' AS check_name,
    CASE
        WHEN COUNT(*) = 0 THEN '✓ 通过：series_id 列已删除'
        ELSE '❌ 失败：series_id 列仍然存在'
    END AS result
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mhdlmskp2kpxguj'
  AND TABLE_NAME = 'vis_videos'
  AND COLUMN_NAME = 'series_id';

-- 验证2: 确认外键约束已删除
SELECT
    '【验证2】fk_video_series 约束是否存在' AS check_name,
    CASE
        WHEN COUNT(*) = 0 THEN '✓ 通过：外键约束已删除'
        ELSE '❌ 失败：外键约束仍然存在'
    END AS result
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'mhdlmskp2kpxguj'
  AND TABLE_NAME = 'vis_videos'
  AND CONSTRAINT_NAME = 'fk_video_series';

-- 验证3: 确认系统仍然可以正常查询
SELECT
    '【验证3】数据查询测试' AS test_name,
    COUNT(*) AS video_count,
    COUNT(DISTINCT id) AS unique_video_count,
    '✓ 查询正常' AS result
FROM `mhdlmskp2kpxguj`.`vis_videos
WHERE status = 'active';

-- 验证4: 确认系列关联仍然完整
SELECT
    '【验证4】系列关联测试' AS test_name,
    COUNT(DISTINCT video_id) AS videos_with_series,
    COUNT(*) AS total_relations,
    '✓ 关联表正常' AS result
FROM `mhdlmskp2kpxguj`.`vis_video_series_rel;

-- ============================================
-- 第5步：更新表注释（可选）
-- ============================================

/*
ALTER TABLE vis_videos
COMMENT = 'VIS视频数据表（已迁移到多对多系列关系）';
*/

-- ============================================
-- 回滚方案（仅供参考，无法恢复数据）
-- ============================================
-- 注意：删除列后，原有数据无法恢复！
-- 以下脚本仅重建表结构，数据需要从备份还原。

/*
-- 重新添加 series_id 列（空数据）
ALTER TABLE vis_videos
ADD COLUMN series_id int DEFAULT NULL COMMENT '关联系列ID（已废弃，使用 vis_video_series_rel 表）'
AFTER product_id;

-- 重建索引
ALTER TABLE vis_videos
ADD INDEX idx_series_id (series_id);

-- 重建外键约束
ALTER TABLE vis_videos
ADD CONSTRAINT fk_video_series
FOREIGN KEY (series_id) REFERENCES vis_series (id) ON DELETE SET NULL;

-- 从 vis_video_series_rel 恢复数据（取每个视频的第一个系列）
UPDATE `mhdlmskp2kpxguj`.`vis_videos v
SET v.series_id = (
    SELECT vsr.series_id
    FROM `mhdlmskp2kpxguj`.`vis_video_series_rel vsr
    WHERE vsr.video_id = v.id
    ORDER BY vsr.series_id ASC
    LIMIT 1
)
WHERE v.id IN (SELECT DISTINCT video_id FROM `mhdlmskp2kpxguj`.`vis_video_series_rel);
*/

-- ============================================
-- 迁移完成检查清单
-- ============================================
SELECT
    '【迁移完成】' AS status,
    CONCAT(
        '请确认以下事项：\n',
        '□ vis_videos.series_id 列已删除\n',
        '□ 外键约束 fk_video_series 已删除\n',
        '□ 前台和后台功能测试通过\n',
        '□ 系列筛选和显示功能正常\n',
        '□ 视频上传和编辑功能正常\n',
        '□ 性能监控无异常\n',
        '□ 数据库备份已安全保存\n\n',
        '恭喜！方案B 架构迁移已完成。\n',
        '系统现已使用纯多对多关系，数据冗余已彻底消除。'
    ) AS checklist;

-- ============================================
-- 性能优化建议（迁移后）
-- ============================================
SELECT
    '【后续优化建议】' AS recommendation,
    CONCAT(
        '1. 运行 ANALYZE TABLE vis_videos; 更新表统计信息\n',
        '2. 运行 ANALYZE TABLE vis_video_series_rel; 更新关联表统计信息\n',
        '3. 监控慢查询日志 1-2 周，确保没有性能回归\n',
        '4. 如果数据量超过 10万 条记录，考虑分区策略\n',
        '5. 定期清理 deleted 状态的视频记录和孤立的系列记录\n',
        '6. 考虑为 vis_series 表添加缓存层（Redis）以减少查询'
    ) AS tips;
