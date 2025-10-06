<?php
// لوحة تحكم المشرف
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supervisor')) {
    header('Location: login.php');
    exit;
}
// جلب المشاريع قيد المراجعة
$projects = $conn->query("SELECT p.*, u.username AS student, s.name AS section FROM projects p JOIN users u ON p.student_id = u.id JOIN sections s ON p.section_id = s.id ORDER BY p.created_at DESC");
// جلب المستخدمين
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
// جلب الأقسام
$sections = $conn->query("SELECT * FROM sections ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم المشرف</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 900px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    h2 { color: #34495e; margin-top: 30px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
    th { background: #eaf6ff; color: #2980b9; }
    .actions a { background:#2980b9; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; font-weight:bold; margin:0 2px; font-size:0.95em; }
    .actions a:hover { background:#1abc9c; }
    .status.accepted { color: #27ae60; font-weight:bold; }
    .status.pending { color: #f39c12; font-weight:bold; }
    .status.rejected { color: #e74c3c; font-weight:bold; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>لوحة تحكم المشرف</h1>
    </header>
    <h2>إدارة المشاريع</h2>
    <table>
      <tr>
        <th>العنوان</th>
        <th>الطالب</th>
        <th>القسم</th>
        <th>السنة</th>
        <th>الحالة</th>
        <th>إجراءات</th>
      </tr>
      <?php while($row = $projects->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['student']); ?></td>
        <td><?php echo htmlspecialchars($row['section']); ?></td>
        <td><?php echo htmlspecialchars($row['year']); ?></td>
        <td class="status <?php echo $row['status']; ?>">
          <?php
            if ($row['status'] == 'pending') echo 'قيد المراجعة';
            elseif ($row['status'] == 'accepted') echo 'مقبول';
            else echo 'مرفوض';
          ?>
        </td>
        <td class="actions">
          <a href="review_project.php?id=<?php echo $row['id']; ?>">مراجعة</a>
          <a href="delete_project.php?id=<?php echo $row['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف المشروع؟');">حذف</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    <h2>إدارة المستخدمين</h2>
    <table>
      <tr>
        <th>اسم المستخدم</th>
        <th>البريد الإلكتروني</th>
        <th>الدور</th>
        <th>الحالة</th>
        <th>إجراءات</th>
      </tr>
      <?php while($user = $users->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td><?php echo $user['active'] ? 'مفعل' : 'غير مفعل'; ?></td>
        <td class="actions">
          <a href="edit_user.php?id=<?php echo $user['id']; ?>">تعديل</a>
          <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف المستخدم؟');">حذف</a>
          <?php if (!$user['active']): ?>
            <a href="activate_user.php?id=<?php echo $user['id']; ?>" style="background:#27ae60;">تفعيل</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    <h2>إدارة الأقسام</h2>
    <table>
      <tr>
        <th>اسم القسم</th>
        <th>إجراءات</th>
      </tr>
      <?php while($section = $sections->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($section['name']); ?></td>
        <td class="actions">
          <a href="edit_section.php?id=<?php echo $section['id']; ?>">تعديل</a>
          <a href="delete_section.php?id=<?php echo $section['id']; ?>" onclick="return confirm('هل أنت متأكد من حذف القسم؟');">حذف</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
