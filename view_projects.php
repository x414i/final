<?php
require_once 'db.php';

// --- الإحصائيات ---
// إجمالي المشاريع المعتمدة
$total_projects_result = $conn->query("SELECT COUNT(*) as total FROM projects WHERE status = 'accepted'");
$total_projects = $total_projects_result->fetch_assoc()['total'];

// عدد الطلاب المشاركين (بافتراض طالب واحد لكل مشروع معتمد)
$total_students_result = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM projects WHERE status = 'accepted'");
$total_students = $total_students_result->fetch_assoc()['total'];

// عدد الأقسام المشاركة
$total_sections_result = $conn->query("SELECT COUNT(DISTINCT section_id) as total FROM projects WHERE status = 'accepted'");
$total_sections = $total_sections_result->fetch_assoc()['total'];

// مشاريع السنة الحالية
$current_year = date('Y');
$current_year_projects = $conn->query("SELECT COUNT(*) as count FROM projects WHERE year = $current_year AND status = 'accepted'")->fetch_assoc()['count'];


// --- البحث والتصفية ---
$where = "WHERE p.status = 'accepted'";
$params = [];
$types = '';

if (!empty($_GET['section_id'])) {
    $where .= " AND p.section_id = ?";
    $params[] = intval($_GET['section_id']);
    $types .= 'i';
}
if (!empty($_GET['year'])) {
    $where .= " AND p.year = ?";
    $params[] = intval($_GET['year']);
    $types .= 'i';
}
if (!empty($_GET['keywords'])) {
    $where .= " AND (p.title LIKE ? OR p.summary LIKE ? OR u.username LIKE ?)";
    $kw = '%' . $_GET['keywords'] . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $types .= 'sss';
}

// الترتيب
$orderBy = "ORDER BY p.uploaded_at DESC"; // Default order
$sort_options = ['year_desc', 'year_asc', 'title_asc', 'title_desc'];
if (!empty($_GET['sort']) && in_array($_GET['sort'], $sort_options)) {
    switch ($_GET['sort']) {
        case 'year_desc':
            $orderBy = "ORDER BY p.year DESC, p.title ASC";
            break;
        case 'year_asc':
            $orderBy = "ORDER BY p.year ASC, p.title ASC";
            break;
        case 'title_asc':
            $orderBy = "ORDER BY p.title ASC";
            break;
        case 'title_desc':
            $orderBy = "ORDER BY p.title DESC";
            break;
    }
}

// جلب الأقسام للفلتر
$sections = $conn->query("SELECT id, name FROM sections ORDER BY name ASC");

// جلب المشاريع
$sql = "
    SELECT p.id, p.title, p.summary, p.year, p.pdf_file, p.code_file,
           u.username AS student_name,
           s.name AS section_name
    FROM projects p
    JOIN users u ON p.student_id = u.id
    JOIN sections s ON p.section_id = s.id
    $where
    $orderBy
";

