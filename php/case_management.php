<?php
include 'header.php';
require_once 'config.php';

// 確保只有管理員可以訪問
if ($current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// 處理搜尋和排序
$search = '';
$order_by = 'c.created_at';
$order_dir = 'DESC';
$status_filter = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if (isset($_GET['order_by'])) {
        $order_by = $_GET['order_by'];
        $order_dir = $_GET['order_dir'] == 'ASC' ? 'ASC' : 'DESC';
    }

    if (isset($_GET['status_filter'])) {
        $status_filter = $_GET['status_filter'];
    }
}

// 處理分頁
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

try {
    // 搜尋過濾的條件
    $search_condition = '';
    if ($search != '') {
        $search_condition = "AND (c.client_name LIKE :search 
                                OR c.client_contact LIKE :search
                                OR c.basic_info LIKE :search
                                OR sw.full_name LIKE :search)";
    }

    // 狀態過濾條件
    $status_condition = '';
    if ($status_filter != '') {
        $status_condition = "AND c.status = :status_filter";
    }

    // 獲取總個案數
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM cases c
                                 LEFT JOIN social_workers sw ON c.worker_id = sw.worker_id
                                 WHERE 1=1 $search_condition $status_condition");
    if ($search != '') {
        $total_stmt->bindValue(':search', '%' . $search . '%');
    }
    if ($status_filter != '') {
        $total_stmt->bindValue(':status_filter', $status_filter);
    }
    $total_stmt->execute();
    $total_cases = $total_stmt->fetchColumn();
    $total_pages = ceil($total_cases / $records_per_page);

    // 獲取篩選後的個案
    $stmt = $pdo->prepare("SELECT 
            c.case_id, 
            c.client_name, 
            c.client_contact, 
            c.basic_info, 
            c.created_at, 
            c.updated_at, 
            c.status,
            sw.full_name as worker_name,
            c.worker_id
        FROM cases c
        LEFT JOIN social_workers sw ON c.worker_id = sw.worker_id
        WHERE 1=1 $search_condition $status_condition
        ORDER BY $order_by $order_dir
        LIMIT :offset, :records_per_page");

    if ($search != '') {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    if ($status_filter != '') {
        $stmt->bindValue(':status_filter', $status_filter);
    }

    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取所有社工列表
    $workers_stmt = $pdo->query("SELECT worker_id, full_name FROM social_workers WHERE user_type = 'U'");
    $workers = $workers_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "獲取個案資料失敗: " . $e->getMessage();
    $cases = [];
    $workers = [];
}

// 處理分配個案的邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_case'])) {
    $case_id = $_POST['case_id'];
    $worker_id = $_POST['worker_id'];

    try {
        // 更新個案的社工
        $update_stmt = $pdo->prepare("UPDATE cases SET worker_id = :worker_id, status = 'Active' WHERE case_id = :case_id AND status = 'Unassigned'");
        $update_stmt->bindParam(':worker_id', $worker_id, PDO::PARAM_INT);
        $update_stmt->bindParam(':case_id', $case_id, PDO::PARAM_INT);
        $update_stmt->execute();

        $success = "個案已成功分配給社工。";
        header("Location: case_management.php");
        exit();

    } catch(PDOException $e) {
        $error = "分配個案失敗: " . $e->getMessage();
    }
}

