<?php
include 'header.php';
require_once 'config.php';

// 确保只有管理员可以访问
if ($current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

// 检查是否传入案例ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = '未指定要刪除的個案ID';
    header("Location: case_management.php");
    exit();
}

$case_id = $_GET['id'];

try {
    // 开始事务
    $pdo->beginTransaction();

    // 删除个案
    $delete_stmt = $pdo->prepare("DELETE FROM cases WHERE case_id = ?");
    $delete_result = $delete_stmt->execute([$case_id]);

    if ($delete_result) {
        // 提交事务
        $pdo->commit();
        $_SESSION['success_message'] = '個案刪除成功';
    } else {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '刪除個案失敗';
    }

} catch(PDOException $e) {
    // 如果出现错误，回滚事务
    $pdo->rollBack();
    $_SESSION['error_message'] = '刪除個案失敗：' . $e->getMessage();
}

// 重定向回个案管理页面
header("Location: case_management.php");
exit();
?>