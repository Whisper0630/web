
<?php
require_once 'config.php';
require_once 'header 2.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $contact_number = $_POST['contact_number'];

    // 簡單的表單驗證
    if (empty($username) || empty($password) || empty($full_name) || empty($contact_number)) {
        $error = "所有欄位都是必填的";
    } else {
        try {
            // 檢查用戶名是否已存在
            $stmt = $pdo->prepare("SELECT * FROM social_workers WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = "此用戶名已被使用";
            } else {
                // 密碼加密
                $hashed_password = hash('sha256', $password);
                
                // 插入新用戶
                $stmt = $pdo->prepare("INSERT INTO social_workers (username, password, full_name, contact_number, user_type) VALUES (?, ?, ?, ?, 'U')");
                
                if ($stmt->execute([$username, $hashed_password, $full_name, $contact_number])) {
                    // 註冊成功，重定向到登入頁面
                    header("Location: login.php?registered=true");
                    exit();
                } else {
                    $error = "註冊失敗，請稍後再試";
                }
            }
        } catch(PDOException $e) {
            $error = "發生錯誤: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>社工系統 - 註冊</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <form method="post" action="">
            <h2>社工系統 - 註冊</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>用戶名</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>姓名</label>
                <input type="text" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label>聯絡電話</label>
                <input type="tel" name="contact_number" required>
            </div>
            
            <button type="submit">註冊</button>
            <p>已有帳號？ <a href="login.php">登入</a></p>
        </form>
    </div>
</body>
</html>