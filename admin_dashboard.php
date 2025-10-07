<?php
session_start();
// التحقق من أن المستخدم مسجل دخوله كمسؤول أو مشرف
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supervisor')) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم المسؤول</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
        --primary-color: #2980b9;
        --secondary-color: #8e44ad;
        --background-color: #ecf0f1;
        --widget-bg: #ffffff;
        --text-color: #34495e;
        --header-bg: #34495e;
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
    }
    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
    }
    .sidebar ul {
        list-style: none;
        padding: 0;
    }
    .sidebar ul li a {
        color: #fff;
        text-decoration: none;
        display: block;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: background-color 0.3s;
    }
    .sidebar ul li a:hover, .sidebar ul li a.active {
        background-color: var(--primary-color);
    }
    .main-content {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    header {
        background-color: var(--widget-bg);
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    header .user-info a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
    }
    .content {
        padding: 30px;
        flex-grow: 1;
    }
    .widget {
        background-color: var(--widget-bg);
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .widget h3 {
        margin-top: 0;
        color: var(--primary-color);
    }
    .quick-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
    }
    .action-card {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: #fff;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        flex-basis: 200px;
        text-decoration: none;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .action-card i {
        font-size: 2.5em;
        margin-bottom: 15px;
    }
    .action-card span {
        font-size: 1.1em;
        font-weight: 600;
        display: block;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fa-solid fa-shield-halved"></i> لوحة التحكم</h2>
    <ul>
      <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
      <li><a href="activate_users.php"><i class="fa-solid fa-user-check"></i> تفعيل الحسابات</a></li>
      <li><a href="view_users.php"><i class="fa-solid fa-users"></i> عرض المستخدمين</a></li>
      <li><a href="add_user.php"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم</a></li>
      <li><a href="review_projects.php"><i class="fa-solid fa-file-circle-check"></i> مراجعة المشاريع</a></li>
    </ul>
  </div>
  <div class="main-content">
    <header>
      <div class="welcome-message">
        <h3>أهلاً بك، <?php echo htmlspecialchars($_SESSION['role']); ?>!</h3>
      </div>
      <div class="user-info">
        <a href="http://localhost/final/"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
      </div>
    </header>
    <div class="content">
      <div class="widget">
        <h3>الإجراءات السريعة</h3>
        <div class="quick-actions">
          <a href="activate_users.php" class="action-card">
            <i class="fa-solid fa-user-check"></i>
            <span>تفعيل الحسابات</span>
          </a>
          <a href="view_users.php" class="action-card">
            <i class="fa-solid fa-users"></i>
            <span>عرض المستخدمين</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
