<?php
session_start();
require_once 'db.php';

// التحقق من أن المستخدم مسجل دخوله كمسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// تفعيل المستخدم عند الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_user_id'])) {
    $userId = intval($_POST['activate_user_id']);
    $stmt = $conn->prepare("UPDATE users SET active = 1 WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

// جلب المستخدمين غير المفعلين
$result = $conn->query("SELECT id, username, email, role, created_at FROM users WHERE active = 0 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تفعيل الحسابات</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
        --primary-color: #2980b9;
        --secondary-color: #27ae60;
        --background-color: #ecf0f1;
        --widget-bg: #ffffff;
        --text-color: #34495e;
        --header-bg: #34495e;
        --danger-color: #c0392b;
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
        margin-right: 250px; /* Same as sidebar width */
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
    header .user-info a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    .content { padding: 30px; flex-grow: 1; }
    .widget {
        background-color: var(--widget-bg);
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .widget h3 { margin-top: 0; color: var(--primary-color); }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 15px;
        text-align: right;
        border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #f2f2f2;
        font-weight: 600;
    }
    .activate-btn {
        background-color: var(--secondary-color);
        color: #fff;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-family: 'Cairo', sans-serif;
        transition: background-color 0.3s;
    }
    .activate-btn:hover { background-color: #229954; }
    .no-users {
        text-align: center;
        padding: 20px;
        color: #7f8c8d;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fa-solid fa-shield-halved"></i> لوحة التحكم</h2>
    <ul>
      <li><a href="admin_dashboard.php"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
      <li><a href="activate_users.php" class="active"><i class="fa-solid fa-user-check"></i> تفعيل الحسابات</a></li>
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
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
      </div>
    </header>
    <div class="content">
      <div class="widget">
        <h3>الحسابات بانتظار التفعيل</h3>
        <?php if ($result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>اسم المستخدم</th>
              <th>البريد الإلكتروني</th>
              <th>الدور</th>
              <th>تاريخ التسجيل</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php while($user = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td><?php echo htmlspecialchars($user['role']); ?></td>
              <td><?php echo $user['created_at']; ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="activate_user_id" value="<?php echo $user['id']; ?>">
                  <button type="submit" class="activate-btn">تفعيل</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p class="no-users">لا توجد حسابات بانتظار التفعيل حالياً.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
