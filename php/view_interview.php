<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工和管理員可以訪問
if ($current_user['user_type'] != 'U' && $current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$case_id = $_GET['case_id'] ?? null;

// 獲取已認領的個案，並包含訪談記錄數量
try {
    $cases_stmt = $pdo->prepare("
        SELECT 
            c.case_id, 
            c.client_name, 
            c.basic_info, 
            c.status, 
            c.created_at,
            (SELECT COUNT(*) FROM interviews WHERE case_id = c.case_id) AS interview_count
        FROM cases c 
        WHERE c.worker_id = ? AND c.status != 'Closed'
        ORDER BY c.created_at DESC
    ");
    $cases_stmt->execute([$current_user['worker_id']]);
    $cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = '無法載入個案列表：' . $e->getMessage();
}

// 如果選擇了案件，顯示該案件的訪談資訊
$case = null;
$interviews = [];

if ($case_id) {
    try {
        // 獲取個案資訊
        $case_stmt = $pdo->prepare("
            SELECT * FROM cases 
            WHERE case_id = ? AND worker_id = ?
        ");
        $case_stmt->execute([$case_id, $current_user['worker_id']]);
        $case = $case_stmt->fetch(PDO::FETCH_ASSOC);

        // 獲取該案件的所有訪談
        $interviews_stmt = $pdo->prepare("
            SELECT * FROM interviews 
            WHERE case_id = ? 
            ORDER BY interview_date DESC
        ");
        $interviews_stmt->execute([$case_id]);
        $interviews = $interviews_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = '無法載入訪談紀錄：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訪談記錄查看</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
    <div class="container">
        <h1>訪談記錄查看</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$case_id): ?>
            <section>
                <h2>個案列表</h2>
                <?php if (empty($cases)): ?>
                    <p>目前沒有進行中的個案。</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>客戶姓名</th>
                                <th>基本資訊</th>
                                <th>訪談次數</th>
                                <th>案件狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cases as $case_item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($case_item['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($case_item['basic_info']); ?></td>
                                <td><?php echo $case_item['interview_count']; ?></td>
                                <td><?php echo $case_item['status'] === 'Active' ? '進行中' : '未認領'; ?></td>
                                <td>
                                    <a href="?case_id=<?php echo $case_item['case_id']; ?>">查看訪談紀錄</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section>
                <h2><?php echo htmlspecialchars($case['client_name']); ?> 的訪談記錄</h2>

                <div class="case-info">
                    <strong>基本資訊：</strong><?php echo htmlspecialchars($case['basic_info']); ?>
                </div>

                <section class="past-interviews">
                    <h3>過往訪談記錄</h3>
                    <?php if (empty($interviews)): ?>
                        <p>尚無訪談記錄</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>訪談日期</th>
                                    <th>地點</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interviews as $interview): ?>
                                <tr>
                                    <td><?php echo $interview['interview_date']; ?></td>
                                    <td><?php echo htmlspecialchars($interview['location']); ?></td>
                                    <td>
                                        <a href="view_single_interview.php?interview_id=<?php echo $interview['interview_id']; ?>">查看詳情</a>
                                        <a href="edit_interview.php?interview_id=<?php echo $interview['interview_id']; ?>">編輯</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="interview.php" class="button-link">返回我的個案</a>
                    <?php endif; ?>
                </section>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>