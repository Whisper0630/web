<?php
include 'header.php';
require_once 'config.php';

// 確保使用者為社工
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$ended_cases = [];

// 獲取已結束的個案及其訪談記錄
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.case_id,
            c.client_name,
            c.basic_info,
            c.status,
            (
                SELECT COUNT(*)
                FROM interviews i
                WHERE i.case_id = c.case_id
            ) AS interview_count
        FROM cases c
        WHERE c.status = 'Closed'
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute();
    $ended_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = '無法載入已結束的訪談：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>已結束個案訪談紀錄</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
    <div class="container">
        <h1>已結束個案訪談紀錄</h1>

        <?php if ($error): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (empty($ended_cases)): ?>
            <p>目前尚無已結束的個案。</p>
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
                    <?php foreach ($ended_cases as $case): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($case['basic_info']); ?></td>
                        <td><?php echo $case['interview_count']; ?></td>
                        <td>已結束</td>
                        <td>
                            <a href="view_closed_case_interviews.php?case_id=<?php echo $case['case_id']; ?>">查看訪談紀錄</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>