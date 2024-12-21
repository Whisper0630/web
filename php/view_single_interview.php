<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工和管理員可以訪問
if ($current_user['user_type'] != 'U' && $current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

$interview_id = $_GET['interview_id'] ?? null;
$error = '';

if (!$interview_id) {
    header("Location: end_interview.php");
    exit();
}

// 取得訪談詳細資訊
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*, 
            c.client_name, 
            c.basic_info, 
            sw.full_name as worker_name
        FROM interviews i
        JOIN cases c ON i.case_id = c.case_id
        JOIN social_workers sw ON i.worker_id = sw.worker_id
        WHERE i.interview_id = ?
    ");
    $stmt->execute([$interview_id]);
    $interview = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$interview) {
        $error = '找不到此訪談紀錄';
    }
} catch (PDOException $e) {
    $error = '無法載入訪談紀錄：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訪談紀錄詳情</title>
    <link rel="stylesheet" href="css/view_single_interview.css">
</head>
<body>
    <div class="container">
        <h1>訪談紀錄詳情</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="interview-details">
                <div class="detail-section">
                    <h2><?php echo htmlspecialchars($interview['client_name']); ?> 的訪談紀錄</h2>
                    
                    <div class="detail-row">
                        <strong>基本資訊：</strong>
                        <?php echo htmlspecialchars($interview['basic_info']); ?>
                    </div>
                    
                    <div class="detail-row">
                        <strong>社工姓名：</strong>
                        <?php echo htmlspecialchars($interview['worker_name']); ?>
                    </div>
                    
                    <div class="detail-row">
                        <strong>訪談日期：</strong>
                        <?php echo htmlspecialchars($interview['interview_date']); ?>
                    </div>
                    
                    <div class="detail-row">
                        <strong>訪談地點：</strong>
                        <?php echo htmlspecialchars($interview['location']); ?>
                    </div>
                    
                    <div class="detail-row">
                        <strong>訪談內容：</strong>
                        <div class="notes-content">
                            <?php echo nl2html($interview['notes']); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($interview['additional_info'])): ?>
                    <div class="detail-row">
                        <strong>補充資訊：</strong>
                        <div class="additional-info">
                            <?php echo nl2html($interview['additional_info']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($interview['photos'])): ?>
                <div class="photo-section">
                    <h3>訪談照片</h3>
                    <div class="photo-gallery">
                        <?php 
                        $photos = explode(',', $interview['photos']);
                        foreach ($photos as $photo): 
                        ?>
                            <div class="photo-item">
                                <a href="<?php echo htmlspecialchars($photo); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="訪談照片">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="navigation">
                <a href="view_interview.php?case_id=<?php echo $interview['case_id']; ?>" class="button-link">返回歷史訪談</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// 輔助函數，將換行符轉換為 HTML 換行
function nl2html($text) {
    return nl2br(htmlspecialchars($text));
}
?>