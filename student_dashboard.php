<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم الطالب</title>
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
      background-color: #f5f7fb;
      color: var(--dark);
      line-height: 1.6;
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

    .user-info {
      background: rgba(255, 255, 255, 0.2);
      padding: 10px 15px;
      border-radius: var(--border-radius);
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 10px;
    }

    .user-info i {
      font-size: 1.2rem;
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

    /* Main Content Styles */
    .main-content {
      display: grid;
      grid-template-columns: 1fr ;
      gap: 25px;
      margin-bottom: 2rem;
    }

    @media (max-width: 992px) {
      .main-content {
        grid-template-columns: 1fr;
      }
    }

    .content-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: var(--transition);
    }

    .content-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
    }

    .content-card h2 {
      color: var(--primary);
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .content-card h2 i {
      color: var(--secondary);
    }

    /* Welcome Section */
    .welcome-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-right: 4px solid var(--primary);
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 1rem;
    }

    .stat-item {
      background: white;
      padding: 1rem;
      border-radius: var(--border-radius);
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
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

    /* Project Details */
    .project-details p {
      margin-bottom: 10px;
      padding: 8px 0;
      border-bottom: 1px dashed #eee;
    }

    .status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .status.pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status.accepted {
      background-color: #d1ecf1;
      color: #0c5460;
    }

    .status.rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    .project-files {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      margin: 15px 0;
    }

    .project-files a {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 15px;
      background: #f0f5ff;
      color: var(--primary);
      text-decoration: none;
      border-radius: var(--border-radius);
      transition: var(--transition);
      border: 1px solid #dbe4ff;
    }

    .project-files a:hover {
      background: var(--primary);
      color: white;
    }

    /* Buttons */
    .actions {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      margin-top: 1rem;
    }

    .button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 25px;
      text-decoration: none;
      border-radius: var(--border-radius);
      font-weight: 600;
      transition: var(--transition);
      border: none;
      cursor: pointer;
    }

    .button.primary {
      background: var(--primary);
      color: white;
    }

    .button.primary:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
    }

    .button.secondary {
      background: white;
      color: var(--primary);
      border: 2px solid var(--primary);
    }

    .button.secondary:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-3px);
    }

    /* Search Form */
    .search-form {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
      margin-top: 1rem;
    }

    @media (min-width: 768px) {
      .search-form {
        grid-template-columns: 1fr auto auto auto;
      }
    }

    .search-form input,
    .search-form select {
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-family: 'Cairo', sans-serif;
      transition: var(--transition);
    }

    .search-form input:focus,
    .search-form select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .search-form button {
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

    .search-form button:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* Sidebar */
    .sidebar {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 1.5rem;
      height: fit-content;
    }

    .sidebar-section {
      margin-bottom: 2rem;
    }

    .sidebar-section:last-child {
      margin-bottom: 0;
    }

    .sidebar-section h3 {
      color: var(--primary);
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sidebar-section h3 i {
      color: var(--secondary);
    }

    .quick-links {
      list-style: none;
    }

    .quick-links li {
      margin-bottom: 10px;
    }

    .quick-links a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 15px;
      background: #f8f9fa;
      color: var(--dark);
      text-decoration: none;
      border-radius: var(--border-radius);
      transition: var(--transition);
    }

    .quick-links a:hover {
      background: var(--primary);
      color: white;
      transform: translateX(-5px);
    }

    .progress-container {
      margin-top: 1rem;
    }

    .progress-bar {
      height: 10px;
      background: #e9ecef;
      border-radius: 5px;
      overflow: hidden;
      margin-bottom: 5px;
    }

    .progress {
      height: 100%;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 5px;
    }

    .progress-text {
      display: flex;
      justify-content: space-between;
      font-size: 0.85rem;
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

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .actions {
        flex-direction: column;
      }

      .button {
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
            <h1>لوحة تحكم الطالب</h1>
            <p>نظام إدارة المشاريع الطلابية</p>
          </div>
        </div>
        <div class="user-info">
          <i class="fas fa-user-circle"></i>
          <span>مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
      </div>
    </header>

    <nav>
      <a href="index.html" class="active"><i class="fas fa-home"></i> الرئيسية</a>
      <a href="projects.php"><i class="fas fa-project-diagram"></i> عرض المشاريع</a>
      <a href="upload_project.php"><i class="fas fa-upload"></i> رفع مشروع</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </nav>

    <div class="main-content">
      <div class="main-content-left">
        <div class="content-card welcome-section">
          <h2><i class="fas fa-user-graduate"></i> مرحباً بعودتك!</h2>
          <p>نظام إدارة المشاريع الطلابية يمكنك من إدارة مشاريعك وعرض إحصائيات سريعة حول تقدمك.</p>
        </div>

        <div class="content-card">
          <h2><i class="fas fa-chart-bar"></i> إحصائيات سريعة</h2>
          <div class="stats-grid">
            <div class="stat-item">
              <i class="fas fa-check-circle"></i>
              <span>المشاريع المقبولة</span>
              <strong><?php echo $total; ?></strong>
            </div>
            <div class="stat-item">
              <i class="fas fa-lightbulb"></i>
              <span>مشروعك الحالي</span>
              <strong><?php echo $my ? 'موجود' : 'لا يوجد'; ?></strong>
            </div>
            <div class="stat-item">
              <i class="fas fa-hourglass-half"></i>
              <span>حالة المشروع</span>
              <strong><?php echo $project ? ($project['status'] == 'pending' ? 'قيد المراجعة' : ($project['status'] == 'accepted' ? 'مقبول' : 'مرفوض')) : 'غير محدد'; ?></strong>
            </div>
          </div>
        </div>

        <?php if ($project): ?>
          <div class="content-card project-details">
            <h2><i class="fas fa-book"></i> مشروعك الحالي</h2>
            <p><strong>عنوان المشروع:</strong> <?php echo htmlspecialchars($project['title']); ?></p>
            <p><strong>ملخص:</strong> <?php echo htmlspecialchars($project['summary']); ?></p>
            <p><strong>السنة:</strong> <?php echo htmlspecialchars($project['year']); ?></p>
            <p><strong>الحالة:</strong> <span class="status <?php echo $project['status']; ?>">
              <?php
                if ($project['status'] == 'pending') echo 'قيد المراجعة';
                elseif ($project['status'] == 'accepted') echo 'مقبول';
                else echo 'مرفوض';
              ?>
            </span></p>
            <?php if ($project['notes']): ?>
              <p><strong>ملاحظات المشرف:</strong> <?php echo htmlspecialchars($project['notes']); ?></p>
            <?php endif; ?>
            <div class="project-files">
              <?php if ($project['pdf_file']): ?>
                <a href="uploads/<?php echo $project['pdf_file']; ?>" target="_blank"><i class="fas fa-file-pdf"></i> تحميل ملف المشروع (PDF)</a>
              <?php endif; ?>
              <?php if ($project['code_file']): ?>
                <a href="uploads/<?php echo $project['code_file']; ?>" target="_blank"><i class="fas fa-code"></i> تحميل كود المشروع</a>
              <?php endif; ?>
            </div>
            <div class="actions">
              <a href="edit_project.php" class="button primary"><i class="fas fa-edit"></i> تعديل المشروع</a>
            </div>
          </div>
        <?php else: ?>
          <div class="content-card">
            <h2><i class="fas fa-upload"></i> رفع مشروع</h2>
            <p>لا يوجد مشروع مرفوع حالياً. يمكنك رفع مشروعك الجديد من هنا.</p>
            <div class="actions">
              <a href="upload_project.php" class="button primary"><i class="fas fa-upload"></i> رفع مشروع جديد</a>
            </div>
          </div>
        <?php endif; ?>

        <div class="content-card">
          <h2><i class="fas fa-filter"></i> بحث وتصفية المشاريع</h2>
          <form method="GET" action="projects.php" class="search-form">
            <input type="text" name="search" placeholder="بحث عن مشروع بالعنوان أو الكلمات المفتاحية">
            <select name="section">
              <option value="">كل الأقسام</option>
              <?php $sec = $conn->query("SELECT * FROM sections ORDER BY name ASC"); while($s = $sec->fetch_assoc()): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
              <?php endwhile; ?>
            </select>
            <select name="year">
              <option value="">كل السنوات</option>
              <?php for($y=date('Y');$y>=2015;$y--): ?>
                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
            <button type="submit"><i class="fas fa-search"></i> بحث</button>
          </form>
        </div>
      </div>

      <div class="sidebar">
        <div class="sidebar-section">
          <h3><i class="fas fa-link"></i> روابط سريعة</h3>
          <ul class="quick-links">
            <li><a href="projects.php"><i class="fas fa-project-diagram"></i> جميع المشاريع</a></li>
            <li><a href="upload_project.php"><i class="fas fa-upload"></i> رفع مشروع جديد</a></li>
            <li><a href="#"><i class="fas fa-calendar-alt"></i> التقويم الأكاديمي</a></li>
            <li><a href="#"><i class="fas fa-question-circle"></i> المساعدة والدعم</a></li>
          </ul>
        </div>

        <div class="sidebar-section">
          <h3><i class="fas fa-tasks"></i> تقدم المشروع</h3>
          <div class="progress-container">
            <div class="progress-bar">
              <div class="progress" style="width: <?php echo $project ? ($project['status'] == 'accepted' ? '100%' : ($project['status'] == 'pending' ? '50%' : '0%')) : '0%'; ?>"></div>
            </div>
            <div class="progress-text">
              <span>بداية</span>
              <span>نهاية</span>
            </div>
          </div>
          <p style="margin-top: 10px; font-size: 0.9rem; text-align: center;">
            <?php 
              if (!$project) {
                echo 'لم يتم رفع مشروع بعد';
              } else {
                if ($project['status'] == 'pending') echo 'المشروع قيد المراجعة';
                elseif ($project['status'] == 'accepted') echo 'تم قبول المشروع';
                else echo 'تم رفض المشروع';
              }
            ?>
          </p>
        </div>

        <div class="sidebar-section">
          <h3><i class="fas fa-info-circle"></i> معلومات هامة</h3>
          <ul style="list-style: none; font-size: 0.9rem;">
            <li style="margin-bottom: 10px; padding: 8px; background: #f0f5ff; border-radius: 5px;">
              <i class="fas fa-clock" style="color: var(--primary); margin-left: 5px;"></i>
              آخر موعد لرفع المشاريع: 15 يونيو
            </li>
            <li style="margin-bottom: 10px; padding: 8px; background: #f0f5ff; border-radius: 5px;">
              <i class="fas fa-exclamation-triangle" style="color: var(--warning); margin-left: 5px;"></i>
              تأكد من اتباع دليل المشاريع
            </li>
          </ul>
        </div>
      </div>
    </div>

    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>

  <button onclick="topFunction()" id="scrollToTopBtn" class="scroll-to-top" title="Go to top">
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