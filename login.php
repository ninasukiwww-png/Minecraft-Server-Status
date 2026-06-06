<?php
// 登录页面

// 检查是否已安装
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';

session_start();

// 如果用户已登录，重定向到管理页面
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 验证输入
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        // 连接数据库
        $db = new Database();

        // 查询管理员信息
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // 验证密码
            if (password_verify($password, $admin['password'])) {
                // 设置会话
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];

                // 重定向到管理页面
                header('Location: admin.php');
                exit;
            } else {
                $error = '密码错误';
            }
        } else {
            $error = '用户名不存在';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_TITLE ?> · 登录</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=ZCOOL+KuaiLe&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>管理员登录</h1>
        <?php if (!empty($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
    </div>
</body>
</html>