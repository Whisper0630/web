<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工可以訪問
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

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

// 處理放棄或結束個案
if (isset($_GET['abandon_case']) || isset($_GET['close_case'])) {
    $case_id = isset($_GET['abandon_case']) ? $_GET['abandon_case'] : $_GET['close_case'];
    $is_close = isset($_GET['close_case']);

    try {
        if ($is_close) {
            // 結束個案
            $stmt = $pdo->prepare("UPDATE cases SET status = 'Closed' WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $success = '個案已結束';
        } else {
            // 放棄個案
            $stmt = $pdo->prepare("UPDATE cases SET worker_id = NULL, status = 'Unassigned' WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $success = '該個案已被放棄，並重新進入未認領列表。';
        }
    } catch (PDOException $e) {
        $error = '操作失敗：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>個案及訪談管理</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
    <div class="container">
        <h1>個案及訪談管理</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

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
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($case['basic_info']); ?></td>
                            <td><?php echo $case['interview_count']; ?></td>
                            <td><?php echo $case['status'] === 'Active' ? '進行中' : '未認領'; ?></td>
                            <td>
                                <a href="personal_cases.php?case_id=<?php echo $case['case_id']; ?>">
                                    <?php echo $case['interview_count'] == 0 ? '填寫首次訪談' : '新增訪談'; ?>
                                </a>
                                <?php if ($case['interview_count'] > 0): ?>
                                    <a href="view_interview.php?case_id=<?php echo $case['case_id']; ?>">歷史訪談</a>
                                    <a href="?close_case=<?php echo $case['case_id']; ?>">結束個案</a>
                                <?php else: ?>
                                    <a href="?abandon_case=<?php echo $case['case_id']; ?>">放棄個案</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>