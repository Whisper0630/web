<?php
include 'header.php';
require_once 'config.php';

// 確保只有管理員和社工可以訪問
if ($current_user['user_type'] != 'M' && $current_user['user_type'] != 'S') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 輸入驗證
    $client_name = trim($_POST['client_name']);
    $client_contact = trim($_POST['client_contact']);
    $basic_info = trim($_POST['basic_info']);

    // 驗證必填欄位
    if (empty($client_name) || empty($client_contact) || empty($basic_info)) {
        $error = '所有欄位都為必填';
    } else {
        try {
            // 明確設定 worker_id 為 NULL 和狀態為 'Unassigned'
            $stmt = $pdo->prepare("INSERT INTO cases (client_name, client_contact, basic_info, worker_id, status, created_at, updated_at) VALUES (?, ?, ?, NULL, 'Unassigned', NOW(), NOW())");
            $result = $stmt->execute([$client_name, $client_contact, $basic_info]);

            if ($result) {
                $success = '個案新增成功，已進入未認領列表';
            } else {
                $error = '個案新增失敗';
            }
        } catch (PDOException $e) {
            // 記錄詳細錯誤日誌
            error_log('新增個案錯誤：' . $e->getMessage());
            $error = '系統發生錯誤，請稍後再試';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>新增個案</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/add_case.css">
</head>
<body>
    <div class="container">
        <div class="case-form">
            <h1 class="text-center">新增個案</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" onsubmit="this.querySelector('button').disabled = true;">
                <div class="form-group">
                    <label for="client_name">客戶姓名：</label>
                    <input type="text" id="client_name" name="client_name" 
                           class="form-control"
                           placeholder="請輸入客戶姓名" 
                           maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="client_contact">聯絡方式：</label>
                    <input type="text" id="client_contact" name="client_contact" 
                           class="form-control"
                           placeholder="請輸入聯絡方式" 
                           maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="basic_info">基本資訊：</label>
                    <textarea id="basic_info" name="basic_info" 
                              class="form-control"
                              placeholder="請輸入客戶基本資訊" 
                              maxlength="500" required></textarea>
                </div>
                <div class="form-group text-center">
                    <button type="submit" class="btn btn-primary">新增個案</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