// 處理刪除個案的邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_case'])) {
    $case_id = $_POST['case_id'];

    try {
        // 刪除個案
        $delete_stmt = $pdo->prepare("DELETE FROM cases WHERE case_id = :case_id AND status = 'Unassigned'");
        $delete_stmt->bindParam(':case_id', $case_id, PDO::PARAM_INT);
        $delete_stmt->execute();

        $success = "個案已成功刪除。";
        header("Location: case_management.php");
        exit();

    } catch(PDOException $e) {
        $error = "刪除個案失敗: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>個案資料管理</title>
    <link rel="stylesheet" href="css/case_management.css">
    <script type="text/javascript">
        function confirmDelete(caseId) {
            if (confirm('確定要刪除這個個案嗎？')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_case';
                hiddenInput.value = true;
                form.appendChild(hiddenInput);

                var caseInput = document.createElement('input');
                caseInput.type = 'hidden';
                caseInput.name = 'case_id';
                caseInput.value = caseId;
                form.appendChild(caseInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>個案資料管理</h1>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- 搜尋表單 -->
        <form method="get" action="" class="search-form">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜尋客戶姓名、聯絡方式、基本資訊...">
            <button type="submit">搜尋</button>
        </form>

        <!-- 狀態過濾選單 -->
        <form method="get" action="" class="filter-form">
            <label for="status_filter">搜尋狀態：</label>
            <select name="status_filter">
                <option value="">全部狀態</option>
                <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>進行中</option>
                <option value="Unassigned" <?php echo $status_filter == 'Unassigned' ? 'selected' : ''; ?>>未分配</option>
                <option value="Closed" <?php echo $status_filter == 'Closed' ? 'selected' : ''; ?>>已結束</option>
            </select>
            <button type="submit">搜尋</button>
        </form>

        <!-- 排序選單 -->
        <form method="get" action="" class="sort-form">
            <label for="order_by">排序依據：</label>
            <select name="order_by">
                <option value="c.created_at" <?php echo $order_by == 'c.created_at' ? 'selected' : ''; ?>>建立日期</option>
                <option value="c.updated_at" <?php echo $order_by == 'c.updated_at' ? 'selected' : ''; ?>>最後更新</option>
            </select>
            <select name="order_dir">
                <option value="ASC" <?php echo $order_dir == 'ASC' ? 'selected' : ''; ?>>升冪</option>
                <option value="DESC" <?php echo $order_dir == 'DESC' ? 'selected' : ''; ?>>降冪</option>
            </select>
            <button type="submit">排序</button>
        </form>

        <!-- 表格顯示個案 -->
        <table class="case-table">
            <thead>
                <tr>
                    <th>個案編號</th>
                    <th>客戶姓名</th>
                    <th>客戶聯絡方式</th>
                    <th>基本資訊</th>
                    <th>負責社工</th>
                    <th>狀態</th>
                    <th>建立日期</th>
                    <th>最後更新</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cases as $case): ?>
                    <tr>
                        <td><?php echo $case['case_id']; ?></td>
                        <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($case['client_contact']); ?></td>
                        <td class="basic-info" title="<?php echo htmlspecialchars($case['basic_info']); ?>">
                            <?php echo htmlspecialchars($case['basic_info']); ?>
                        </td>
                        <td>
    <?php if ($case['status'] == 'Unassigned'): ?>
        <form method="post" action="" class="assign-form">
            <input type="hidden" name="case_id" value="<?php echo $case['case_id']; ?>">
            <select name="worker_id" required>
                <option value="">請選擇社工</option>
                <?php foreach ($workers as $worker): ?>
                    <option value="<?php echo $worker['worker_id']; ?>"><?php echo htmlspecialchars($worker['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_case" class="btn" 
                    <?php echo empty($workers) ? 'disabled' : ''; ?>>
                <?php echo empty($workers) ? '無可分配社工' : '分配'; ?>
            </button>
        </form>
    <?php else: ?>
        <?php echo htmlspecialchars($case['worker_name'] ?: '無'); ?>
    <?php endif; ?>
</td>
                        <td>
                            <?php
                            switch($case['status']) {
                                case 'Active':
                                    echo '進行中';
                                    break;
                                case 'Unassigned':
                                    echo '未分配';
                                    break;
                                case 'Closed':
                                    echo '已結束';
                                    break;
                                default:
                                    echo '未知狀態';
                            }
                            ?>
                        </td>
                        <td><?php echo $case['created_at']; ?></td>
                        <td><?php echo $case['updated_at']; ?></td>
                        <td>
                            <?php if ($case['status'] == 'Unassigned'): ?>
                                <button class="btn" onclick="window.location.href='edit_case.php?id=<?php echo $case['case_id']; ?>'">編輯個案資料</button>
                                <button class="btn" onclick="confirmDelete(<?php echo $case['case_id']; ?>)">刪除</button>
                            <?php elseif ($case['status'] == 'Active'): ?>
                                <button class="btn" onclick="window.location.href='edit_case.php?id=<?php echo $case['case_id']; ?>'">編輯個案資料</button>
                            <?php elseif ($case['status'] == 'Closed'): ?>
                                <button class="btn" onclick="window.location.href='admin_view_closed_case_interviews.php?case_id=<?php echo $case['case_id']; ?>'">查看訪談紀錄</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 分頁 -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>&status_filter=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
