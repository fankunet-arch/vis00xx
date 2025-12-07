<?php
/**
 * VIS API - Play Sign
 * 文件路径: app/vis/api/play_sign.php
 * 说明: 获取视频播放的临时签名URL（300秒有效期）
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 支持GET和POST请求
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
    vis_json_response(false, null, '不支持的请求方法');
}

try {
    // 获取视频ID
    $videoId = 0;
    if ($method === 'GET') {
        $videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    } else {
        $input = vis_get_json_input();
        if ($input === null) {
            $input = $_POST;
        }
        $videoId = isset($input['id']) ? (int)$input['id'] : 0;
    }

    // 验证视频ID
    if ($videoId <= 0) {
        vis_json_response(false, null, '无效的视频ID');
    }

    // 获取视频信息
    $video = vis_get_video_by_id($pdo, $videoId);
    if (!$video) {
        vis_json_response(false, null, '视频不存在');
    }

    if ($video['status'] !== 'active') {
        vis_json_response(false, null, '视频不可用');
    }

    // 生成签名URL（300秒有效期）
    $signedUrl = vis_get_signed_url($video['r2_key'], VIS_SIGNED_URL_EXPIRES);

    if (!$signedUrl) {
        vis_json_response(false, null, '生成播放链接失败');
    }

    // 返回签名URL和视频基本信息
    vis_json_response(true, [
        'id' => $video['id'],
        'title' => $video['title'],
        'url' => $signedUrl,
        'duration' => $video['duration'],
        'expires_in' => VIS_SIGNED_URL_EXPIRES,
    ], '获取成功');

} catch (Exception $e) {
    vis_log('获取播放链接异常: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
