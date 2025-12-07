<?php
/**
 * VIS API - Video Delete
 * 文件路径: app/vis/api/video_delete.php
 * 说明: 删除视频（软删除数据库记录 + 物理删除R2文件）
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

    // 执行删除
    $result = vis_delete_video($pdo, $videoId);

    if ($result['success']) {
        vis_json_response(true, ['id' => $videoId], $result['message']);
    } else {
        vis_json_response(false, null, $result['message']);
    }

} catch (Exception $e) {
    vis_log('删除视频异常: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
