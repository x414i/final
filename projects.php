<?php
// صفحة عرض المشاريع المقبولة للزوار والطلاب
require_once 'db.php';
// البحث والتصفية
$where = "WHERE p.status = 'accepted'";
$params = [];
if (!empty($_GET['section_id'])) {
    $where .= " AND p.section_id = ?";
    $params[] = intval($_GET['section_id']);
}
if (!empty($_GET['year'])) {
    $where .= " AND p.year = ?";
    $params[] = intval($_GET['year']);
}
if (!empty($_GET['keywords'])) {
    $where .= " AND (p.title LIKE ? OR p.summary LIKE ?)";
    $kw = '%' . $_GET['keywords'] . '%';
    $params[] = $kw;
    $params[] = $kw;
}
// جلب الأقسام
$sections = $conn->query("SELECT id, name FROM sections");
// جلب المشاريع المقبولة
$sql = "SELECT p.*, u.username AS student, s.name AS section FROM projects p JOIN users u ON p.student_id = u.id JOIN sections s ON p.section_id = s.id $where ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql . (count($params) ? str_repeat('s', count($params)) : ''));
if (count($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projects = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>أرشيف المشاريع المقبولة</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 900px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    .filters { margin-bottom: 30px; text-align:center; }
    .filters select, .filters input { padding:8px; border-radius:6px; border:1px solid #b2bec3; margin:0 5px; font-size:1em; }
    .project-card { background:#f7f7f7; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px #eee; }
    .project-title { color:#2980b9; font-size:1.2em; font-weight:bold; }
    .project-meta { color:#636e72; font-size:0.95em; margin-bottom:8px; }
    .project-summary { margin-bottom:10px; }
    .project-files a { color:#1abc9c; text-decoration:underline; margin-right:10px; }
    .project-files a:hover { color:#2980b9; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>أرشيف المشاريع المقبولة</h1>
    </header>
    <form class="filters" method="GET">
      <select name="section_id">
        <option value="">كل الأقسام</option>
        <?php while($row = $sections->fetch_assoc()): ?>
          <option value="<?php echo $row['id']; ?>" <?php if(isset($_GET['section_id']) && $_GET['section_id']==$row['id']) echo 'selected'; ?>><?php echo htmlspecialchars($row['name']); ?></option>
        <?php endwhile; ?>
      </select>
      <input type="number" name="year" placeholder="السنة" value="<?php echo isset($_GET['year']) ? htmlspecialchars($_GET['year']) : ''; ?>">
      <input type="text" name="keywords" placeholder="بحث بالكلمات المفتاحية" value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>">
      <button type="submit"><i class="fa-solid fa-search"></i> بحث</button>
    </form>
    <?php while($project = $projects->fetch_assoc()): ?>
      <div class="project-card">
        <div class="project-title"> <?php echo htmlspecialchars($project['title']); ?> </div>
        <div class="project-meta">
          القسم: <?php echo htmlspecialchars($project['section']); ?> | السنة: <?php echo htmlspecialchars($project['year']); ?> | الطالب: <?php echo htmlspecialchars($project['student']); ?>
        </div>
        <div class="project-summary"> <?php echo htmlspecialchars($project['summary']); ?> </div>
        <div class="project-files">
          <?php if ($project['pdf_file']): ?>
            <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank">تحميل PDF</a>
          <?php endif; ?>
          <?php if ($project['code_file']): ?>
            <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank">تحميل الكود</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
