<?php
// صفحة رفع مشروع جديد للطالب
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
// جلب الأقسام
$sections = $conn->query("SELECT id, name FROM sections");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $year = intval($_POST['year']);
    $section_id = intval($_POST['section_id']);
    $pdf_file = '';
    $code_file = '';
    // رفع الملفات
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $pdf_file = uniqid() . '_' . $_FILES['pdf_file']['name'];
        move_uploaded_file($_FILES['pdf_file']['tmp_name'], 'uploads/' . $pdf_file);
    }
    if (isset($_FILES['code_file']) && $_FILES['code_file']['error'] == 0) {
        $code_file = uniqid() . '_' . $_FILES['code_file']['name'];
        move_uploaded_file($_FILES['code_file']['tmp_name'], 'uploads/' . $code_file);
    }
    // حفظ المشروع
    $stmt = $conn->prepare("INSERT INTO projects (title, summary, student_id, section_id, year, pdf_file, code_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiiiss', $title, $summary, $user_id, $section_id, $year, $pdf_file, $code_file);
    if ($stmt->execute()) {
        $success = 'تم رفع المشروع بنجاح! سيتم مراجعته من قبل المشرف.';
    } else {
        $error = 'حدث خطأ أثناء رفع المشروع.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>رفع مشروع جديد</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    form { margin-top: 10px; }
    label { display: block; margin-top: 18px; color: #34495e; font-weight: 600; }
    input, textarea, select { width: 100%; padding: 12px; margin-top: 7px; border-radius: 6px; border: 1px solid #b2bec3; font-size: 1em; background: #f7f7f7; transition: border-color 0.2s; }
    input:focus, textarea:focus, select:focus { border-color: #2980b9; outline: none; }
    button { background: linear-gradient(90deg, #2980b9 0%, #1abc9c 100%); color: #fff; border: none; padding: 14px 0; border-radius: 6px; margin-top: 25px; cursor: pointer; font-size: 1.1em; font-weight: bold; width: 100%; box-shadow: 0 2px 8px #ccc; transition: background 0.2s; }
    button:hover { background: linear-gradient(90deg, #1abc9c 0%, #2980b9 100%); }
    .error { color: #e74c3c; text-align: center; margin-top: 10px; }
    .success { color: #27ae60; text-align: center; margin-top: 10px; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>رفع مشروع جديد</h1>
    </header>
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <label for="title"><i class="fa-solid fa-heading"></i> عنوان المشروع</label>
      <input type="text" name="title" id="title" required>
      <label for="summary"><i class="fa-solid fa-file-lines"></i> ملخص المشروع</label>
      <textarea name="summary" id="summary" required></textarea>
      <label for="year"><i class="fa-solid fa-calendar-days"></i> السنة</label>
      <input type="number" name="year" id="year" required>
      <label for="section_id"><i class="fa-solid fa-layer-group"></i> القسم</label>
      <select name="section_id" id="section_id" required>
        <option value="">اختر القسم</option>
        <?php while($row = $sections->fetch_assoc()): ?>
          <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
        <?php endwhile; ?>
      </select>
      <label for="pdf_file"><i class="fa-solid fa-file-pdf"></i> ملف المشروع (PDF)</label>
      <input type="file" name="pdf_file" id="pdf_file" accept="application/pdf">
      <label for="code_file"><i class="fa-solid fa-file-code"></i> كود المشروع (ZIP)</label>
      <input type="file" name="code_file" id="code_file" accept="application/zip">
      <button type="submit"><i class="fa-solid fa-upload"></i> رفع المشروع</button>
    </form>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
