<?php 
include 'header.php';
require_once 'config.php';

// 只允許特定用戶訪問
if ($current_user['user_type'] != 'U') {
    header("Location: dashboard.php");
    exit();
}

// 處理排序參數
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'full_name';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] == 'desc' ? 'desc' : 'asc';

// 處理過濾參數
$user_type_filter = isset($_GET['user_type_filter']) ? $_GET['user_type_filter'] : '';

// 處理搜尋
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%%';

try {
    // 構建過濾條件
    $type_condition = $user_type_filter ? "AND user_type = :user_type_filter" : "";

    // 獲取社工列表 (包含搜尋條件、過濾和排序)
    $stmt = $pdo->prepare("SELECT 
        worker_id, 
        username, 
        full_name, 
        contact_number,
        user_type
    FROM social_workers 
    WHERE (username LIKE :search OR full_name LIKE :search OR contact_number LIKE :search)
    $type_condition
    ORDER BY $order_by $order_dir");

    $stmt->bindParam(':search', $search, PDO::PARAM_STR);
    if ($user_type_filter) {
        $stmt->bindParam(':user_type_filter', $user_type_filter, PDO::PARAM_STR);
    }
    $stmt->execute();
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "獲取社工資料失敗: " . $e->getMessage();
    $workers = [];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <link rel="stylesheet" href="css/social_worker.css">
</head>
<body>
    <div class="container">
        <h1>社工列表</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- 搜尋表單 -->
        <form method="GET" action="" class="search-form">
            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="搜尋用戶名、姓名或電話">
            <button type="submit">搜尋</button>
        </form>

        <!-- 職位過濾 -->
        <form method="get" action="" class="filter-form">
            <label for="user_type_filter">職位篩選：</label>
            <select name="user_type_filter">
                <option value="">全部職位</option>
                <option value="M" <?php echo isset($_GET['user_type_filter']) && $_GET['user_type_filter'] == 'M' ? 'selected' : ''; ?>>管理員</option>
                <option value="U" <?php echo isset($_GET['user_type_filter']) && $_GET['user_type_filter'] == 'U' ? 'selected' : ''; ?>>社工</option>
            </select>
            <button type="submit">篩選</button>
        </form>

        <!-- 排序選單 -->
        <form method="get" action="" class="sort-form">
            <label for="order_by">排序依據：</label>
            <select name="order_by">
                <option value="full_name" <?php echo $order_by == 'full_name' ? 'selected' : ''; ?>>姓名</option>
                <option value="username" <?php echo $order_by == 'username' ? 'selected' : ''; ?>>用戶名</option>
                <option value="contact_number" <?php echo $order_by == 'contact_number' ? 'selected' : ''; ?>>聯絡電話</option>
                <option value="user_type" <?php echo $order_by == 'user_type' ? 'selected' : ''; ?>>職位</option>
            </select>
            <select name="order_dir">
                <option value="asc" <?php echo $order_dir == 'asc' ? 'selected' : ''; ?>>升冪</option>
                <option value="desc" <?php echo $order_dir == 'desc' ? 'selected' : ''; ?>>降冪</option>
            </select>
            <button type="submit">排序</button>
        </form>

        <table class="worker-list">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用戶名</th>
                    <th>姓名</th>
                    <th>聯絡電話</th>
                    <th>職位</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workers as $worker): ?>
                    <tr>
                        <td><?php echo $worker['worker_id']; ?></td>
                        <td><?php echo htmlspecialchars($worker['username']); ?></td>
                        <td><?php echo htmlspecialchars($worker['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($worker['contact_number']); ?></td>
                        <td>
                            <?php 
                                echo $worker['user_type'] == 'M' ? '管理員' : '社工'; 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>