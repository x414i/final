<?php
session_start();
include 'db.php';

// Redirect if user is not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$sections = null;

// Fetch sections from the database
$sql_sections = "SELECT id, name FROM sections ORDER BY name ASC";
$result_sections = $conn->query($sql_sections);

if (!$result_sections) {
    $error = "خطأ في جلب الأقسام: " . $conn->error;
}

// Handle project upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $year = intval($_POST['year']);
    $section_id = intval($_POST['section_id']);
    $student_id = $_SESSION['user_id'];

    // التحقق من صحة البيانات الأساسية
    if (empty($title) || empty($summary) || empty($year) || empty($section_id)) {
        $error = "جميع الحقول الإلزامية مطلوبة";
    } else {
        // File upload handling
        $pdf_file_name = '';
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $pdf_target_dir = "uploads/pdfs/";
            if (!is_dir($pdf_target_dir)) {
                mkdir($pdf_target_dir, 0777, true);
            }
            // استخدم اسم الملف فقط بدلاً من المسار الكامل
            $pdf_file_name = uniqid('pdf_') . '_' . basename($_FILES['pdf_file']['name']);
            $pdf_file_path = $pdf_target_dir . $pdf_file_name;
            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdf_file_path)) {
                $error = "فشل في رفع ملف PDF.";
            }
        }

        $code_file_name = '';
        if (empty($error) && isset($_FILES['code_file']) && $_FILES['code_file']['error'] === UPLOAD_ERR_OK) {
            $code_target_dir = "uploads/code/";
            if (!is_dir($code_target_dir)) {
                mkdir($code_target_dir, 0777, true);
            }
            // استخدم اسم الملف فقط بدلاً من المسار الكامل
            $code_file_name = uniqid('code_') . '_' . basename($_FILES['code_file']['name']);
            $code_file_path = $code_target_dir . $code_file_name;
            if (!move_uploaded_file($_FILES['code_file']['tmp_name'], $code_file_path)) {
                $error = "فشل في رفع ملف الكود.";
            }
        }

        if (empty($error)) {
            // استخدم أسماء الملفات فقط في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO projects (student_id, section_id, title, summary, year, pdf_file, code_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iisssss", $student_id, $section_id, $title, $summary, $year, $pdf_file_name, $code_file_name);

            if ($stmt->execute()) {
                $success = "تم رفع المشروع بنجاح! سيتم مراجعته قريباً.";
                // إعادة تعيين المتغيرات لعرض النموذج فارغ
                $title = $summary = $year = $section_id = '';
            } else {
                $error = "خطأ في قاعدة البيانات: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>رفع مشروع جديد</title>
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
      padding: 20px;
    }

    .container {
      max-width: 800px;
      width: 100%;
      margin: 0 auto;
    }

    /* Header Styles */
    header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 1.5rem 0;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      box-shadow: var(--box-shadow);
      margin-bottom: 0;
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

    .user-info {
      background: rgba(255, 255, 255, 0.2);
      padding: 10px 15px;
      border-radius: var(--border-radius);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-info i {
      font-size: 1.2rem;
    }

    /* Navigation Styles */
    nav {
      background: white;
      padding: 0.8rem 2rem;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      border-bottom: 1px solid #e9ecef;
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

    /* Form Container */
    .form-container {
      background: white;
      border-radius: 0 0 var(--border-radius) var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .form-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #f0f0f0;
    }

    .form-header h2 {
      color: var(--primary);
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .form-header p {
      color: var(--gray);
      font-size: 1rem;
    }

    /* Form Styles */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }

    @media (min-width: 768px) {
      .form-grid {
        grid-template-columns: 1fr 1fr;
      }
      
      .form-full-width {
        grid-column: 1 / -1;
      }
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group label i {
      color: var(--primary);
      width: 20px;
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-family: 'Cairo', sans-serif;
      transition: var(--transition);
      background: #f8f9fa;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
      background: white;
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }

    .file-upload-container {
      border: 2px dashed #ddd;
      border-radius: var(--border-radius);
      padding: 1.5rem;
      text-align: center;
      transition: var(--transition);
      background: #f8f9fa;
    }

    .file-upload-container:hover {
      border-color: var(--primary);
      background: #f0f5ff;
    }

    .file-upload-container i {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 1rem;
    }

    .file-upload-container h4 {
      margin-bottom: 0.5rem;
      color: var(--dark);
    }

    .file-upload-container p {
      color: var(--gray);
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .file-input {
      width: 100%;
      padding: 10px;
      border-radius: var(--border-radius);
      border: 1px solid #ddd;
      background: white;
    }

    /* Buttons */
    .form-actions {
      display: flex;
      gap: 15px;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 14px 30px;
      text-decoration: none;
      border-radius: var(--border-radius);
      font-weight: 600;
      transition: var(--transition);
      border: none;
      cursor: pointer;
      font-family: 'Cairo', sans-serif;
      font-size: 1rem;
      flex: 1;
      min-width: 150px;
    }

    .button.primary {
      background: var(--primary);
      color: white;
    }

    .button.primary:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
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

    /* Messages */
    .message {
      padding: 15px;
      border-radius: var(--border-radius);
      margin-bottom: 1.5rem;
      text-align: center;
      font-weight: 600;
    }

    .message.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .message.success {
      background: #d1edf1;
      color: #0c5460;
      border: 1px solid #b8e0e6;
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

      .form-actions {
        flex-direction: column;
      }

      .button {
        width: 100%;
      }
    }

    /* Steps Indicator */
    .steps-indicator {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
      position: relative;
    }

    .steps-indicator::before {
      content: '';
      position: absolute;
      top: 20px;
      right: 0;
      left: 0;
      height: 3px;
      background: #e9ecef;
      z-index: 1;
    }

    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 2;
    }

    .step-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      color: var(--gray);
      font-weight: bold;
      transition: var(--transition);
    }

    .step.active .step-icon {
      background: var(--primary);
      color: white;
    }

    .step.completed .step-icon {
      background: var(--success);
      color: white;
    }

    .step-text {
      font-size: 0.85rem;
      color: var(--gray);
      font-weight: 600;
    }

    .step.active .step-text {
      color: var(--primary);
    }

    .step.completed .step-text {
      color: var(--success);
    }

    .form-section {
      display: none;
    }

    .form-section.active {
      display: block;
    }

    .navigation-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
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
            <h1>نظام إدارة المشاريع الطلابية</h1>
            <p>رفع مشروع جديد</p>
          </div>
        </div>
        <div class="user-info">
          <i class="fas fa-user-circle"></i>
          <span>مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
      </div>
    </header>

    <nav>
      <a href="student_dashboard.php"><i class="fas fa-home"></i> لوحة التحكم</a>
      <a href="projects.php"><i class="fas fa-project-diagram"></i> عرض المشاريع</a>
      <a href="upload_project.php" class="active"><i class="fas fa-upload"></i> رفع مشروع</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </nav>

    <div class="form-container">
      <div class="form-header">
        <h2><i class="fas fa-cloud-upload-alt"></i> رفع مشروع جديد</h2>
        <p>املأ النموذج أدناه لرفع مشروعك الجديد</p>
      </div>

      <div class="steps-indicator">
        <div class="step active" id="step1">
          <div class="step-icon">1</div>
          <div class="step-text">معلومات المشروع</div>
        </div>
        <div class="step" id="step2">
          <div class="step-icon">2</div>
          <div class="step-text">التفاصيل</div>
        </div>
        <div class="step" id="step3">
          <div class="step-icon">3</div>
          <div class="step-text">رفع الملفات</div>
        </div>
        <div class="step" id="step4">
          <div class="step-icon">4</div>
          <div class="step-text">التأكيد</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="projectForm">
        <!-- الخطوة 1: معلومات المشروع -->
        <div class="form-section active" id="section1">
          <div class="form-grid">
            <div class="form-group form-full-width">
              <label for="title"><i class="fas fa-heading"></i> عنوان المشروع</label>
              <input type="text" name="title" id="title" class="form-control" placeholder="أدخل عنوان المشروع" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
            </div>

            <div class="form-group form-full-width">
              <label for="summary"><i class="fas fa-file-lines"></i> ملخص المشروع</label>
              <textarea name="summary" id="summary" class="form-control" placeholder="أدخل ملخصاً مختصراً عن المشروع" required><?php echo isset($summary) ? htmlspecialchars($summary) : ''; ?></textarea>
            </div>
          </div>
          
          <div class="navigation-buttons">
            <button type="button" class="button secondary" disabled>السابق</button>
            <button type="button" class="button primary" onclick="nextStep(1)">التالي</button>
          </div>
        </div>

        <!-- الخطوة 2: التفاصيل -->
        <div class="form-section" id="section2">
          <div class="form-grid">
            <div class="form-group">
              <label for="year"><i class="fas fa-calendar-days"></i> السنة</label>
              <input type="number" name="year" id="year" class="form-control" min="2015" max="<?php echo date('Y'); ?>" value="<?php echo isset($year) ? $year : date('Y'); ?>" required>
            </div>

            <div class="form-group">
              <label for="section_id"><i class="fas fa-layer-group"></i> القسم</label>
              <select name="section_id" id="section_id" class="form-control" required>
                <option value="">اختر القسم</option>
                <?php 
                if ($result_sections && $result_sections->num_rows > 0) {
                    while($row = $result_sections->fetch_assoc()) {
                        $selected = (isset($section_id) && $section_id == $row['id']) ? 'selected' : '';
                        echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                    }
                }
                ?>
              </select>
            </div>
          </div>
          
          <div class="navigation-buttons">
            <button type="button" class="button secondary" onclick="prevStep(2)">السابق</button>
            <button type="button" class="button primary" onclick="nextStep(2)">التالي</button>
          </div>
        </div>

        <!-- الخطوة 3: رفع الملفات -->
        <div class="form-section" id="section3">
          <div class="form-grid">
            <div class="form-group form-full-width">
              <div class="file-upload-container">
                <i class="fas fa-file-pdf"></i>
                <h4>رفع ملف المشروع (PDF)</h4>
                <p>يمكنك رفع ملف PDF يحتوي على توثيق المشروع</p>
                <input type="file" name="pdf_file" id="pdf_file" class="file-input" accept="application/pdf">
              </div>
            </div>

            <div class="form-group form-full-width">
              <div class="file-upload-container">
                <i class="fas fa-file-code"></i>
                <h4>رفع كود المشروع (ZIP)</h4>
                <p>يمكنك رفع ملف مضغوط يحتوي على كود المشروع</p>
                <input type="file" name="code_file" id="code_file" class="file-input" accept="application/zip,.rar,.7z">
              </div>
            </div>
          </div>
          
          <div class="navigation-buttons">
            <button type="button" class="button secondary" onclick="prevStep(3)">السابق</button>
            <button type="button" class="button primary" onclick="nextStep(3)">التالي</button>
          </div>
        </div>

        <!-- الخطوة 4: التأكيد -->
        <div class="form-section" id="section4">
          <div class="form-grid">
            <div class="form-group form-full-width">
              <h3><i class="fas fa-check-circle"></i> تأكيد المعلومات</h3>
              <p>يرجى مراجعة المعلومات التالية قبل إرسال المشروع:</p>
              
              <div style="background: #f8f9fa; padding: 15px; border-radius: var(--border-radius); margin-top: 15px;">
                <p><strong>عنوان المشروع:</strong> <span id="confirmTitle"></span></p>
                <p><strong>ملخص المشروع:</strong> <span id="confirmSummary"></span></p>
                <p><strong>السنة:</strong> <span id="confirmYear"></span></p>
                <p><strong>القسم:</strong> <span id="confirmSection"></span></p>
                <p><strong>ملف PDF:</strong> <span id="confirmPdf"></span></p>
                <p><strong>ملف الكود:</strong> <span id="confirmCode"></span></p>
              </div>
            </div>
          </div>
          
          <div class="navigation-buttons">
            <button type="button" class="button secondary" onclick="prevStep(4)">السابق</button>
            <button type="submit" class="button primary"><i class="fas fa-upload"></i> رفع المشروع</button>
          </div>
        </div>
      </form>
    </div>

    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>

  <script>
    let currentStep = 1;
    const totalSteps = 4;

    function showStep(step) {
      // إخفاء جميع الأقسام
      document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
      });
      
      // إظهار القسم الحالي
      document.getElementById('section' + step).classList.add('active');
      
      // تحديث الخطوات
      document.querySelectorAll('.step').forEach((stepElement, index) => {
        stepElement.classList.remove('active', 'completed');
        if (index + 1 < step) {
          stepElement.classList.add('completed');
        } else if (index + 1 === step) {
          stepElement.classList.add('active');
        }
      });
      
      currentStep = step;
      
      // إذا كانت الخطوة الأخيرة، املأ معلومات التأكيد
      if (step === 4) {
        document.getElementById('confirmTitle').textContent = document.getElementById('title').value;
        document.getElementById('confirmSummary').textContent = document.getElementById('summary').value;
        document.getElementById('confirmYear').textContent = document.getElementById('year').value;
        document.getElementById('confirmSection').textContent = document.getElementById('section_id').options[document.getElementById('section_id').selectedIndex].text;
        
        const pdfFile = document.getElementById('pdf_file').files[0];
        document.getElementById('confirmPdf').textContent = pdfFile ? pdfFile.name : 'لم يتم رفع ملف';
        
        const codeFile = document.getElementById('code_file').files[0];
        document.getElementById('confirmCode').textContent = codeFile ? codeFile.name : 'لم يتم رفع ملف';
      }
    }

    function nextStep(current) {
      // التحقق من صحة البيانات قبل الانتقال
      if (validateStep(current)) {
        showStep(current + 1);
      }
    }

    function prevStep(current) {
      showStep(current - 1);
    }

    function validateStep(step) {
      switch(step) {
        case 1:
          const title = document.getElementById('title').value.trim();
          const summary = document.getElementById('summary').value.trim();
          if (!title || !summary) {
            alert('يرجى ملء جميع الحقول الإلزامية');
            return false;
          }
          break;
        case 2:
          const year = document.getElementById('year').value;
          const section = document.getElementById('section_id').value;
          if (!year || !section) {
            alert('يرجى ملء جميع الحقول الإلزامية');
            return false;
          }
          break;
        // الخطوة 3 (رفع الملفات) غير إلزامية، لذا لا تحتاج للتحقق
      }
      return true;
    }

    // إضافة تفاعلية لعناصر رفع الملفات
    document.querySelectorAll('.file-input').forEach(input => {
      input.addEventListener('change', function() {
        const container = this.closest('.file-upload-container');
        if (this.files.length > 0) {
          container.style.borderColor = '#4361ee';
          container.style.background = '#e8f4ff';
          
          // إضافة اسم الملف المختار
          let fileName = this.files[0].name;
          let fileInfo = container.querySelector('p');
          fileInfo.innerHTML = `تم اختيار الملف: <strong>${fileName}</strong>`;
        } else {
          container.style.borderColor = '#ddd';
          container.style.background = '#f8f9fa';
          let fileInfo = container.querySelector('p');
          fileInfo.innerHTML = container.querySelector('h4').nextElementSibling.textContent;
        }
      });
    });

    // تهيئة الخطوة الأولى
    showStep(1);
  </script>
</body>
</html>