<?php
// صفحة مراجعة مشروع من قبل المشرف
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supervisor')) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id'])) {
    header('Location: admin_dashboard.php');
    exit;
}
$project_id = intval($_GET['id']);
$error = '';
$success = '';
// جلب بيانات المشروع
$stmt = $conn->prepare("SELECT p.*, u.username AS student, s.name AS section FROM projects p JOIN users u ON p.student_id = u.id JOIN sections s ON p.section_id = s.id WHERE p.id = ? LIMIT 1");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) {
    header('Location: admin_dashboard.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $stmt = $conn->prepare("UPDATE projects SET status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param('ssi', $status, $notes, $project_id);
    if ($stmt->execute()) {
        $success = 'تم تحديث حالة المشروع بنجاح.';
        // إعادة جلب البيانات
        $stmt = $conn->prepare("SELECT p.*, u.username AS student, s.name AS section FROM projects p JOIN users u ON p.student_id = u.id JOIN sections s ON p.section_id = s.id WHERE p.id = ? LIMIT 1");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'حدث خطأ أثناء تحديث حالة المشروع.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>مراجعة مشروع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    .project-info { background:#f7f7f7; border-radius:8px; padding:20px; margin-bottom:20px; }
    label { display: block; margin-top: 18px; color: #34495e; font-weight: 600; }
    textarea, select { width: 100%; padding: 12px; margin-top: 7px; border-radius: 6px; border: 1px solid #b2bec3; font-size: 1em; background: #f7f7f7; transition: border-color 0.2s; }
    textarea:focus, select:focus { border-color: #2980b9; outline: none; }
    button { background: linear-gradient(90deg, #2980b9 0%, #1abc9c 100%); color: #fff; border: none; padding: 14px 0; border-radius: 6px; margin-top: 25px; cursor: pointer; font-size: 1.1em; font-weight: bold; width: 100%; box-shadow: 0 2px 8px #ccc; transition: background 0.2s; }
    button:hover { background: linear-gradient(90deg, #1abc9c 0%, #2980b9 100%); }
    .error { color: #e74c3c; text-align: center; margin-top: 10px; }
    .success { color: #27ae60; text-align: center; margin-top: 10px; }
    .back { text-align:center; margin-top:20px; }
    .back a { color:#2980b9; text-decoration:underline; font-weight:bold; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>مراجعة مشروع</h1>
    </header>
    <div class="project-info">
      <strong>عنوان المشروع:</strong> <?php echo htmlspecialchars($project['title']); ?><br>
      <strong>الطالب:</strong> <?php echo htmlspecialchars($project['student']); ?><br>
      <strong>القسم:</strong> <?php echo htmlspecialchars($project['section']); ?><br>
      <strong>السنة:</strong> <?php echo htmlspecialchars($project['year']); ?><br>
      <strong>ملخص:</strong> <?php echo htmlspecialchars($project['summary']); ?><br>
      <?php if ($project['pdf_file']): ?>
        <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank">تحميل ملف المشروع (PDF)</a><br>
      <?php endif; ?>
      <?php if ($project['code_file']): ?>
        <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank">تحميل كود المشروع</a><br>
      <?php endif; ?>
      <strong>الحالة الحالية:</strong> <?php
        if ($project['status'] == 'pending') echo 'قيد المراجعة';
        elseif ($project['status'] == 'accepted') echo 'مقبول';
        else echo 'مرفوض';
      ?><br>
      <?php if ($project['notes']): ?>
        <strong>ملاحظات سابقة:</strong> <?php echo htmlspecialchars($project['notes']); ?><br>
      <?php endif; ?>
    </div>
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST">
      <label for="status">تغيير حالة المشروع</label>
      <select name="status" id="status" required>
        <option value="pending" <?php if($project['status']=='pending') echo 'selected'; ?>>قيد المراجعة</option>
        <option value="accepted" <?php if($project['status']=='accepted') echo 'selected'; ?>>مقبول</option>
        <option value="rejected" <?php if($project['status']=='rejected') echo 'selected'; ?>>مرفوض</option>
      </select>
      <label for="notes">ملاحظات المشرف</label>
      <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($project['notes']); ?></textarea>
      <button type="submit"><i class="fa-solid fa-check"></i> حفظ التغييرات</button>
    </form>
    <div class="back">
      <a href="admin_dashboard.php">&larr; العودة للوحة التحكم</a>
    </div>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
