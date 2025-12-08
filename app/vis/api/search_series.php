<?php
/**
 * VIS API - Search Series Dictionary
 * 文件路径: app/vis/api/search_series.php
 * 说明: 搜索系列字典，用于前端自动补全
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

$keyword = trim($_GET['keyword'] ?? '');

if (mb_strlen($keyword) < 1) {
    vis_json_response(true, ['series' => []]);
}

$series = vis_search_series_dict($pdo, $keyword);

vis_json_response(true, ['series' => $series]);
