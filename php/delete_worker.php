<?php
include 'header.php';
require_once 'config.php';

// 确保只有管理员可以访问
if ($current_user['user_type'] != 'M') {
    header("Location: dashboard.php");
    exit();
}

// 检查是否传入了工作者ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = '未指定要刪除的社工ID';
    header("Location: social_worker_management.php");
    exit();
}

$worker_id = $_GET['id'];

// 新增：防止删除自己的账号
if ($worker_id == $_SESSION['worker_id']) {
    $_SESSION['error_message'] = '不能刪除自己的帳號';
    header("Location: social_worker_management.php");
    exit();
}

try {
    // 开始事务
    $pdo->beginTransaction();

    // 检查是否是最后一个管理员
    $admin_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM social_workers WHERE user_type = 'M'");
    $admin_count_stmt->execute();
    $admin_count = $admin_count_stmt->fetchColumn();

    // 检查要删除的用户是否是最后一个管理员
    $current_user_type_stmt = $pdo->prepare("SELECT user_type FROM social_workers WHERE worker_id = ?");
    $current_user_type_stmt->execute([$worker_id]);
    $current_user_type = $current_user_type_stmt->fetchColumn();

    if ($current_user_type == 'M' && $admin_count <= 1) {
        $_SESSION['error_message'] = '無法刪除最後一個管理員帳號';
        header("Location: social_worker_management.php");
        exit();
    }

    // 检查该社工是否有关联的个案
    $case_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE worker_id = ?");
    $case_count_stmt->execute([$worker_id]);
    $case_count = $case_count_stmt->fetchColumn();

    if ($case_count > 0) {
        $_SESSION['error_message'] = '無法刪除有關聯個案的社工帳號';
        header("Location: social_worker_management.php");
        exit();
    }

    // 删除社工
    $delete_stmt = $pdo->prepare("DELETE FROM social_workers WHERE worker_id = ?");
    $delete_result = $delete_stmt->execute([$worker_id]);

    if ($delete_result) {
        // 提交事务
        $pdo->commit();
        $_SESSION['success_message'] = '社工帳號刪除成功';
    } else {
        // 回滚事务
        $pdo->rollBack();
        $_SESSION['error_message'] = '刪除社工帳號失敗';
    }

} catch(PDOException $e) {
    // 如果出现错误，回滚事务
    $pdo->rollBack();
    $_SESSION['error_message'] = '刪除社工帳號失敗：' . $e->getMessage();
}

// 重定向回社工管理页面
header("Location: social_worker_management.php");
exit();
?>