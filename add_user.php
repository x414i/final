<?php
session_start();
require_once 'db.php';

// التحقق من أن المستخدم مسجل دخوله كمسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

// إضافة مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $active = isset($_POST['active']) ? 1 : 0;

    // التحقق من عدم تكرار اسم المستخدم أو البريد الإلكتروني
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $message = '<div class="message error">اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل.</div>';
    } else {
        // التحقق من أن كلمة المرور ليست فارغة
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, role, active) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param('ssssi', $username, $email, $hashedPassword, $role, $active);

            if ($insertStmt->execute()) {
                $message = '<div class="message success">تمت إضافة المستخدم بنجاح.</div>';
            } else {
                $message = '<div class="message error">حدث خطأ أثناء إضافة المستخدم.</div>';
            }
        } else {
            $message = '<div class="message error">حقل كلمة المرور مطلوب.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إضافة مستخدم جديد</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
        --primary-color: #2980b9;
        --background-color: #ecf0f1;
        --widget-bg: #ffffff;
        --header-bg: #34495e;
        --success-color: #27ae60;
        --error-color: #c0392b;
    }
    body {
        font-family: 'Cairo', sans-serif;
        background-color: var(--background-color);
        margin: 0;
        display: flex;
    }
    .sidebar {
        width: 250px;
        background-color: var(--header-bg);
        color: #fff;
        height: 100vh;
        padding: 20px;
        box-sizing: border-box;
        position: fixed;
    }
    .sidebar h2 { text-align: center; margin-bottom: 30px; }
    .sidebar ul { list-style: none; padding: 0; }
    .sidebar ul li a {
        color: #fff;
        text-decoration: none;
        display: block;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: background-color 0.3s;
    }
    .sidebar ul li a:hover, .sidebar ul li a.active { background-color: var(--primary-color); }
    .main-content {
        margin-right: 250px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .content { padding: 30px; flex-grow: 1; }
    .widget {
        background-color: var(--widget-bg);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        max-width: 600px;
        margin: auto;
    }
    .widget h3 { margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 25px; }
    .form-group { margin-bottom: 20px; }
    label { font-weight: 600; display: block; margin-bottom: 8px; }
    input[type="text"], input[type="email"], input[type="password"], select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
    }
    .checkbox-group { display: flex; align-items: center; }
    .checkbox-group input { width: auto; margin-left: 10px; }
    .form-actions { margin-top: 30px; display: flex; gap: 15px; }
    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-family: 'Cairo', sans-serif;
    }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    .btn-secondary { background-color: #bdc3c7; color: #333; }
    .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    .success { background-color: #d4edda; color: var(--success-color); }
    .error { background-color: #f8d7da; color: var(--error-color); }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fa-solid fa-shield-halved"></i> لوحة التحكم</h2>
    <ul>
      <li><a href="admin_dashboard.php"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
      <li><a href="activate_users.php"><i class="fa-solid fa-user-check"></i> تفعيل الحسابات</a></li>
      <li><a href="view_users.php"><i class="fa-solid fa-users"></i> عرض المستخدمين</a></li>
      <li><a href="add_user.php" class="active"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم</a></li>
    </ul>
  </div>
  <div class="main-content">
    <div class="content">
      <div class="widget">
        <h3>إضافة مستخدم جديد</h3>
        <?php echo $message; ?>
        <form method="POST">
          <div class="form-group">
            <label for="username">اسم المستخدم</label>
            <input type="text" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required>
          </div>
          <div class="form-group">
            <label for="role">الدور</label>
            <select id="role" name="role" required>
              <option value="student">طالب</option>
              <option value="supervisor">مشرف</option>
              <option value="admin">مدير النظام</option>
            </select>
          </div>
          <div class="form-group checkbox-group">
            <input type="checkbox" id="active" name="active" value="1" checked>
            <label for="active">تفعيل الحساب مباشرة</label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">إضافة المستخدم</button>
            <a href="view_users.php" class="btn btn-secondary">العودة إلى القائمة</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
