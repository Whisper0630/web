<?php
session_start();

// 檢查是否已登入
if (!isset($_SESSION['worker_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// 獲取當前登入用戶資訊
try {
    $stmt = $pdo->prepare("SELECT * FROM social_workers WHERE worker_id = ?");
    $stmt->execute([$_SESSION['worker_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("取得用戶資訊失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工管理系統</title>
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            社工管理系統
        </div>
        
        <div class="navbar-menu">
            <a href="dashboard.php">首頁</a>
            
            <?php if ($current_user['user_type'] == 'U'): ?>
                <a href="social_worker.php">社工資料</a>
                <a href="case.php">認領個案</a>
                <a href="interview.php">我的個案</a>
                <a href="end_interview.php">過往訪談紀錄</a>
            <?php else: ?>
                <a href="social_worker_management.php">社工管理</a>
                <a href="case_management.php">個案管理</a>
                <a href="add_case.php">新增個案</a>
            <?php endif; ?>
        </div>
        
        <div class="navbar-user">
            <span>歡迎, <?php echo htmlspecialchars($current_user['full_name']); ?></span>
            <a href="profile_edit.php" class="profile-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                編輯個人資料
            </a>
            <a href="logout.php">登出</a>
        </div>
    </nav>
</body>
</html>