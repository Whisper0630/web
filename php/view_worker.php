<?php
include 'header.php';
require_once 'config.php';

// 確保只有管理員可以訪問
if ($current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

// 確認是否有傳入社工ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = '無效的社工ID';
    header("Location: social_worker_management.php");
    exit();
}

$worker_id = $_GET['id'];

try {
    // 獲取社工基本資訊
    $worker_stmt = $pdo->prepare("SELECT * FROM social_workers WHERE worker_id = ?");
    $worker_stmt->execute([$worker_id]);
    $worker = $worker_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$worker) {
        $_SESSION['error_message'] = '找不到該社工';
        header("Location: social_worker_management.php");
        exit();
    }

    // 計算已解決的個案數量
    $closed_cases_stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE worker_id = ? AND status = 'Closed'");
    $closed_cases_stmt->execute([$worker_id]);
    $closed_cases_count = $closed_cases_stmt->fetchColumn();

    // 計算進行中的個案數量
    $active_cases_stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE worker_id = ? AND status = 'Active'");
    $active_cases_stmt->execute([$worker_id]);
    $active_cases_count = $active_cases_stmt->fetchColumn();

    // 獲取最近的5個個案
    $recent_cases_stmt = $pdo->prepare("SELECT case_id, client_name, status, created_at 
                                        FROM cases 
                                        WHERE worker_id = ? 
                                        ORDER BY created_at DESC 
                                        LIMIT 5");
    $recent_cases_stmt->execute([$worker_id]);
    $recent_cases = $recent_cases_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 計算總訪談次數
    $total_interviews_stmt = $pdo->prepare("SELECT COUNT(*) FROM interviews WHERE worker_id = ?");
    $total_interviews_stmt->execute([$worker_id]);
    $total_interviews_count = $total_interviews_stmt->fetchColumn();

} catch(PDOException $e) {
    $_SESSION['error_message'] = '系統錯誤：' . $e->getMessage();
    header("Location: social_worker_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工詳細資訊</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .worker-profile {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .worker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .worker-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #f1f1f1;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .recent-cases table {
            width: 100%;
            border-collapse: collapse;
        }
        .recent-cases th, .recent-cases td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .recent-cases th {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="worker-profile">
        <div class="worker-header">
            <div>
                <h1><?php echo htmlspecialchars($worker['full_name']); ?></h1>
                <p>用戶名：<?php echo htmlspecialchars($worker['username']); ?></p>
                <p>職位：<?php echo $worker['user_type'] == 'M' ? '管理員' : '社工'; ?></p>
            </div>
            <div>
                <p>聯絡電話：<?php echo htmlspecialchars($worker['contact_number']); ?></p>
                <p>加入日期：<?php echo $worker['created_at']; ?></p>
                <p>最後登入：<?php echo $worker['last_login'] ?? '從未登入'; ?></p>
            </div>
        </div>

        <div class="worker-stats">
            <div class="stat-card">
                <h3>已解決個案</h3>
                <p><?php echo $closed_cases_count; ?> 個</p>
            </div>
            <div class="stat-card">
                <h3>進行中個案</h3>
                <p><?php echo $active_cases_count; ?> 個</p>
            </div>
            <div class="stat-card">
                <h3>總訪談次數</h3>
                <p><?php echo $total_interviews_count; ?> 次</p>
            </div>
        </div>

        <div class="recent-cases">
            <h2>最近個案</h2>
            <table>
                <thead>
                    <tr>
                        <th>個案編號</th>
                        <th>客戶名稱</th>
                        <th>狀態</th>
                        <th>建立日期</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_cases as $case): ?>
                        <tr>
                            <td><?php echo $case['case_id']; ?></td>
                            <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td>
                                <?php 
                                switch($case['status']) {
                                    case 'Active': echo '進行中'; break;
                                    case 'Closed': echo '已結束'; break;
                                    case 'Unassigned': echo '未分配'; break;
                                    default: echo $case['status'];
                                }
                                ?>
                            </td>
                            <td><?php echo $case['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>