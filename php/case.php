<?php
include 'header.php';
require_once 'config.php';

// 確保使用者為社工
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['claim_case'])) {
    $case_id = $_POST['case_id'];

    try {
        // 開始資料庫事務
        $pdo->beginTransaction();

        // 確認個案是否未被認領
        $check_stmt = $pdo->prepare("SELECT status FROM cases WHERE case_id = ? AND worker_id IS NULL AND status = 'Unassigned'");
        $check_stmt->execute([$case_id]);
        $case = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($case) {
            // 更新個案的工作人員 ID 和狀態
            $update_stmt = $pdo->prepare("UPDATE cases SET worker_id = ?, status = 'Active' WHERE case_id = ? AND worker_id IS NULL AND status = 'Unassigned'");
            $result = $update_stmt->execute([$current_user['worker_id'], $case_id]);

            if ($result) {
                $success = '個案認領成功';
                $pdo->commit();
            } else {
                $error = '更新失敗，請重試';
                $pdo->rollBack();
            }
        } else {
            $error = '個案已被認領或不存在';
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = '系統錯誤：' . $e->getMessage();
    }
}

// 獲取未認領的個案
$unclaimed_cases = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM cases WHERE worker_id IS NULL AND status = 'Unassigned'");
    $stmt->execute();
    $unclaimed_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = '無法加載未認領的個案';
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/case.css">
    <title>認領個案</title>
</head>
<body>
    <h1>未認領個案</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="case.php">
        <table border="1">
            <tr>
                <th>個案編號</th>
                <th>客戶名稱</th>
                <th>基本資訊</th>
                <th>操作</th>
            </tr>
            <?php foreach ($unclaimed_cases as $case): ?>
                <tr>
                    <td><?php echo htmlspecialchars($case['case_id']); ?></td>
                    <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($case['basic_info']); ?></td>
                    <td>
                        <button type="submit" name="claim_case" value="1">
                            認領
                        </button>
                        <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case['case_id']); ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
</body>
</html>
