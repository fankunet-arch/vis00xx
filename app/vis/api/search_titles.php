<?php
/**
 * VIS API - Search Video Titles
 * 文件路径: app/vis/api/search_titles.php
 * 说明: 搜索视频标题接口 (用于模糊搜索和去重)
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

$keyword = $_GET['keyword'] ?? '';
// 仅当输入至少1个字符时才搜索（虽然前端已控制，后端加层保险）
if (mb_strlen($keyword) < 1) {
    vis_json_response(true, ['titles' => []]);
}

try {
    $titles = vis_search_video_titles($pdo, $keyword);
    vis_json_response(true, ['titles' => $titles]);
} catch (Exception $e) {
    vis_log('标题搜索API错误: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '搜索失败: ' . $e->getMessage());
}
