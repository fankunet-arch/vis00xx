<?php
/**
 * VIS API - Product Quick Create
 * 文件路径: app/vis/api/product_quick_create.php
 * 说明: 产品快速创建/搜索接口
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 仅允许POST请求（除了search操作）
$action = $_POST['action'] ?? $_GET['action'] ?? 'search';

try {
    switch ($action) {
        case 'search':
            // 搜索产品
            $keyword = $_GET['keyword'] ?? '';
            if (empty($keyword)) {
                vis_json_response(false, null, '请输入搜索关键词');
            }

            $products = vis_search_products($pdo, $keyword);
            vis_json_response(true, ['products' => $products]);
            break;

        case 'create':
            // 快速创建产品
            $productName = trim($_POST['product_name'] ?? '');
            $seriesId = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;

            if (empty($productName)) {
                vis_json_response(false, null, '产品名称不能为空');
            }

            $result = vis_create_product($pdo, [
                'product_name' => $productName,
                'series_id' => $seriesId
            ]);

            if ($result['success']) {
                vis_json_response(true, [
                    'id' => $result['id'],
                    'product_name' => $productName
                ], '产品创建成功');
            } else {
                vis_json_response(false, null, $result['message']);
            }
            break;

        case 'list':
            // 获取所有产品
            $seriesId = !empty($_GET['series_id']) ? (int)$_GET['series_id'] : null;
            $products = vis_get_products($pdo, $seriesId);

            vis_json_response(true, ['products' => $products]);
            break;

        default:
            vis_json_response(false, null, '无效的操作');
    }

} catch (Exception $e) {
    vis_log('产品API错误: ' . $e->getMessage(), 'ERROR');
    vis_json_response(false, null, '操作失败: ' . $e->getMessage());
}
