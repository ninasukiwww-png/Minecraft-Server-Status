<?php
// 管理员页面

// 检查是否已安装
if (!file_exists('installed.lock')) {
    header('Location: install.php');
    exit;
}

require_once 'config.php';
require_once 'db.php';

session_start();

// 检查管理员是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 连接数据库
$db = new Database();

// 处理删除服务器请求
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($db->deleteServer($id)) {
        $success = '服务器已成功删除';
    } else {
        $error = '删除服务器失败';
    }
}

// 处理添加服务器请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_server'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $server_type = isset($_POST['server_type']) ? $_POST['server_type'] : 'java';
    $sort_weight = isset($_POST['sort_weight']) ? intval($_POST['sort_weight']) : 1000;
    $show_player_history = isset($_POST['show_player_history']) ? 1 : 0;
    $show_ip = isset($_POST['show_ip']) ? 1 : 0;
    $ip_description = isset($_POST['ip_description']) ? $_POST['ip_description'] : '';

    if (empty($name) || empty($address)) {
        $error = '请输入服务器名称和地址';
    } else {
        if ($db->addServer($name, $address, $server_type, $sort_weight)) {
            // 获取刚刚添加的服务器ID
            $server_id = $db->getConnection()->insert_id;
            // 设置显示历史在线人数的选项
            $db->setShowPlayerHistory($server_id, $show_player_history);
            // 设置显示IP的选项
            $db->setShowIp($server_id, $show_ip);
            // 设置IP替代描述
            $db->setIpDescription($server_id, $ip_description);
            $success = '服务器已成功添加';
        } else {
            $error = '添加服务器失败';
        }
    }
}

// 处理更新服务器请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_server'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $server_type = isset($_POST['server_type']) ? $_POST['server_type'] : 'java';
    $sort_weight = isset($_POST['sort_weight']) ? intval($_POST['sort_weight']) : null;
    $show_player_history = isset($_POST['show_player_history']) ? 1 : 0;
    $show_ip = isset($_POST['show_ip']) ? 1 : 0;
    $ip_description = isset($_POST['ip_description']) ? $_POST['ip_description'] : '';

    if (empty($name) || empty($address)) {
        $error = '请输入服务器名称和地址';
    } else {
        if ($db->updateServer($id, $name, $address, $server_type, $sort_weight)) {
            // 设置显示历史在线人数的选项
            $db->setShowPlayerHistory($id, $show_player_history);
            // 设置显示IP的选项
            $db->setShowIp($id, $show_ip);
            // 设置IP替代描述
            $db->setIpDescription($id, $ip_description);
            $success = '服务器已成功更新';
        } else {
            $error = '更新服务器失败';
        }
    }
}

// 获取排序参数，默认按排序权重降序
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'sort_weight';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// 获取所有服务器（带排序）
$servers = $db->getAllServers($sort_by, $sort_order);

