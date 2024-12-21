<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工和管理員可以訪問
if ($current_user['user_type'] != 'U' && $current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

$case_id = $_GET['case_id'] ?? null;
$error = '';

if (!$case_id) {
    header("Location: end_interview.php");
    exit();
}

// 取得案件詳細資訊
try {
    $case_stmt = $pdo->prepare("
        SELECT 
            case_id, 
            client_name, 
            basic_info, 
            status, 
            created_at
        FROM cases 
        WHERE case_id = ? AND status = 'Closed'
    ");
    $case_stmt->execute([$case_id]);
    $case = $case_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        $error = '找不到此已結束案件';
    }

    // 獲取該案件的所有訪談
    $interviews_stmt = $pdo->prepare("
        SELECT 
            interview_id, 
            interview_date, 
            location,
            worker_id
        FROM interviews 
        WHERE case_id = ? 
        ORDER BY interview_date DESC
    ");
    $interviews_stmt->execute([$case_id]);
    $interviews = $interviews_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 取得社工姓名
    $workers_stmt = $pdo->prepare("
        SELECT worker_id, full_name 
        FROM social_workers
    ");
    $workers_stmt->execute();
    $workers = $workers_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) {
    $error = '無法載入訪談紀錄：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>已結束案件訪談記錄</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($case['client_name']); ?> 的訪談記錄</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <section>
                <h2>案件基本資訊</h2>
                <div class="case-info">
                    <div><strong>客戶姓名：</strong> <?php echo htmlspecialchars($case['client_name']); ?></div>
                    <div><strong>基本資訊：</strong> <?php echo htmlspecialchars($case['basic_info']); ?></div>
                    <div><strong>建立日期：</strong> <?php echo $case['created_at']; ?></div>
                    <div><strong>案件狀態：</strong> 已結束</div>
                </div>
            </section>

            <section class="past-interviews">
                <h3>訪談記錄</h3>
                <?php if (empty($interviews)): ?>
                    <p>尚無訪談記錄</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>訪談日期</th>
                                <th>地點</th>
                                <th>社工</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interviews as $interview): ?>
                            <tr>
                                <td><?php echo $interview['interview_date']; ?></td>
                                <td><?php echo htmlspecialchars($interview['location']); ?></td>
                                <td><?php echo htmlspecialchars($workers[$interview['worker_id']] ?? '未知'); ?></td>
                                <td>
                                    <a href="view_end_single_interview.php?interview_id=<?php echo $interview['interview_id']; ?>">查看詳情</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <div class="navigation">
                <a href="end_interview.php" class="button-link">返回已結束個案列表</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>