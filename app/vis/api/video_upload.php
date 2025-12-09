<?php
/**
 * VIS API - Video Upload
 * 文件路径: app/vis/api/video_upload.php
 * 说明: 处理视频上传（上传流程：本地临时 -> R2 -> 数据库）
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
    // 检查是否有文件上传
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过php.ini限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只上传了一部分',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => 'PHP扩展停止了文件上传',
        ];

        $error = $_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE;
        $message = $errorMessages[$error] ?? '文件上传失败';

        vis_json_response(false, null, $message);
    }

    // 获取表单数据
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'product');

    // 多系列支持 (Tags)
    $seriesNames = $_POST['series_names'] ?? [];
    if (!is_array($seriesNames)) {
        $seriesNames = array_filter(explode(',', $seriesNames));
    }

    $platform = trim($_POST['platform'] ?? 'other');
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;  // 前端传来的时长（秒）
    $coverBase64 = trim($_POST['cover_base64'] ?? '');  // 前端传来的封面图（Base64）
    $createdBy = $_SESSION['user_login'] ?? 'system';

    // 新增：产品、系列、季节 (支持直接传入名称进行创建)
    $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $seriesId = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;
    $seasonId = !empty($_POST['season_id']) ? (int)$_POST['season_id'] : null;

    $productName = trim($_POST['product_name'] ?? '');
    $seriesName = trim($_POST['series_name'] ?? '');

    // 验证标题
    if (empty($title)) {
        vis_json_response(false, null, '请输入视频标题');
    }

    // 获取上传文件信息
    $uploadedFile = $_FILES['video'];
    $tmpPath = $uploadedFile['tmp_name'];
    $originalFilename = $uploadedFile['name'];
    $fileSize = $uploadedFile['size'];
    $mimeType = $uploadedFile['type'];

    // 检查文件大小
    if ($fileSize > VIS_MAX_FILE_SIZE) {
        vis_json_response(false, null, '文件大小超过限制（最大' . round(VIS_MAX_FILE_SIZE / 1024 / 1024) . 'MB）');
    }

    // 获取文件扩展名
    $pathInfo = pathinfo($originalFilename);
    $extension = strtolower($pathInfo['extension'] ?? '');

    // 验证文件类型
    if (!vis_validate_file_type($mimeType, $extension)) {
        vis_json_response(false, null, '不支持的文件类型，仅支持 mp4 和 mov 格式');
    }

    // 生成R2存储路径
    $r2Key = vis_generate_r2_key($extension);

    // 上传视频到R2
    vis_log("开始上传视频到R2: {$r2Key}", 'INFO');
    $uploadResult = vis_upload_to_r2($tmpPath, $r2Key, $mimeType);

    if (!$uploadResult['success']) {
        vis_json_response(false, null, '上传到云存储失败: ' . $uploadResult['message']);
    }

    // 处理封面图上传（如果前端提供了）
    $coverUrl = null;
    if (!empty($coverBase64)) {
        try {
            // 解码 Base64 图片
            $coverData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $coverBase64));

            if ($coverData !== false) {
                // 生成封面图存储路径（与视频同目录，jpg格式）
                $coverKey = str_replace('.' . $extension, '.jpg', $r2Key);

                // 保存到临时文件
                $coverTmpPath = VIS_UPLOAD_TEMP_DIR . '/' . uniqid('cover_') . '.jpg';
                file_put_contents($coverTmpPath, $coverData);

                // 上传封面到R2
                vis_log("上传封面图到R2: {$coverKey}", 'INFO');
                $coverUploadResult = vis_upload_to_r2($coverTmpPath, $coverKey, 'image/jpeg');

                if ($coverUploadResult['success']) {
                    // 使用 R2 的签名 URL 或自定义域名
                    $coverUrl = VIS_R2_PUBLIC_URL . '/' . $coverKey;
                } else {
                    vis_log("封面图上传失败（不影响视频上传）: " . $coverUploadResult['message'], 'WARNING');
                }

                // 删除临时封面文件
                @unlink($coverTmpPath);
            }
        } catch (Exception $e) {
            vis_log("封面图处理异常（不影响视频上传）: " . $e->getMessage(), 'WARNING');
        }
    }

    // [方案B 重构] 开启事务处理
    // 简化逻辑：系列创建已内置在 vis_create_video() 中，这里只需处理产品
    $pdo->beginTransaction();

    try {
        // 1. [重构] 移除独立的系列创建逻辑
        // 系列创建现在由 vis_create_video() 统一处理（通过 series_names 数组）

        // 2. 处理产品（如果未提供ID但提供了名称）
        $productSeriesName = null; // 用于存储产品关联的系列名称（作为默认建议）

        if (empty($productId) && !empty($productName)) {
            // 检查产品是否存在
            $stmt = $pdo->prepare("
                SELECT p.id, s.series_name
                FROM vis_products p
                LEFT JOIN vis_series s ON p.series_id = s.id
                WHERE p.product_name = ?
                LIMIT 1
            ");
            $stmt->execute([$productName]);
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingProduct) {
                $productId = $existingProduct['id'];
                // [维度4 解耦] 产品的系列仅作为"默认建议"
                $productSeriesName = $existingProduct['series_name'];
            } else {
                // 创建新产品（使用第一个系列标签或 seriesName 作为产品的系列）
                $productSeriesId = null;
                if (!empty($seriesName)) {
                    $productSeriesId = _vis_ensure_series_exists($pdo, $seriesName);
                }

                $productResult = vis_create_product($pdo, [
                    'product_name' => $productName,
                    'series_id' => $productSeriesId
                ]);

                if (!$productResult['success']) {
                    throw new Exception('创建产品失败: ' . $productResult['message']);
                }
                $productId = $productResult['id'];
                $productSeriesName = $seriesName;
            }
        }

        // 3. [维度4 解耦] 将产品的系列作为默认建议添加到 series_names
        // 但用户可以自由删除或修改（数据库层面不强制一致）
        if (!empty($productSeriesName) && empty($seriesNames)) {
            // 仅当用户没有手动指定系列标签时，才使用产品的系列作为默认值
            $seriesNames = [$productSeriesName];
        }

        // 4. [兼容] 如果用户使用了旧的 seriesName 单选字段，合并到 seriesNames 数组
        if (!empty($seriesName) && !in_array($seriesName, $seriesNames)) {
            $seriesNames[] = $seriesName;
        }

        // 创建数据库记录
        $videoData = [
            'title' => $title,
            'platform' => $platform,
            'category' => $category,
            'series_names' => array_filter($seriesNames), // 传递系列标签数组（过滤空值）
            'product_id' => $productId,
            // [重构] 移除 series_id 参数，统一使用 series_names
            'season_id' => $seasonId,
            'r2_key' => $r2Key,
            'cover_url' => $coverUrl,
            'duration' => $duration,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'original_filename' => $originalFilename,
            'created_by' => $createdBy,
        ];

        $createResult = vis_create_video($pdo, $videoData);

        if (!$createResult['success']) {
            throw new Exception('创建视频记录失败: ' . $createResult['message']);
        }

        $pdo->commit();

        // 删除临时文件
        @unlink($tmpPath);

        vis_json_response(true, [
            'id' => $createResult['id'],
            'title' => $title,
            'r2_key' => $r2Key,
        ], '视频上传成功');

    } catch (Exception $e) {
        $pdo->rollBack();

        // 如果数据库操作失败，需要清理已上传到R2的文件 (因为事务回滚不回滚R2文件)
        vis_delete_from_r2($r2Key);
        if (!empty($coverUrl)) {
             // 提取key并删除
             $coverKey = str_replace(VIS_R2_PUBLIC_URL . '/', '', $coverUrl);
             vis_delete_from_r2($coverKey);
        }

        throw $e; // 抛出给外层catch处理
    }

} catch (Exception $e) {
    vis_log('视频上传异常: ' . $e->getMessage(), 'ERROR');
    // 确保临时文件被清理
    if (isset($tmpPath) && file_exists($tmpPath)) {
        @unlink($tmpPath);
    }
    vis_json_response(false, null, '系统错误: ' . $e->getMessage());
}