// 获取要编辑的服务器
$edit_server = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_server = $db->getServerById($_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_TITLE ?> · 管理</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=ZCOOL+KuaiLe&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <div class="header">
            <h1>服务器管理</h1>
            <div class="nav-links">
                <a href="index.php" class="nav-btn">回到主页</a>
                <a href="view_logs.php" class="nav-btn">查看日志</a>
                <a href="api_management.php" class="nav-btn">API管理</a>
                <a href="logout.php" class="nav-btn danger">退出登录</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?= $edit_server ? '编辑服务器' : '添加服务器' ?></h2>
            <form method="post" action="admin.php">
                <?php if ($edit_server): ?>
                    <input type="hidden" name="id" value="<?= $edit_server['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">服务器名称</label>
                    <input type="text" id="name" name="name" value="<?= $edit_server ? $edit_server['name'] : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">服务器地址 (域名或IP:端口)</label>
                    <input type="text" id="address" name="address" value="<?= $edit_server ? $edit_server['address'] : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="server_type">服务器类型</label>
                    <select id="server_type" name="server_type" required>
                        <option value="java" <?= $edit_server && $edit_server['server_type'] === 'java' ? 'selected' : '' ?>>Java版</option>
                        <option value="bedrock" <?= $edit_server && $edit_server['server_type'] === 'bedrock' ? 'selected' : '' ?>>基岩版</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_weight">排序权重（数字越大，越靠前）</label>
                    <input type="number" id="sort_weight" name="sort_weight" min="0" max="9999" value="<?= $edit_server ? $edit_server['sort_weight'] : 1000 ?>" required>
                    <small style="color: #666;">默认值：1000</small>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        显示历史在线人数图表
                        <label class="switch">
                            <input type="checkbox" id="show_player_history" name="show_player_history" value="1"<?= $edit_server && isset($edit_server['show_player_history']) && $edit_server['show_player_history'] ? ' checked' : (empty($edit_server) ? ' checked' : '') ?>>
                            <span class="slider round"></span>
                        </label>
                    </label>
                    <small style="color: #666;">开启表示在服务器状态页面可以查看历史在线人数图表，关闭则默认不显示但仍记录数据</small>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        显示服务器IP地址
                        <label class="switch">
                            <input type="checkbox" id="show_ip" name="show_ip" value="1"<?= $edit_server && isset($edit_server['show_ip']) && $edit_server['show_ip'] ? ' checked' : (empty($edit_server) ? ' checked' : '') ?>>
                            <span class="slider round"></span>
                        </label>
                    </label>
                    <small style="color: #666;">开启表示在服务器状态页面显示IP地址，关闭则隐藏IP地址</small>
                </div>
                <div class="form-group">
                    <label for="ip_description">IP替代描述文本 (不显示IP时使用)</label>
                    <input type="text" id="ip_description" name="ip_description" value="<?= $edit_server ? htmlspecialchars($edit_server['ip_description']) : '' ?>">
                    <small style="color: #666;">当关闭显示IP时，将显示此文本代替IP地址，例如："加群xxxxx以获取ip"</small>
                </div>
                <button type="submit" class="btn" name="<?= $edit_server ? 'update_server' : 'add_server' ?>">
                    <?= $edit_server ? '更新服务器' : '添加服务器' ?>
                </button>
                <?php if ($edit_server): ?>
                    <a href="admin.php" class="btn">取消编辑</a>
                <?php endif; ?>
            </form>
        </div>

        <h2>服务器列表</h2>
        <table>
            <thead>
                <tr>
                    <th><a href="admin.php?sort_by=id&sort_order=<?= ($sort_by === 'id' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">ID</a></th>
                    <th><a href="admin.php?sort_by=name&sort_order=<?= ($sort_by === 'name' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">名称</a></th>
                    <th>地址</th>
                    <th><a href="admin.php?sort_by=server_type&sort_order=<?= ($sort_by === 'server_type' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">类型</a></th>
                    <th><a href="admin.php?sort_by=sort_weight&sort_order=<?= ($sort_by === 'sort_weight' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">排序权重</a></th>
                    <th><a href="admin.php?sort_by=created_at&sort_order=<?= ($sort_by === 'created_at' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">创建时间</a></th>
                    <th><a href="admin.php?sort_by=updated_at&sort_order=<?= ($sort_by === 'updated_at' && $sort_order === 'ASC') ? 'DESC' : 'ASC' ?>">更新时间</a></th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servers->num_rows > 0): ?>
                    <?php while ($server = $servers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $server['id'] ?></td>
                            <td><?= $server['name'] ?></td>
                            <td><?= $server['address'] ?></td>
                            <td><?= $server['server_type'] === 'java' ? 'Java版' : '基岩版' ?></td>
                            <td><?= $server['sort_weight'] ?></td>
                            <td><?= $server['created_at'] ?></td>
                            <td><?= $server['updated_at'] ?></td>
                            <td>
                                <a href="admin.php?action=edit&id=<?= $server['id'] ?>", class="action-btn edit-btn">编辑</a>
                                <a href="admin.php?action=delete&id=<?= $server['id'] ?>" onclick="return confirm('确定要删除这个服务器吗？')" class="action-btn delete-btn">删除</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">暂无服务器，请添加服务器</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>