<?php
// لوحة تحكم الطالب
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// جلب مشروع الطالب إن وجد
$stmt = $conn->prepare("SELECT * FROM projects WHERE student_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم الطالب</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    .status { text-align:center; margin:15px 0; font-size:1.1em; }
    .status.accepted { color: #27ae60; }
    .status.pending { color: #f39c12; }
    .status.rejected { color: #e74c3c; }
    .project-info { background:#f7f7f7; border-radius:8px; padding:20px; margin-bottom:20px; }
    .actions { text-align:center; margin-top:20px; }
    .actions a { background:#2980b9; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold; margin:0 5px; }
    .actions a:hover { background:#1abc9c; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>لوحة تحكم الطالب</h1>
    </header>
    <!-- خيارات البحث والتصفية -->
    <div style="background:#eaf6ff; border-radius:8px; padding:18px; margin-bottom:25px;">
      <form method="GET" action="projects.php" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <input type="text" name="search" placeholder="بحث عن مشروع بالعنوان أو الكلمات المفتاحية" style="flex:2; padding:10px; border-radius:6px; border:1px solid #b2bec3;">
        <select name="section" style="flex:1; padding:10px; border-radius:6px; border:1px solid #b2bec3;">
          <option value="">كل الأقسام</option>
          <?php $sec = $conn->query("SELECT * FROM sections ORDER BY name ASC"); while($s = $sec->fetch_assoc()): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
          <?php endwhile; ?>
        </select>
        <select name="year" style="flex:1; padding:10px; border-radius:6px; border:1px solid #b2bec3;">
          <option value="">كل السنوات</option>
          <?php for($y=date('Y');$y>=2015;$y--): ?>
            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
        <button type="submit" style="background:#2980b9; color:#fff; padding:10px 20px; border-radius:6px; border:none; font-weight:bold;">بحث</button>
      </form>
    </div>

    <!-- إحصائيات سريعة -->
    <div style="background:#f7f7f7; border-radius:8px; padding:15px; margin-bottom:20px; text-align:center;">
      <?php
        $total = $conn->query("SELECT COUNT(*) AS c FROM projects WHERE status='accepted'")->fetch_assoc()['c'];
        $my = $project ? 1 : 0;
      ?>
      <span>عدد المشاريع المقبولة: <strong><?php echo $total; ?></strong></span> | 
      <span>مشروعك الحالي: <strong><?php echo $my ? 'موجود' : 'لا يوجد'; ?></strong></span>
    </div>

    <?php if ($project): ?>
      <div class="project-info">
        <strong>عنوان المشروع:</strong> <?php echo htmlspecialchars($project['title']); ?><br>
        <strong>ملخص:</strong> <?php echo htmlspecialchars($project['summary']); ?><br>
        <strong>السنة:</strong> <?php echo htmlspecialchars($project['year']); ?><br>
        <strong>الحالة:</strong> <span class="status <?php echo $project['status']; ?>">
          <?php
            if ($project['status'] == 'pending') echo 'قيد المراجعة';
            elseif ($project['status'] == 'accepted') echo 'مقبول';
            else echo 'مرفوض';
          ?>
        </span><br>
        <?php if ($project['notes']): ?>
          <strong>ملاحظات المشرف:</strong> <?php echo htmlspecialchars($project['notes']); ?><br>
        <?php endif; ?>
        <?php if ($project['pdf_file']): ?>
          <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank">تحميل ملف المشروع (PDF)</a><br>
        <?php endif; ?>
        <?php if ($project['code_file']): ?>
          <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank">تحميل كود المشروع</a><br>
        <?php endif; ?>
      </div>
      <div class="actions">
        <a href="edit_project.php">تعديل المشروع</a>
      </div>
    <?php else: ?>
      <div class="actions">
        <a href="upload_project.php">رفع مشروع جديد</a>
      </div>
    <?php endif; ?>

    <!-- عرض المشاريع المقبولة -->
    <div style="margin-top:30px;">
      <a href="projects.php" style="background:#1abc9c; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:bold;">عرض جميع المشاريع المقبولة</a>
    </div>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
