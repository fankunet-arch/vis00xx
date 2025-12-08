<?php
/**
 * VIS API - Video Save
 * 文件路径: app/vis/api/video_save.php
 * 说明: 保存视频编辑信息
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 仅允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    vis_json_response(false, null, '仅支持POST请求');
}

try {
    // 获取JSON输入或POST数据
    $input = vis_get_json_input();
    if ($input === null) {
        $input = $_POST;
    }

    // 验证视频ID
    $videoId = isset($input['id']) ? (int)$input['id'] : 0;
    if ($videoId <= 0) {
        vis_json_response(false, null, '无效的视频ID');
    }

    // 检查视频是否存在
    $video = vis_get_video_by_id($pdo, $videoId);
    if (!$video) {
        vis_json_response(false, null, '视频不存在');
    }

    if ($video['status'] === 'deleted') {
        vis_json_response(false, null, '视频已被删除');
    }

    // 准备更新数据
    $updateData = [];

    if (isset($input['title']) && !empty(trim($input['title']))) {
        $updateData['title'] = trim($input['title']);
    }

    if (isset($input['category'])) {
        $updateData['category'] = trim($input['category']);
    }

    // 多系列支持
    if (isset($input['series_names']) && is_array($input['series_names'])) {
        $updateData['series_names'] = $input['series_names'];
    }

    if (isset($input['platform'])) {
        $updateData['platform'] = trim($input['platform']);
    }

    // 产品、系列、季节字段（允许为空）
    if (isset($input['product_id'])) {
        $updateData['product_id'] = !empty($input['product_id']) ? (int)$input['product_id'] : null;
    }

    if (isset($input['series_id'])) {
        $updateData['series_id'] = !empty($input['series_id']) ? (int)$input['series_id'] : null;
    }

    if (isset($input['season_id'])) {
        $updateData['season_id'] = !empty($input['season_id']) ? (int)$input['season_id'] : null;
    }

    // 验证是否有需要更新的字段
    if (empty($updateData)) {
        vis_json_response(false, null, '没有需要更新的内容');
    }

    // 执行更新
    $result = vis_update_video($pdo, $videoId, $updateData);

    if ($result['success']) {
        vis_json_response(true, ['id' => $videoId], $result['message']);
    } else {
        vis_json_response(false, null, $result['message']);
    }

} catch (Exception $e) {
    vis_log('保存视频信息异常: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
