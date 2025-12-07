<?php
/**
 * VIS API - Series Quick Create
 * 文件路径: app/vis/api/series_quick_create.php
 * 说明: 系列快速创建/搜索接口
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // 创建新系列
            $seriesName = trim($_POST['series_name'] ?? '');

            if (empty($seriesName)) {
                vis_json_response(false, null, '系列名称不能为空');
            }

            // 检查是否已存在同名系列
            $stmt = $pdo->prepare("SELECT id FROM vis_series WHERE series_name = ? LIMIT 1");
            $stmt->execute([$seriesName]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // 已存在，返回现有系列ID
                vis_json_response(true, [
                    'id' => $existing['id'],
                    'series_name' => $seriesName,
                    'is_new' => false
                ], '系列已存在');
            }

            // 创建新系列
            $result = vis_create_series($pdo, [
                'series_name' => $seriesName
            ]);

            if ($result['success']) {
                vis_json_response(true, [
                    'id' => $result['id'],
                    'series_name' => $seriesName,
                    'is_new' => true
                ], '系列创建成功');
            } else {
                vis_json_response(false, null, $result['message']);
            }
            break;

        case 'search':
            // 搜索系列（用于自动补全）
            $keyword = trim($_POST['keyword'] ?? $_GET['keyword'] ?? '');

            if (empty($keyword)) {
                $stmt = $pdo->query("SELECT id, series_name FROM vis_series WHERE is_enabled = 1 ORDER BY sort_order, series_name");
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, series_name
                    FROM vis_series
                    WHERE is_enabled = 1
                      AND series_name LIKE ?
                    ORDER BY sort_order, series_name
                ");
                $stmt->execute(['%' . $keyword . '%']);
            }

            $series = $stmt->fetchAll(PDO::FETCH_ASSOC);

            vis_json_response(true, ['series' => $series]);
            break;

        default:
            vis_json_response(false, null, '无效的操作');
    }

} catch (Exception $e) {
    vis_log('系列快速创建异常: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
