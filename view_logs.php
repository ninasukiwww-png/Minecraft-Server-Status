<?php
// 日志查看页面 - 显示API调用日志

// 检查是否已安装
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';

// 检查是否已登录
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 日志文件路径
$log_file = 'api.log';

// 获取日志内容 - 只显示当天的日志
$log_content = '';
if (file_exists($log_file)) {
    try {
        // 设置最大读取行数和最大文件大小限制
        $max_lines = 500; // 最多显示500行
        $max_file_size = 500 * 1024; // 最大500KB
        
        // 获取当前日期（格式：YYYY-MM-DD）
        $current_date = date('Y-m-d');
        
        // 检查文件大小
        $file_size = filesize($log_file);
        
        if ($file_size > 0) {
            // 读取文件内容并筛选当天的日志
            $file = fopen($log_file, 'r');
            if ($file) {
                $today_lines = [];
                $line_count = 0;
                
                // 逐行读取并筛选当天的日志
                while (($line = fgets($file)) !== false) {
                    // 检查日志行是否包含今天的日期
                    if (strpos($line, "[$current_date") !== false) {
                        $today_lines[] = $line;
                        $line_count++;
                        
                        // 如果达到最大行数限制，停止读取
                        if ($line_count >= $max_lines) {
                            break;
                        }
                    }
                }
                fclose($file);
                
                // 如果有当天的日志行
                if (!empty($today_lines)) {
                    $log_content = implode('', $today_lines);
                } else {
                    // 没有当天的日志，但文件不为空，可能是文件刚刚被归档
                    $log_content = "[今天暂无新的日志记录]\n";
                }
            } else {
                $log_content = "[错误: 无法打开日志文件]";
            }
        }
    } catch (Exception $e) {
        $log_content = "[错误: 读取日志时发生异常 - " . $e->getMessage() . "]";
    }
}

// 清空日志功能
if (isset($_POST['clear_log'])) {
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
        header('Location: view_logs.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_TITLE ?> · 日志</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=ZCOOL+KuaiLe&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <div class="header">
            <h1>API调用日志</h1>
            <div class="nav-links">
                <a href="admin.php" class="nav-btn">服务器管理</a>
                <a href="view_logs.php" class="nav-btn">查看日志</a>
                <a href="index.php" class="nav-btn">返回首页</a>
                <a href="logout.php" class="nav-btn danger">退出登录</a>
            </div>
        </div>
        <div class="log-list">
            <form method="post" style="margin-bottom: 1rem; text-align: right;">
                <button type="submit" name="clear_log" class="btn" onclick="return confirm('确定要清空所有日志吗？');">清空日志</button>
            </form>
            <?php if (!empty($log_content)): ?>
                <?php
                $log_lines = explode("\n", $log_content);
                foreach ($log_lines as $line):
                    if (empty(trim($line))) continue;
                ?>
                <div class="log-entry-line">
                    <span class="log-time"><?= htmlspecialchars(mb_substr($line, 0, 19)) ?></span>
                    <span class="log-message"><?= htmlspecialchars($line) ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="log-entry-line" style="text-align: center; color: rgba(200,235,250,0.4);">暂无日志记录</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>