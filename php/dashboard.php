<?php 
include 'header.php';
require_once 'config.php';
// 獲取系統公告（假設您有一個公告表）
try {
    $stmt = $pdo->query("SELECT * FROM system_announcements ORDER BY created_at DESC LIMIT 5");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $announcements = [];
}

// 統計個案數量
try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status = 'Unassigned' THEN 1 END) as unassigned_cases,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_cases,
        COUNT(CASE WHEN status = 'Closed' THEN 1 END) as closed_cases
    FROM cases");
    $stmt->execute();
    $case_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $case_stats = [
        'unassigned_cases' => 0, 
        'active_cases' => 0, 
        'closed_cases' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工管理系統 - 首頁</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
    <div class="intro-section">
    <h1>社工個案管理平台</h1>
    <p>這是專為社會工作者設計的個案管理平台，讓您輕鬆管理個案、輸入訪談紀錄、以及查看個案狀況。開始使用，提升工作效率！</p>
    <div class="intro-slider">
        <div class="slider-container">
            <div class="slider-item active">
                <div class="slider-content">
                    <h3>全方位個案管理</h3>
                    <p>集中管理所有個案資料，清晰掌握每位服務對象的需求和狀況</p>
                </div>
            </div>
            <div class="slider-item">
                <div class="slider-content">
                    <h3>詳細訪談紀錄</h3>
                    <p>了解每個個案的最新進度，確保服務目標按計劃推進</p>
                </div>
            </div>
            <div class="slider-item">
                <div class="slider-content">
                    <h3>智能數據分析</h3>
                    <p>透過數據分析發掘服務成效，輔助決策制定與資源分配</p>
                </div>
            </div>
        </div>
        <div class="slider-controls">
            <button class="slider-prev">&#10094;</button>
            <button class="slider-next">&#10095;</button>
        </div>
        <div class="slider-dots"></div>
    </div>
</div>

<style>
.intro-section {
    position: relative;
    text-align: left;
    margin-bottom: 20px;
}

.intro-slider {
    margin-top: 15px;
    position: relative;
    width: 100%;
    overflow: hidden;
}

.slider-container {
    display: flex;
    transition: transform 0.5s ease;
}

.slider-item {
    flex: 0 0 100%;
    display: none;
    background-color: #f4f4f4;
    border-radius: 8px;
    padding: 20px;
}

.slider-item.active {
    display: block;
}

.slider-content {
    text-align: center;
    color: #333;
}

.slider-content h3 {
    margin-bottom: 10px;
    font-size: 1.2em;
    color: #2c3e50;
}

.slider-controls {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
}

.slider-prev, .slider-next {
    background: rgba(0,0,0,0.2);
    border: none;
    color: white;
    padding: 10px;
    cursor: pointer;
    border-radius: 50%;
    width: 40px;
    height: 40px;
}

.slider-dots {
    display: flex;
    justify-content: center;
    margin-top: 10px;
    gap: 10px;
}

.slider-dot {
    width: 10px;
    height: 10px;
    background: #bbb;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s;
}

.slider-dot.active {
    background: #2c3e50;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.slider-container');
    const slides = document.querySelectorAll('.slider-item');
    const prevButton = document.querySelector('.slider-prev');
    const nextButton = document.querySelector('.slider-next');
    const dotsContainer = document.querySelector('.slider-dots');

    let currentSlide = 0;

    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('span');
        dot.classList.add('slider-dot');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });

    const dots = document.querySelectorAll('.slider-dot');

    function goToSlide(slideIndex) {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        
        currentSlide = slideIndex;
        
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    nextButton.addEventListener('click', () => {
        let nextIndex = (currentSlide + 1) % slides.length;
        goToSlide(nextIndex);
    });

    prevButton.addEventListener('click', () => {
        let prevIndex = (currentSlide - 1 + slides.length) % slides.length;
        goToSlide(prevIndex);
    });
});
</script>
        <div class="announcements-section">
            <h2>系統公告</h2>
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                        <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                        <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                        <small><?php echo $announcement['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>目前暫無公告</p>
            <?php endif; ?>
        </div>

        <div class="stats-card">
    <div>
        <h3>待處理案件</h3>
        <p><?php echo $case_stats['unassigned_cases']; ?></p>
    </div>
    <div>
        <h3>進行中個案</h3>
        <p><?php echo $case_stats['active_cases']; ?></p>
    </div>
    <div>
        <h3>已完成案件</h3>
        <p><?php echo $case_stats['closed_cases']; ?></p>
    </div>
</div>

        <div class="support-section">
            <h2>支援與幫助</h2>
            <div class="support-links">
                <h3>
                    <a href="faq.php">📖 常見問題</a>
                    查詢常見問題解答，快速解決您的疑慮。
                </h3>
                <h3>
                    <a href="manual.php">📘 操作手冊</a>
                    查看系統操作指南，學習如何高效使用各項功能。
                </h3>
                <h3>
                    <a href="contact.php">📞 客服聯繫</a>
                    若有任何疑問，請聯繫我們的客服團隊，24小時在線為您服務。
                </h3>
            </div>
        </div>
    </div>
</body>
</html>
