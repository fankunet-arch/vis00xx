-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mhdlmskp2kpxguj.mysql.db
-- 生成日期： 2025-12-08 22:24:55
-- 服务器版本： 8.4.6-6
-- PHP 版本： 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mhdlmskp2kpxguj`
--
CREATE DATABASE IF NOT EXISTS `mhdlmskp2kpxguj` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mhdlmskp2kpxguj`;

-- --------------------------------------------------------

--
-- 表的结构 `sys_users`
--

DROP TABLE IF EXISTS `sys_users`;
CREATE TABLE `sys_users` (
  `user_id` bigint UNSIGNED NOT NULL COMMENT '用户唯一ID (主键)',
  `user_login` varchar(60) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户登录名 (不可变, 用于登录)',
  `user_secret_hash` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户密码的哈希值 (用于验证)',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户电子邮箱 (可用于通知和找回密码)',
  `user_display_name` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '用户显示名称 (在界面上展示的名字)',
  `user_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending' COMMENT '用户账户状态 (例如: active, suspended, pending, deleted)',
  `user_registered_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '用户注册时间 (UTC)',
  `user_last_login_at` datetime(6) DEFAULT NULL COMMENT '用户最后登录时间 (UTC)',
  `user_updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) COMMENT '记录最后更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='系统用户表';

-- --------------------------------------------------------

--
-- 表的结构 `vis_categories`
--

DROP TABLE IF EXISTS `vis_categories`;
CREATE TABLE `vis_categories` (
  `id` int UNSIGNED NOT NULL COMMENT '分类ID',
  `category_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类名称',
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分类代码',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类描述',
  `sort_order` int UNSIGNED DEFAULT '0' COMMENT '排序顺序',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用（1=启用, 0=禁用）',
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIS视频分类表';

-- --------------------------------------------------------

--
-- 表的结构 `vis_products`
--

DROP TABLE IF EXISTS `vis_products`;
CREATE TABLE `vis_products` (
  `id` int NOT NULL COMMENT '产品ID',
  `product_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品名称（如：黑糖珍珠奶茶）',
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品代码（唯一标识）',
  `series_id` int DEFAULT NULL COMMENT '所属系列ID',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '产品描述',
  `sort_order` int DEFAULT '0' COMMENT '排序顺序',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='产品表';

-- --------------------------------------------------------

--
-- 表的结构 `vis_seasons`
--

DROP TABLE IF EXISTS `vis_seasons`;
CREATE TABLE `vis_seasons` (
  `id` int NOT NULL COMMENT '季节ID',
  `season_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '季节名称',
  `season_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '季节代码',
  `sort_order` int DEFAULT '0' COMMENT '排序顺序',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='季节表';

-- --------------------------------------------------------

--
-- 表的结构 `vis_series`
--

DROP TABLE IF EXISTS `vis_series`;
CREATE TABLE `vis_series` (
  `id` int NOT NULL COMMENT '系列ID',
  `series_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称（如：抹茶系列）',
  `series_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列代码（唯一标识）',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '系列描述',
  `sort_order` int DEFAULT '0' COMMENT '排序顺序',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='视频系列表';

-- --------------------------------------------------------

--
-- 表的结构 `vis_series_dict`
--

DROP TABLE IF EXISTS `vis_series_dict`;
CREATE TABLE `vis_series_dict` (
  `id` int UNSIGNED NOT NULL COMMENT '系列ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系列字典表(用于前端模糊搜索下拉)';

-- --------------------------------------------------------

--
-- 表的结构 `vis_videos`
--

DROP TABLE IF EXISTS `vis_videos`;
CREATE TABLE `vis_videos` (
  `id` int UNSIGNED NOT NULL COMMENT '视频ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视频标题',
  `platform` enum('wechat','xiaohongshu','douyin','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT '来源平台',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '其他' COMMENT '分类（备料/制作/打包/营销等）',
  `product_id` int DEFAULT NULL COMMENT '关联产品ID',
  `series_id` int DEFAULT NULL COMMENT '关联系列ID',
  `season_id` int DEFAULT NULL COMMENT '关联季节ID',
  `r2_key` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'R2存储路径（例如：vis/202512/uuid.mp4）',
  `cover_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图URL（视频首帧或默认图）',
  `duration` int UNSIGNED DEFAULT '0' COMMENT '视频时长（秒）',
  `file_size` bigint UNSIGNED DEFAULT '0' COMMENT '文件大小（字节）',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'video/mp4' COMMENT '文件MIME类型',
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '原始文件名',
  `status` enum('active','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT '状态（active=正常, deleted=已删除）',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '上传者用户名',
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '创建时间',
  `updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) COMMENT '更新时间',
  `deleted_at` datetime(6) DEFAULT NULL COMMENT '删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIS视频数据表';

-- --------------------------------------------------------

--
-- 替换视图以便查看 `vis_video_details_view`
-- （参见下面的实际视图）
--
DROP VIEW IF EXISTS `vis_video_details_view`;
CREATE TABLE `vis_video_details_view` (
`category` varchar(50)
,`category_name` varchar(50)
,`cover_url` varchar(512)
,`created_at` datetime(6)
,`created_by` varchar(50)
,`duration` int unsigned
,`file_size` bigint unsigned
,`id` int unsigned
,`mime_type` varchar(100)
,`original_filename` varchar(255)
,`platform` enum('wechat','xiaohongshu','douyin','other')
,`product_code` varchar(50)
,`product_id` int
,`product_name` varchar(100)
,`r2_key` varchar(512)
,`season_code` varchar(20)
,`season_id` int
,`season_name` varchar(50)
,`series_code` varchar(50)
,`series_id` int
,`series_name` varchar(100)
,`status` enum('active','deleted')
,`title` varchar(255)
,`updated_at` datetime(6)
);

-- --------------------------------------------------------

--
-- 表的结构 `vis_video_series_rel`
--

DROP TABLE IF EXISTS `vis_video_series_rel`;
CREATE TABLE `vis_video_series_rel` (
  `video_id` int UNSIGNED NOT NULL COMMENT '视频ID',
  `series_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='视频与系列关联表 (修正版)';

--
-- 转储表的索引
--

--
-- 表的索引 `sys_users`
--
ALTER TABLE `sys_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uk_user_login` (`user_login`),
  ADD UNIQUE KEY `uk_user_email` (`user_email`);

--
-- 表的索引 `vis_categories`
--
ALTER TABLE `vis_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_category_code` (`category_code`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 表的索引 `vis_products`
--
ALTER TABLE `vis_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_product_code` (`product_code`),
  ADD KEY `idx_series_id` (`series_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_enabled` (`is_enabled`);

--
-- 表的索引 `vis_seasons`
--
ALTER TABLE `vis_seasons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_season_code` (`season_code`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 表的索引 `vis_series`
--
ALTER TABLE `vis_series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_series_code` (`series_code`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_is_enabled` (`is_enabled`);

--
-- 表的索引 `vis_series_dict`
--
ALTER TABLE `vis_series_dict`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_name` (`name`);

--
-- 表的索引 `vis_videos`
--
ALTER TABLE `vis_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_series_id` (`series_id`),
  ADD KEY `idx_season_id` (`season_id`);

--
-- 表的索引 `vis_video_series_rel`
--
ALTER TABLE `vis_video_series_rel`
  ADD PRIMARY KEY (`video_id`,`series_name`),
  ADD KEY `idx_series_name` (`series_name`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sys_users`
--
ALTER TABLE `sys_users`
  MODIFY `user_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户唯一ID (主键)';

--
-- 使用表AUTO_INCREMENT `vis_categories`
--
ALTER TABLE `vis_categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID';

--
-- 使用表AUTO_INCREMENT `vis_products`
--
ALTER TABLE `vis_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT '产品ID';

--
-- 使用表AUTO_INCREMENT `vis_seasons`
--
ALTER TABLE `vis_seasons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT '季节ID';

--
-- 使用表AUTO_INCREMENT `vis_series`
--
ALTER TABLE `vis_series`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT '系列ID';

--
-- 使用表AUTO_INCREMENT `vis_series_dict`
--
ALTER TABLE `vis_series_dict`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '系列ID';

--
-- 使用表AUTO_INCREMENT `vis_videos`
--
ALTER TABLE `vis_videos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID';

-- --------------------------------------------------------

--
-- 视图结构 `vis_video_details_view`
--
DROP TABLE IF EXISTS `vis_video_details_view`;

DROP VIEW IF EXISTS `vis_video_details_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`mhdlmskp2kpxguj`@`%` SQL SECURITY DEFINER VIEW `vis_video_details_view`  AS SELECT `v`.`id` AS `id`, `v`.`title` AS `title`, `v`.`platform` AS `platform`, `v`.`category` AS `category`, `c`.`category_name` AS `category_name`, `v`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `p`.`product_code` AS `product_code`, `v`.`series_id` AS `series_id`, `s`.`series_name` AS `series_name`, `s`.`series_code` AS `series_code`, `v`.`season_id` AS `season_id`, `se`.`season_name` AS `season_name`, `se`.`season_code` AS `season_code`, `v`.`r2_key` AS `r2_key`, `v`.`cover_url` AS `cover_url`, `v`.`duration` AS `duration`, `v`.`file_size` AS `file_size`, `v`.`mime_type` AS `mime_type`, `v`.`original_filename` AS `original_filename`, `v`.`status` AS `status`, `v`.`created_by` AS `created_by`, `v`.`created_at` AS `created_at`, `v`.`updated_at` AS `updated_at` FROM ((((`vis_videos` `v` left join `vis_categories` `c` on((`v`.`category` = `c`.`category_code`))) left join `vis_products` `p` on((`v`.`product_id` = `p`.`id`))) left join `vis_series` `s` on((`v`.`series_id` = `s`.`id`))) left join `vis_seasons` `se` on((`v`.`season_id` = `se`.`id`))) WHERE (`v`.`status` = 'active') ;

--
-- 限制导出的表
--

--
-- 限制表 `vis_products`
--
ALTER TABLE `vis_products`
  ADD CONSTRAINT `fk_product_series` FOREIGN KEY (`series_id`) REFERENCES `vis_series` (`id`) ON DELETE SET NULL;

--
-- 限制表 `vis_videos`
--
ALTER TABLE `vis_videos`
  ADD CONSTRAINT `fk_video_product` FOREIGN KEY (`product_id`) REFERENCES `vis_products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_video_season` FOREIGN KEY (`season_id`) REFERENCES `vis_seasons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_video_series` FOREIGN KEY (`series_id`) REFERENCES `vis_series` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