if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>عرض المشاريع</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --secondary: #7209b7;
      --success: #4cc9f0;
      --warning: #f72585;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --border-radius: 12px;
      --box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Cairo', sans-serif;
      background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
      color: var(--dark);
      line-height: 1.6;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }

    /* Header Styles */
    header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 1.5rem 0;
      border-radius: 0 0 var(--border-radius) var(--border-radius);
      box-shadow: var(--box-shadow);
      margin-bottom: 2rem;
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo-container img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, 0.3);
      object-fit: cover;
    }

    .header-title h1 {
      font-size: 1.8rem;
      margin-bottom: 5px;
    }

    .header-title p {
      opacity: 0.9;
      font-size: 0.9rem;
    }

    /* Navigation Styles */
    nav {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 0.8rem;
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    nav a {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      text-decoration: none;
      color: var(--dark);
      border-radius: var(--border-radius);
      transition: var(--transition);
      font-weight: 600;
    }

    nav a:hover {
      background-color: var(--primary);
      color: white;
      transform: translateY(-2px);
    }

    nav a.active {
      background-color: var(--primary);
      color: white;
    }

    /* Stats Section */
    .stats-section {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .stat-item {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: var(--border-radius);
      text-align: center;
      transition: var(--transition);
      border-top: 4px solid var(--primary);
    }

    .stat-item:hover {
      transform: translateY(-3px);
    }

    .stat-item i {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 10px;
    }

    .stat-item span {
      display: block;
      color: var(--gray);
      font-size: 0.9rem;
      margin-bottom: 5px;
    }

    .stat-item strong {
      font-size: 1.5rem;
      color: var(--dark);
    }

    /* Search and Filter Section */
    .search-filter-section {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .search-filter-section h2 {
      color: var(--primary);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .filters {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
    }

    @media (min-width: 768px) {
      .filters {
        grid-template-columns: 1fr auto auto auto auto;
      }
    }

    .filters input,
    .filters select {
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-family: 'Cairo', sans-serif;
      transition: var(--transition);
    }

    .filters input:focus,
    .filters select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .filters button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-family: 'Cairo', sans-serif;
      font-weight: 600;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .filters button:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* Projects Grid */
    .projects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 25px;
      margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
      .projects-grid {
        grid-template-columns: 1fr;
      }
    }

    .project-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 1.5rem;
      transition: var(--transition);
      border-top: 4px solid var(--primary);
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
    }

    .project-card h3 {
      color: var(--primary);
      margin-bottom: 1rem;
      font-size: 1.3rem;
      line-height: 1.4;
    }

    .project-card p {
      margin-bottom: 0.8rem;
      color: var(--dark);
    }

    .project-card .summary {
      flex-grow: 1;
      margin-bottom: 1.5rem;
      color: var(--gray);
      line-height: 1.6;
    }

    .project-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #eee;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.9rem;
      color: var(--gray);
    }

    .meta-item i {
      color: var(--primary);
    }

    .file-links {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: auto;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 15px;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: var(--border-radius);
      font-weight: 600;
      transition: var(--transition);
      font-size: 0.9rem;
    }

    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    .btn.outline {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }

    .btn.outline:hover {
      background: var(--primary);
      color: white;
    }

    .no-projects {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem;
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
    }

    .no-projects i {
      font-size: 3rem;
      color: var(--gray);
      margin-bottom: 1rem;
    }

    .no-projects h3 {
      color: var(--dark);
      margin-bottom: 0.5rem;
    }

    .no-projects p {
      color: var(--gray);
    }

    /* Footer */
    footer {
      text-align: center;
      padding: 1.5rem 0;
      margin-top: 2rem;
      border-top: 1px solid #e9ecef;
      color: var(--gray);
      font-size: 0.9rem;
    }

    /* Scroll to Top Button */
    .scroll-to-top {
      position: fixed;
      bottom: 30px;
      left: 30px;
      background: var(--primary);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      transition: var(--transition);
      border: none;
      font-size: 1.2rem;
      z-index: 100;
    }

    .scroll-to-top:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }

      nav {
        flex-direction: column;
        gap: 5px;
      }

      .file-links {
        flex-direction: column;
      }

      .btn {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div class="header-content">
        <div class="logo-container">
          <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
          <div class="header-title">
            <h1>المشاريع المعتمدة</h1>
            <p>استعرض المشاريع الطلابية المقبولة</p>
          </div>
        </div>
        <div class="user-info">
          <i class="fas fa-project-diagram"></i>
          <span>مكتبة المشاريع الطلابية</span>
        </div>
      </div>
    </header>

    <nav>
      <a href="student_dashboard.php"><i class="fas fa-home"></i> الرئيسية</a>
      <a href="projects.php" class="active"><i class="fas fa-project-diagram"></i> عرض المشاريع</a>
      <a href="upload_project.php"><i class="fas fa-upload"></i> رفع مشروع</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </nav>

    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-item">
          <i class="fas fa-check-circle"></i>
          <span>إجمالي المشاريع</span>
          <strong><?php echo $total_projects; ?></strong>
        </div>
        <div class="stat-item">
          <i class="fas fa-calendar"></i>
          <span>مشاريع السنة الحالية</span>
          <strong><?php echo $current_year_projects; ?></strong>
        </div>
        <div class="stat-item">
          <i class="fas fa-users"></i>
          <span>الطلاب المشاركين</span>
          <strong><?php echo $total_students; ?></strong>
        </div>
        <div class="stat-item">
          <i class="fas fa-building"></i>
          <span>الأقسام المشاركة</span>
          <strong><?php echo $total_sections; ?></strong>
        </div>
      </div>
    </div>

    <div class="search-filter-section">
      <h2><i class="fas fa-filter"></i> بحث وتصفية المشاريع</h2>
      <form class="filters" method="GET">
        <input type="text" name="keywords" placeholder="ابحث في العنوان، الملخص، أو اسم الطالب..." value="<?php echo isset($_GET['keywords']) ? htmlspecialchars($_GET['keywords']) : ''; ?>">
        
        <select name="section_id">
          <option value="">كل الأقسام</option>
          <?php 
          if ($sections && $sections->num_rows > 0) {
              $sections->data_seek(0);
              while($row = $sections->fetch_assoc()): 
          ?>
            <option value="<?php echo $row['id']; ?>" <?php if(isset($_GET['section_id']) && $_GET['section_id']==$row['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($row['name']); ?>
            </option>
          <?php 
              endwhile;
          }
          ?>
        </select>
        
        <select name="year">
          <option value="">كل السنوات</option>
          <?php for($y = date("Y"); $y >= 2015; $y--): ?>
          <option value="<?php echo $y; ?>" <?php if(isset($_GET['year']) && $_GET['year']==$y) echo 'selected'; ?>><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
        
        <select name="sort">
          <option value="">ترتيب حسب</option>
          <option value="year_desc" <?php if(isset($_GET['sort']) && $_GET['sort']=='year_desc') echo 'selected'; ?>>السنة (الأحدث أولاً)</option>
          <option value="year_asc" <?php if(isset($_GET['sort']) && $_GET['sort']=='year_asc') echo 'selected'; ?>>السنة (الأقدم أولاً)</option>
          <option value="title_asc" <?php if(isset($_GET['sort']) && $_GET['sort']=='title_asc') echo 'selected'; ?>>العنوان (أ-ي)</option>
          <option value="title_desc" <?php if(isset($_GET['sort']) && $_GET['sort']=='title_desc') echo 'selected'; ?>>العنوان (ي-أ)</option>
        </select>
        
        <button type="submit"><i class="fa-solid fa-search"></i> تصفية</button>
      </form>
    </div>

    <main class="projects-grid">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while($project = $result->fetch_assoc()): ?>
        <div class="project-card">
          <h3><?php echo htmlspecialchars($project['title']); ?></h3>
          
          <div class="project-meta">
            <div class="meta-item">
              <i class="fas fa-user-graduate"></i>
              <span><?php echo htmlspecialchars($project['student_name']); ?></span>
            </div>
            <div class="meta-item">
              <i class="fas fa-layer-group"></i>
              <span><?php echo htmlspecialchars($project['section_name']); ?></span>
            </div>
            <div class="meta-item">
              <i class="fas fa-calendar"></i>
              <span><?php echo htmlspecialchars($project['year']); ?></span>
            </div>
          </div>
          
          <p class="summary"><?php echo htmlspecialchars($project['summary']); ?></p>
          
          <div class="file-links">
            <?php if($project['pdf_file']): ?>
              <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank" class="btn"><i class="fa-solid fa-file-pdf"></i> عرض PDF</a>
            <?php endif; ?>
            <?php if($project['code_file']): ?>
              <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank" class="btn outline"><i class="fa-solid fa-file-zipper"></i> تحميل الكود</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-projects">
          <i class="fas fa-folder-open"></i>
          <h3>لا توجد مشاريع معتمدة</h3>
          <p>لم يتم اعتماد أي مشاريع حتى الآن أو لا توجد نتائج تطابق معايير البحث.</p>
          <a href="upload_project.php" class="btn" style="margin-top: 1rem;"><i class="fas fa-upload"></i> ابدأ برفع مشروعك</a>
        </div>
      <?php endif; ?>
    </main>

    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>

  <button onclick="topFunction()" id="scrollToTopBtn" class="scroll-to-top" title="انتقل إلى الأعلى">
    <i class="fas fa-arrow-up"></i>
  </button>

  <script>
    // Get the button
    let mybutton = document.getElementById("scrollToTopBtn");

    // When the user scrolls down 20px from the top of the document, show the button
    window.onscroll = function() { scrollFunction() };

    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        mybutton.style.display = "flex";
      } else {
        mybutton.style.display = "none";
      }
    }

    // When the user clicks on the button, scroll to the top of the document
    function topFunction() {
      document.body.scrollTop = 0; // For Safari
      document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
    }
  </script>
</body>
</html>