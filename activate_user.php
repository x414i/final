<?php
// صفحة تفعيل حساب المستخدم من قبل المشرف
session_start();
require_once 'db.php';

// تحقق من صلاحية المشرف
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE users SET active = 1 WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        header('Location: admin_dashboard.php?msg=تم التفعيل بنجاح');
        exit;
    } else {
        echo "حدث خطأ أثناء التفعيل.";
    }
} else {
    echo "معرّف المستخدم غير موجود.";
}
?>
