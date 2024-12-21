<?php
include 'header.php';
require_once 'config.php';

// 确保只有管理员和社工可以访问
if ($current_user['user_type'] != 'M' && $current_user['user_type'] != 'S') {
    header("Location: dashboard.php");
    exit();
}

// 检查是否传入案例ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: case_management.php");
    exit();
}

$case_id = $_GET['id'];
$error = '';
$success = '';

// 获取当前个案信息
try {
    $stmt = $pdo->prepare("SELECT * FROM cases WHERE case_id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$case) {
        header("Location: case_management.php");
        exit();
    }
} catch(PDOException $e) {
    $error = '獲取個案資料失敗：' . $e->getMessage();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = $_POST['client_name'];
    $client_contact = $_POST['client_contact'];
    $basic_info = $_POST['basic_info'];

    try {
        // 更新个案
        $stmt = $pdo->prepare("UPDATE cases SET client_name = ?, client_contact = ?, basic_info = ?, updated_at = NOW() WHERE case_id = ?");
        $result = $stmt->execute([$client_name, $client_contact, $basic_info, $case_id]);

        if ($result) {
            $success = '個案更新成功';
            // 刷新个案信息
            $stmt = $pdo->prepare("SELECT * FROM cases WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = '個案更新失敗';
        }
    } catch(PDOException $e) {
        $error = '更新個案失敗：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯個案</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .case-form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>編輯個案</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" class="case-form">
            <div class="form-group">
                <label>客戶姓名</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($case['client_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>客戶聯絡方式</label>
                <input type="text" name="client_contact" value="<?php echo htmlspecialchars($case['client_contact']); ?>" required>
            </div>

            <div class="form-group">
                <label>基本資訊</label>
                <textarea name="basic_info" rows="5" required><?php echo htmlspecialchars($case['basic_info']); ?></textarea>
            </div>

            <div class="form-group">
                <label>建立日期</label>
                <input type="text" value="<?php echo $case['created_at']; ?>" readonly>
            </div>

            <div class="form-group">
                <label>最後更新</label>
                <input type="text" value="<?php echo $case['updated_at']; ?>" readonly>
            </div>

            <button type="submit">更新個案</button>
        </form>
    </div>
</body>
</html>