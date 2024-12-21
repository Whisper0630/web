<?php
session_start();
require_once 'config.php';
require_once 'header 2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "請輸入用戶名和密碼";
    } else {
        try {
            // 使用加密密碼查詢
            $hashed_password = hash('sha256', $password);
            $stmt = $pdo->prepare("SELECT * FROM social_workers WHERE username = ? AND password = ?");
            $stmt->execute([$username, $hashed_password]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 登入成功，設置session
                $_SESSION['worker_id'] = $user['worker_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // 更新最後登入時間
                $update_stmt = $pdo->prepare("UPDATE social_workers SET last_login = CURRENT_TIMESTAMP WHERE worker_id = ?");
                $update_stmt->execute([$user['worker_id']]);
                
                // 根據用戶類型跳轉
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "用戶名或密碼錯誤";
            }
        } catch(PDOException $e) {
            $error = "發生錯誤: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工系統 - 登入</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <form method="post" action="">
            <h2>社工系統 - 登入</h2>
            
            <?php 
            if (isset($_GET['registered']) && $_GET['registered'] == 'true') {
                echo '<div class="success">註冊成功，請登入</div>';
            }
            
            if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>用戶名</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">登入</button>
            <p>還沒有帳號？ <a href="register.php">註冊</a></p>
        </form>
    </div>
</body>
</html>

<?php 
// 先建立公告表（如果尚未建立）
try {
    $stmt = $pdo->query("CREATE TABLE IF NOT EXISTS system_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 插入預設公告（如果表是空的）
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM system_announcements");
    if ($count_stmt->fetchColumn() == 0) {
        $default_announcements = [
            ['title' => '系統升級通知', 'content' => '本系統將於近期進行功能優化，預計不會影響正常使用。'],
            ['title' => '隱私保護聲明', 'content' => '我們承諾嚴格保護所有用戶和個案資訊，遵守最高的資料安全標準。']
        ];

        $insert_stmt = $pdo->prepare("INSERT INTO system_announcements (title, content) VALUES (?, ?)");
        foreach ($default_announcements as $announcement) {
            $insert_stmt->execute([$announcement['title'], $announcement['content']]);
        }
    }
} catch(PDOException $e) {
    // 處理錯誤
    error_log("資料庫初始化錯誤: " . $e->getMessage());
}
?>