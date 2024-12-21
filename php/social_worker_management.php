<?php 
include 'header.php';
require_once 'config.php';

// 確保只有管理員可以訪問
if ($current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

// 處理排序參數
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'created_at';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] == 'desc' ? 'desc' : 'asc';

// 處理過濾參數
$user_type_filter = isset($_GET['user_type_filter']) ? $_GET['user_type_filter'] : '';

// 處理搜尋
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%%';

// 分頁設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

try {
    // 構建過濾條件
    $type_condition = $user_type_filter ? "AND user_type = :user_type_filter" : "";

    // 獲取社工總數
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM social_workers 
        WHERE (username LIKE :search OR full_name LIKE :search OR contact_number LIKE :search)
        $type_condition");
    $total_stmt->bindParam(':search', $search, PDO::PARAM_STR);
    if ($user_type_filter) {
        $total_stmt->bindParam(':user_type_filter', $user_type_filter, PDO::PARAM_STR);
    }
    $total_stmt->execute();
    $total_workers = $total_stmt->fetchColumn();
    $total_pages = ceil($total_workers / $records_per_page);

    // 獲取社工列表
    $stmt = $pdo->prepare("SELECT 
        worker_id, 
        username, 
        full_name, 
        contact_number, 
        user_type, 
        created_at, 
        last_login 
    FROM social_workers 
    WHERE (username LIKE :search OR full_name LIKE :search OR contact_number LIKE :search)
    $type_condition
    ORDER BY $order_by $order_dir
    LIMIT :offset, :records_per_page");

    $stmt->bindParam(':search', $search, PDO::PARAM_STR);
    if ($user_type_filter) {
        $stmt->bindParam(':user_type_filter', $user_type_filter, PDO::PARAM_STR);
    }
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
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
    <link rel="stylesheet" href="css/social_worker_management.css">
</head>
<body>
    <div class="container">
        <h1>社工資料管理</h1>
        
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
                <option value="created_at" <?php echo $order_by == 'created_at' ? 'selected' : ''; ?>>建立日期</option>
                <option value="username" <?php echo $order_by == 'username' ? 'selected' : ''; ?>>用戶名</option>
                <option value="full_name" <?php echo $order_by == 'full_name' ? 'selected' : ''; ?>>姓名</option>
                <option value="last_login" <?php echo $order_by == 'last_login' ? 'selected' : ''; ?>>最後登入</option>
                <option value="user_type" <?php echo $order_by == 'user_type' ? 'selected' : ''; ?>>職位</option>
            </select>
            <select name="order_dir">
                <option value="asc" <?php echo $order_dir == 'asc' ? 'selected' : ''; ?>>升冪</option>
                <option value="desc" <?php echo $order_dir == 'desc' ? 'selected' : ''; ?>>降冪</option>
            </select>
            <button type="submit">排序</button>
        </form>

        <!-- 表格顯示用戶 -->
        <table class="worker-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用戶名</th>
                    <th>姓名</th>
                    <th>聯絡電話</th>
                    <th>職位</th>
                    <th>建立日期</th>
                    <th>最後登入</th>
                    <th>操作</th>
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
                        <td><?php echo $worker['created_at']; ?></td>
                        <td><?php echo $worker['last_login'] ?? '從未登入'; ?></td>
                        <td>
                            <a href="view_worker.php?id=<?php echo $worker['worker_id']; ?>">檢視</a>
                            <a href="delete_worker.php?id=<?php echo $worker['worker_id']; ?>" onclick="return confirm('確定要解雇這個社工嗎？');">解雇</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 分頁 -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>&user_type_filter=<?php echo $user_type_filter; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>