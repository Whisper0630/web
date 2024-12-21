<?php
include 'header.php';
require_once 'config.php';

$error = '';
$success = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $contact_number = $_POST['contact_number'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // 驗證當前密碼
        $stmt = $pdo->prepare("SELECT password FROM social_workers WHERE worker_id = ?");
        $stmt->execute([$_SESSION['worker_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $hashed_current_password = hash('sha256', $current_password);

        if ($hashed_current_password !== $user['password']) {
            $error = '當前密碼不正確';
        } else {
        // 準備更新語句
        $update_fields = ['full_name = ?', 'contact_number = ?'];
        $update_params = [$full_name, $contact_number];

        // 如果提供了新密碼
        if (!empty($new_password)) {
        // 密碼驗證
        if ($new_password !== $confirm_password) {
            $error = '新密碼和確認密碼不一致';
            } elseif (strlen($new_password) < 1) {
            $error = '新密碼長度至少需要1個字元';
            } else {
                $hashed_new_password = hash('sha256', $new_password);
                $update_fields[] = 'password = ?';
                $update_params[] = $hashed_new_password;
            }
        }

        // 執行更新
        if (empty($error)) {
                $update_query = "UPDATE social_workers SET " . implode(', ', $update_fields) . " WHERE worker_id = ?";
                $update_params[] = $_SESSION['worker_id'];

                $stmt = $pdo->prepare($update_query);
                $stmt->execute($update_params);

                $success = '個人資料更新成功';
            }
        }
    } catch(PDOException $e) {
        $error = '更新失敗：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯個人資料</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .error-message {
            color: red;
            background-color: #ffeeee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            background-color: #eeffee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>編輯個人資料</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label>姓名</label>
                <input type="text" name="full_name" 
                    value="<?php echo htmlspecialchars($current_user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>聯絡電話</label>
                <input type="tel" name="contact_number" 
                    value="<?php echo htmlspecialchars($current_user['contact_number']); ?>" required>
            </div>

            <div class="form-group">
                <label>當前密碼（修改資料時需要驗證）</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>新密碼（選填，不想修改請留空）</label>
                <input type="password" name="new_password">
            </div>

            <div class="form-group">
                <label>確認新密碼</label>
                <input type="password" name="confirm_password">
            </div>

            <button type="submit">更新資料</button>
        </form>
    </div>
</body>
</html>