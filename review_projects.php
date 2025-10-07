<?php
session_start();
require_once 'db.php';

// التحقق من أن المستخدم مسجل دخوله كمسؤول أو مشرف
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header('Location: login.php');
    exit;
}

// تحديث حالة المشروع عند الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id']) && isset($_POST['status'])) {
    $projectId = intval($_POST['project_id']);
    $status = $_POST['status'];

    if (in_array($status, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $projectId);
        $stmt->execute();
    }
}

// جلب جميع المشاريع مع أسماء الطلاب والأقسام
$query = "
    SELECT p.id, p.title, p.summary, p.year, p.status, p.pdf_file, p.code_file,
           u.username AS student_name,
           s.name AS section_name
    FROM projects p
    JOIN users u ON p.student_id = u.id
    JOIN sections s ON p.section_id = s.id
    ORDER BY p.uploaded_at DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>مراجعة المشاريع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
        --primary-color: #2980b9;
        --background-color: #ecf0f1;
        --widget-bg: #ffffff;
        --header-bg: #34495e;
        --success-color: #27ae60;
        --danger-color: #c0392b;
        --warning-color: #f39c12;
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
    .main-content { margin-right: 250px; flex-grow: 1; }
    .content { padding: 30px; }
    .widget {
        background-color: var(--widget-bg);
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 15px; text-align: right; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .status-pending { color: var(--warning-color); font-weight: bold; }
    .status-approved { color: var(--success-color); font-weight: bold; }
    .status-rejected { color: var(--danger-color); font-weight: bold; }
    .action-btn {
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-family: 'Cairo', sans-serif;
        margin-left: 5px;
    }
    .approve-btn { background-color: var(--success-color); }
    .reject-btn { background-color: var(--danger-color); }
    .file-links a { margin-left: 10px; color: var(--primary-color); }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2><i class="fa-solid fa-shield-halved"></i> لوحة التحكم</h2>
    <ul>
      <li><a href="admin_dashboard.php"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
      <li><a href="activate_users.php"><i class="fa-solid fa-user-check"></i> تفعيل الحسابات</a></li>
      <li><a href="view_users.php"><i class="fa-solid fa-users"></i> عرض المستخدمين</a></li>
      <li><a href="add_user.php"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم</a></li>
      <li><a href="review_projects.php" class="active"><i class="fa-solid fa-file-circle-check"></i> مراجعة المشاريع</a></li>
    </ul>
  </div>
  <div class="main-content">
    <div class="content">
      <div class="widget">
        <h3>مراجعة المشاريع المقدمة</h3>
        <table>
          <thead>
            <tr>
              <th>عنوان المشروع</th>
              <th>الطالب</th>
              <th>القسم</th>
              <th>السنة</th>
              <th>الملفات</th>
              <th>الحالة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php while($project = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($project['title']); ?></td>
              <td><?php echo htmlspecialchars($project['student_name']); ?></td>
              <td><?php echo htmlspecialchars($project['section_name']); ?></td>
              <td><?php echo htmlspecialchars($project['year']); ?></td>
              <td class="file-links">
                <?php if($project['pdf_file']): ?>
                  <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank"><i class="fa-solid fa-file-pdf"></i></a>
                <?php endif; ?>
                <?php if($project['code_file']): ?>
                  <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank"><i class="fa-solid fa-file-zipper"></i></a>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-<?php echo $project['status']; ?>">
                  <?php 
                    if($project['status'] == 'pending') echo 'قيد المراجعة';
                    elseif($project['status'] == 'approved') echo 'مقبول';
                    else echo 'مرفوض';
                  ?>
                </span>
              </td>
              <td>
                <?php if($project['status'] == 'pending'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                  <button type="submit" name="status" value="approved" class="action-btn approve-btn">قبول</button>
                  <button type="submit" name="status" value="rejected" class="action-btn reject-btn">رفض</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
