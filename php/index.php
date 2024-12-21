<?php 
require_once 'config.php';

// 获取系统公告（不需要登录）
try {
    $stmt = $pdo->query("SELECT * FROM system_announcements ORDER BY created_at DESC LIMIT 3");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $announcements = [];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工管理系統 - 歡迎頁</title>
    <link rel="stylesheet" href="css/index.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <div class="landing-container">
        <header class="main-header">
            <div class="logo">
                <h1>社工個案管理平台</h1>
            </div>
            <nav class="main-nav">
                <a href="login.php" class="btn btn-login">登入</a>
                <a href="register.php" class="btn btn-register">註冊</a>
            </nav>
        </header>

        <section class="hero-section">
            <div class="hero-content">
                <h2>提升社工效率，關懷每一個個案</h2>
                <p>專業、安全的個案管理解決方案，簡化您的工作流程，讓愛與關懷更加高效</p>
                <div class="hero-cta">
                    <a href="register.php" class="btn btn-primary">免費註冊</a>
                    <a href="login.php" class="btn btn-secondary">登入帳號</a>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="feature">
                <div class="feature-icon">📋</div>
                <h3>個案管理</h3>
                <p>輕鬆追蹤和管理個案資訊，記錄每一個重要細節</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>資料分析</h3>
                <p>智能統計與報表，協助您做出更明智的決策</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <h3>資料安全</h3>
                <p>最先進的加密技術，確保個案資訊絕對安全</p>
            </div>
        </section>

        <section class="announcements-section">
            <h2>最新公告</h2>
            <div class="announcements-grid">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-card">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                            <small><?php echo $announcement['created_at']; ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>目前暫無公告</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="support-section">
            <h2>需要幫助？</h2>
            <div class="support-links">
                <a href="contact.php">📞 聯絡客服</a>
                <a href="faq.php">📖 常見問題</a>
            </div>
        </section>

        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> 社工個案管理平台。保留所有權利。</p>
        </footer>
    </div>
</body>
</html>