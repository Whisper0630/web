<?php
include 'header.php';
require_once 'config.php';

// 確保只有社工可以訪問
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

$interview_id = $_GET['interview_id'] ?? null;
$error = '';
$success = '';

if (!$interview_id) {
    header("Location: view_interview.php");
    exit();
}

// 檢查訪談是否屬於當前社工
try {
    $stmt = $pdo->prepare("
        SELECT i.*, c.client_name, c.basic_info 
        FROM interviews i
        JOIN cases c ON i.case_id = c.case_id
        WHERE i.interview_id = ? AND i.worker_id = ?
    ");
    $stmt->execute([$interview_id, $current_user['worker_id']]);
    $interview = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$interview) {
        header("Location: view_interview.php");
        exit();
    }
} catch (PDOException $e) {
    $error = '無法載入訪談紀錄：' . $e->getMessage();
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        // 如果有新上傳的照片，與原有照片合併
        $existing_photos = explode(',', $interview['photos'] ?? '');
        $photo_paths = array_merge($existing_photos, $photo_paths);
        $photo_path = implode(',', array_filter($photo_paths));
    } else {
        // 如果沒有新照片，保留原來的照片
        $photo_path = $interview['photos'];
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE interviews 
            SET interview_date = ?, 
                location = ?, 
                notes = ?, 
                additional_info = ?, 
                photos = ?, 
                updated_at = NOW()
            WHERE interview_id = ?
        ");
        $result = $stmt->execute([
            $interview_date,
            $location,
            $notes,
            $additional_info,
            $photo_path,
            $interview_id
        ]);

        if ($result) {
            $success = '訪談紀錄更新成功';
            // 重新載入訪談資料
            $stmt = $pdo->prepare("
                SELECT i.*, c.client_name, c.basic_info 
                FROM interviews i
                JOIN cases c ON i.case_id = c.case_id
                WHERE i.interview_id = ?
            ");
            $stmt->execute([$interview_id]);
            $interview = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = '訪談紀錄更新失敗';
        }
    } catch (PDOException $e) {
        $error = '訪談紀錄更新失敗：' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯訪談紀錄</title>
    <link rel="stylesheet" href="css/interview.css">
</head>
<body>
    <div class="container">
        <h1>編輯訪談紀錄</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <section>
            <h2>編輯 <?php echo htmlspecialchars($interview['client_name']); ?> 的訪談紀錄</h2>

            <div class="case-info">
                <strong>基本資訊：</strong><?php echo htmlspecialchars($interview['basic_info']); ?>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div>
                    <label>訪談日期</label>
                    <input type="date" name="interview_date" 
                           value="<?php echo $interview['interview_date']; ?>" 
                           required>
                </div>
                <div>
                    <label>訪談地點</label>
                    <input type="text" name="interview_location" 
                           value="<?php echo htmlspecialchars($interview['location']); ?>" 
                           required>
                </div>
                <div>
                    <label>訪談內容</label>
                    <textarea name="interview_notes" rows="5" required><?php 
                        echo htmlspecialchars($interview['notes']); 
                    ?></textarea>
                </div>
                <div>
                    <label>補充資訊</label>
                    <textarea name="additional_info" rows="3"><?php 
                        echo htmlspecialchars($interview['additional_info'] ?? ''); 
                    ?></textarea>
                </div>
                <div>
                    <label>上傳照片</label>
                    <input type="file" name="interview_photos[]" multiple>
                    <?php if ($interview['photos']): ?>
                        <p>現有照片：<?php 
                            $photos = explode(',', $interview['photos']);
                            foreach ($photos as $photo) {
                                echo '<a href="' . htmlspecialchars($photo) . '" target="_blank">查看</a> ';
                            }
                        ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit">更新訪談紀錄</button>
                <a href="view_interview.php?case_id=<?php echo $interview['case_id']; ?>" class="button-link">取消</a>
            </form>
        </section>
    </div>
</body>
</html>