<?php
// API管理页面

session_start();

// 检查是否已安装
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 加载API信息
$api_data = [];
$api_file = 'api.json';
if (file_exists($api_file)) {
    $api_data = json_decode(file_get_contents($api_file), true);
}

// 处理获取API最新信息请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_api_info'])) {
    // 目标API URL
    $target_url = API_UPDATE_URL;

    // 尝试获取API信息
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间为10秒

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 检查请求是否成功
    if ($http_code === 200 && !empty($response)) {
        // 解析JSON响应
        $api_data = json_decode($response, true);

        if ($api_data !== null) {
            // 保存到本地api.json文件
            $file_path = 'api.json';
            if (file_put_contents($file_path, json_encode($api_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                // 记录日志
                $log_message = '[' . date('Y-m-d H:i:s') . '] 成功获取并更新API信息';
                file_put_contents('api.log', $log_message . PHP_EOL, FILE_APPEND);
                $success = 'API信息已成功更新!';
                // 重新加载API数据
                $api_data = $api_data;
                // 重新获取Java和Bedrock类型的API
                $java_apis = [];
                $bedrock_apis = [];
                if (isset($api_data['routes'])) {
                    foreach ($api_data['routes'] as $route) {
                        if ($route['type'] === 'java') {
                            $java_apis[] = $route;
                        } elseif ($route['type'] === 'bedrock') {
                            $bedrock_apis[] = $route;
                        }
                    }
                }
            } else {
                // 记录错误日志
                $log_message = '[' . date('Y-m-d H:i:s') . '] 保存API信息失败: 无法写入文件';
                file_put_contents('api.log', $log_message . PHP_EOL, FILE_APPEND);
                $error = '保存API信息失败: 无法写入文件';
            }
        } else {
            // 记录错误日志
            $log_message = '[' . date('Y-m-d H:i:s') . '] 解析API响应失败: JSON格式无效';
            file_put_contents('api.log', $log_message . PHP_EOL, FILE_APPEND);
            $error = '解析API响应失败: JSON格式无效';
        }
    } else {
        // 记录错误日志
        $error_msg = empty($response) ? '无响应' : 'HTTP错误码: ' . $http_code;
        $log_message = '[' . date('Y-m-d H:i:s') . '] 获取API信息失败: ' . $error_msg;
        file_put_contents('api.log', $log_message . PHP_EOL, FILE_APPEND);
        $error = '获取API信息失败: ' . $error_msg;
    }
}

// 处理API选择请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_selection'])) {
    $selected_java_api = $_POST['java_api'];
    $selected_bedrock_api = $_POST['bedrock_api'];

    // 保存选择到配置
    $config = [
        'selected_java_api' => $selected_java_api,
        'selected_bedrock_api' => $selected_bedrock_api
    ];

    file_put_contents('api_selection.json', json_encode($config, JSON_PRETTY_PRINT));
    $success = 'API选择已保存';
}

// 加载已选择的API
$selected_apis = [];
$selection_file = 'api_selection.json';
if (file_exists($selection_file)) {
    $selected_apis = json_decode(file_get_contents($selection_file), true);
}

// 获取Java和Bedrock类型的API
$java_apis = [];
$bedrock_apis = [];
if (isset($api_data['routes'])) {
    foreach ($api_data['routes'] as $route) {
        if ($route['type'] === 'java') {
            $java_apis[] = $route;
        } elseif ($route['type'] === 'bedrock') {
            $bedrock_apis[] = $route;
        }
    }
}

// 加载默认选择
$selected_java_api = isset($selected_apis['selected_java_api']) ? $selected_apis['selected_java_api'] : (isset($java_apis[0]['api_url']) ? $java_apis[0]['api_url'] : '');
$selected_bedrock_api = isset($selected_apis['selected_bedrock_api']) ? $selected_apis['selected_bedrock_api'] : (isset($bedrock_apis[0]['api_url']) ? $bedrock_apis[0]['api_url'] : '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_TITLE ?> · API管理</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=ZCOOL+KuaiLe&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <div class="header">
            <h1>API管理</h1>
            <div class="nav-links">
                <a href="admin.php" class="nav-btn">← 返回管理页面</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <h2>选择API</h2>
        <form method="post" action="api_management.php" class="form-container">
            <div class="form-group">
                <label for="java_api">Java版服务器API:</label>
                <select id="java_api" name="java_api">
                    <?php foreach ($java_apis as $api): ?>
                        <option value="<?= $api['api_url'] ?>" <?= ($api['api_url'] === $selected_java_api) ? 'selected' : '' ?>><?= $api['name'] ?> (<?= $api['api_url'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bedrock_api">基岩版服务器API:</label>
                <select id="bedrock_api" name="bedrock_api">
                    <?php foreach ($bedrock_apis as $api): ?>
                        <option value="<?= $api['api_url'] ?>" <?= ($api['api_url'] === $selected_bedrock_api) ? 'selected' : '' ?>><?= $api['name'] ?> (<?= $api['api_url'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="save_api_selection" class="btn">保存选择</button>
            <button type="submit" name="fetch_api_info" class="btn" style="background: rgba(76,175,80,0.20); color: #81c784;">获取API最新信息</button>
        </form>

        <h2>API信息</h2>
        <div class="api-list">
            <?php if (empty($api_data['routes'])): ?>
                <p style="color: rgba(200,235,250,0.6);">没有可用的API信息，请先获取API最新信息。</p>
            <?php else: ?>
                <h3 style="font-family: var(--font-title); font-weight: 400; color: rgba(200,235,250,0.7); margin: 1rem 0 0.6rem;">Java版API</h3>
                <?php if (empty($java_apis)): ?>
                    <p style="color: rgba(200,235,250,0.6);">没有可用的Java版API。</p>
                <?php else: ?>
                    <?php foreach ($java_apis as $api): ?>
                        <div class="api-item">
                            <div class="api-info" style="font-weight: 600;"><?= $api['name'] ?></div>
                            <div class="api-info"><small><?= $api['api_url'] ?></small></div>
                            <div class="api-website" style="margin-top: 0.4rem;"><a href="<?= $api['website'] ?>" target="_blank" style="color: var(--color-accent); font-size: 0.85rem;">官方网站</a></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h3 style="font-family: var(--font-title); font-weight: 400; color: rgba(200,235,250,0.7); margin: 1rem 0 0.6rem;">基岩版API</h3>
                <?php if (empty($bedrock_apis)): ?>
                    <p style="color: rgba(200,235,250,0.6);">没有可用的基岩版API。</p>
                <?php else: ?>
                    <?php foreach ($bedrock_apis as $api): ?>
                        <div class="api-item">
                            <div class="api-info" style="font-weight: 600;"><?= $api['name'] ?></div>
                            <div class="api-info"><small><?= $api['api_url'] ?></small></div>
                            <div class="api-website" style="margin-top: 0.4rem;"><a href="<?= $api['website'] ?>" target="_blank" style="color: var(--color-accent); font-size: 0.85rem;">官方网站</a></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>