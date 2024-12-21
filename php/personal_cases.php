<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工可以訪問
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

$case_id = $_GET['case_id'] ?? null;
$error = '';
$success = '';

// 如果沒有選擇個案，載入已認領的個案
if (!$case_id) {
    try {
        $cases_stmt = $pdo->prepare("SELECT * FROM cases WHERE worker_id = ? AND status IN ('Active', 'Unassigned')");
        $cases_stmt->execute([$current_user['worker_id']]);
        $cases = $cases_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = '無法載入已認領個案：' . $e->getMessage();
    }
} else {
    // 獲取特定個案資訊
    try {
        $case_stmt = $pdo->prepare("SELECT * FROM cases WHERE case_id = ? AND worker_id = ?");
        $case_stmt->execute([$case_id, $current_user['worker_id']]);
        $case = $case_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$case) {
            header("Location: interview.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = '無法載入個案：' . $e->getMessage();
    }
}

// 處理新增訪談記錄
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_interview'])) {
    $case_id = $_POST['case_id'];
    $interview_date = $_POST['interview_date'];
    $location = $_POST['interview_location'];
    $notes = $_POST['interview_notes'];
    $additional_info = $_POST['additional_info'];

    // 照片上傳
    $photo_path = null;
    if (!empty($_FILES['interview_photos']['name'][0])) {
        $upload_dir = 'uploads/interview_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $photo_paths = [];
        foreach ($_FILES['interview_photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['interview_photos']['error'][$key] == UPLOAD_ERR_OK) {
                $original_name = $_FILES['interview_photos']['name'][$key];
                $unique_filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($original_name, PATHINFO_EXTENSION);
                $destination = $upload_dir . $unique_filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $photo_paths[] = $destination;
                }
            }
        }
        $photo_path = implode(',', $photo_paths);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO interviews (case_id, worker_id, interview_date, location, notes, additional_info, photos, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([
            $case_id,
            $current_user['worker_id'],
            $interview_date,
            $location,
            $notes,
            $additional_info,
            $photo_path
        ]);

        if ($result) {
            $success = '訪談紀錄新增成功';
        } else {
            $error = '訪談紀錄新增失敗';
        }
    } catch (PDOException $e) {
        $error = '訪談紀錄新增失敗：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訪談記錄管理</title>
    <link rel="stylesheet" href="css/personal_cases.css">
</head>
<body>
    <div class="container">
        <h1>訪談記錄管理</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($case_id): ?>
            <section>
                <h2>新增 <?php echo htmlspecialchars($case['client_name']); ?> 的訪談紀錄</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    <div>
                        <label>訪談日期</label>
                        <input type="date" name="interview_date" max="<?php echo date('Y-m-d'); ?>" required>   
                    </div>
                    <div>
                        <label>訪談地點</label>
                        <input type="text" name="interview_location" required>
                    </div>
                    <div>
                        <label>訪談內容</label>
                        <textarea name="interview_notes" rows="5" required></textarea>
                    </div>
                    <div>
                        <label>補充資訊</label>
                        <textarea name="additional_info" rows="3"></textarea>
                    </div>
                    <div>
                        <label>上傳照片</label>
                        <input type="file" name="interview_photos[]" multiple>
                    </div>
                    <button type="submit" name="submit_interview">儲存</button>
                </form>
            </section>
        <?php else: ?>
            <section>
                <h2>請選擇一個個案進行訪談</h2>
                <table>
                    <thead>
                        <tr>
                            <th>個案名稱</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td>
                                <a href="?case_id=<?php echo $case['case_id']; ?>">新增訪談</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>