<?php
session_start();
include 'db.php';

// إعداد نظام تسجيل الأخطاء - مع تحسينات
function setupLogging() {
    $logDir = 'logs/';
    
    // التحقق من وجود المجلد وإنشاؤه إذا لم يكن موجوداً
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            // إذا فشل إنشاء المجلد، حاول استخدام مجلد مؤقت
            $logDir = sys_get_temp_dir() . '/project_logs/';
            if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
                // إذا فشل أيضاً، استخدم المجلد الحالي
                $logDir = './';
            }
        }
    }
    
    // التحقق من إمكانية الكتابة في المجلد
    if (!is_writable($logDir)) {
        // حاول تغيير الصلاحيات
        @chmod($logDir, 0755);
    }
    
    return $logDir;
}

function logError($message) {
    $logDir = setupLogging();
    
    $logFile = $logDir . 'error_log_' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "[$timestamp] ERROR: $message" . PHP_EOL;
    
    // محاولة الكتابة مع التعامل مع الأخطاء
    $result = @file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
    
    if ($result === false) {
        // إذا فشل الكتابة، عرض رسالة خطأ للمطور
        error_log("فشل في كتابة سجل الأخطاء: " . $logFile);
    }
}

function logInfo($message) {
    $logDir = setupLogging();
    
    $logFile = $logDir . 'info_log_' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    $infoMessage = "[$timestamp] INFO: $message" . PHP_EOL;
    
    @file_put_contents($logFile, $infoMessage, FILE_APPEND | LOCK_EX);
}

// إنشاء مجلدات الرفع إذا لم تكن موجودة
function setupUploadDirs() {
    $dirs = ['uploads/pdfs/', 'uploads/code/'];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                logError("فشل في إنشاء مجلد الرفع: $dir");
                return false;
            }
        }
        
        // التحقق من إمكانية الكتابة
        if (!is_writable($dir)) {
            if (!chmod($dir, 0755)) {
                logError("المجلد غير قابل للكتابة: $dir");
                return false;
            }
        }
    }
    
    return true;
}

// Redirect if user is not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    logError("محاولة وصول غير مصرح به إلى صفحة رفع المشروع - user_id: " . ($_SESSION['user_id'] ?? 'غير معروف'));
    header("Location: login.php");
    exit();
}

// إعداد مجلدات الرفع
if (!setupUploadDirs()) {
    $error = "هناك مشكلة في إعداد نظام الملفات. يرجى الاتصال بالدعم الفني.";
    logError("فشل في إعداد مجلدات الرفع");
}

$error = '';
$success = '';
$sections = null;

// Fetch sections from the database
$sql_sections = "SELECT id, name FROM sections ORDER BY name ASC";
$result_sections = $conn->query($sql_sections);

if (!$result_sections) {
    $error = "خطأ في جلب الأقسام: " . $conn->error;
    logError("فشل في جلب الأقسام: " . $conn->error);
}

// Handle project upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $year = intval($_POST['year']);
    $section_id = intval($_POST['section_id']);
    $student_id = $_SESSION['user_id'];

    logInfo("بدء محاولة رفع مشروع - الطالب: $student_id, العنوان: $title");

    // التحقق من صحة البيانات الأساسية
    if (empty($title) || empty($summary) || empty($year) || empty($section_id)) {
        $error = "جميع الحقول الإلزامية مطلوبة";
        logError("بيانات ناقصة في النموذج - الطالب: $student_id");
    } else {
        // File upload handling
        $pdf_file_name = '';
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            logInfo("معالجة ملف PDF - خطأ: " . $_FILES['pdf_file']['error'] . " - الطالب: $student_id");
            
            if ($_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $pdf_target_dir = "uploads/pdfs/";
                
                // التحقق من نوع الملف
                $allowed_pdf_types = ['application/pdf', 'application/octet-stream'];
                $file_type = $_FILES['pdf_file']['type'];
                $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_type, $allowed_pdf_types) && $file_extension !== 'pdf') {
                    $error = "نوع الملف غير مسموح به. يرجى رفع ملف PDF فقط.";
                    logError("نوع ملف PDF غير مسموح به: $file_type - الامتداد: $file_extension - الطالب: $student_id");
                } else {
                    // التحقق من حجم الملف (10MB كحد أقصى)
                    $max_file_size = 10 * 1024 * 1024; // 10MB
                    if ($_FILES['pdf_file']['size'] > $max_file_size) {
                        $error = "حجم ملف PDF كبير جداً. الحد الأقصى هو 10MB.";
                        logError("حجم ملف PDF كبير: {$_FILES['pdf_file']['size']} - الطالب: $student_id");
                    } else {
                        // استخدم اسم الملف فقط بدلاً من المسار الكامل
                        $pdf_file_name = uniqid('pdf_') . '_' . basename($_FILES['pdf_file']['name']);
                        $pdf_file_path = $pdf_target_dir . $pdf_file_name;
                        
                        if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdf_file_path)) {
                            $error = "فشل في رفع ملف PDF.";
                            $last_error = error_get_last();
                            logError("فشل في نقل ملف PDF - الطالب: $student_id - المسار: $pdf_file_path - خطأ: " . ($last_error ? $last_error['message'] : 'غير معروف'));
                        } else {
                            logInfo("تم رفع ملف PDF بنجاح: $pdf_file_name - الطالب: $student_id - الحجم: {$_FILES['pdf_file']['size']}");
                        }
                    }
                }
            } else {
                // معالجة أخطاء الرفع
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح به في الخادم',
                    UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح به في النموذج',
                    UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
                    UPLOAD_ERR_NO_TMP_DIR => 'المجلد المؤقت غير موجود',
                    UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
                    UPLOAD_ERR_EXTENSION => 'رفع الملف متوقف بسبب امتداد غير مسموح'
                ];
                
                $error_code = $_FILES['pdf_file']['error'];
                $error_msg = $upload_errors[$error_code] ?? 'خطأ غير معروف في رفع الملف';
                $error = "خطأ في رفع ملف PDF: $error_msg";
                logError("خطأ في رفع ملف PDF - الرمز: $error_code - الرسالة: $error_msg - الطالب: $student_id");
            }
        }

        $code_file_name = '';
        if (empty($error) && isset($_FILES['code_file']) && $_FILES['code_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            logInfo("معالجة ملف الكود - خطأ: " . $_FILES['code_file']['error'] . " - الطالب: $student_id");
            
            if ($_FILES['code_file']['error'] === UPLOAD_ERR_OK) {
                $code_target_dir = "uploads/code/";
                
                // التحقق من نوع الملف
                $allowed_code_types = [
                    'application/zip', 
                    'application/x-zip-compressed', 
                    'application/x-rar-compressed', 
                    'application/x-7z-compressed',
                    'application/octet-stream'
                ];
                $allowed_extensions = ['zip', 'rar', '7z'];
                
                $file_type = $_FILES['code_file']['type'];
                $file_extension = strtolower(pathinfo($_FILES['code_file']['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_type, $allowed_code_types) && !in_array($file_extension, $allowed_extensions)) {
                    $error = "نوع الملف غير مسموح به. يرجى رفع ملف مضغوط (ZIP, RAR, 7Z) فقط.";
                    logError("نوع ملف الكود غير مسموح به: $file_type - الامتداد: $file_extension - الطالب: $student_id");
                } else {
                    // التحقق من حجم الملف (20MB كحد أقصى)
                    $max_file_size = 20 * 1024 * 1024; // 20MB
                    if ($_FILES['code_file']['size'] > $max_file_size) {
                        $error = "حجم ملف الكود كبير جداً. الحد الأقصى هو 20MB.";
                        logError("حجم ملف الكود كبير: {$_FILES['code_file']['size']} - الطالب: $student_id");
                    } else {
                        // استخدم اسم الملف فقط بدلاً من المسار الكامل
                        $code_file_name = uniqid('code_') . '_' . basename($_FILES['code_file']['name']);
                        $code_file_path = $code_target_dir . $code_file_name;
                        
                        if (!move_uploaded_file($_FILES['code_file']['tmp_name'], $code_file_path)) {
                            $error = "فشل في رفع ملف الكود.";
                            $last_error = error_get_last();
                            logError("فشل في نقل ملف الكود - الطالب: $student_id - المسار: $code_file_path - خطأ: " . ($last_error ? $last_error['message'] : 'غير معروف'));
                        } else {
                            logInfo("تم رفع ملف الكود بنجاح: $code_file_name - الطالب: $student_id - الحجم: {$_FILES['code_file']['size']}");
                        }
                    }
                }
            } else {
                // معالجة أخطاء الرفع
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح به في الخادم',
                    UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح به في النموذج',
                    UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
                    UPLOAD_ERR_NO_TMP_DIR => 'المجلد المؤقت غير موجود',
                    UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
                    UPLOAD_ERR_EXTENSION => 'رفع الملف متوقف بسبب امتداد غير مسموح'
                ];
                
                $error_code = $_FILES['code_file']['error'];
                $error_msg = $upload_errors[$error_code] ?? 'خطأ غير معروف في رفع الملف';
                $error = "خطأ في رفع ملف الكود: $error_msg";
                logError("خطأ في رفع ملف الكود - الرمز: $error_code - الرسالة: $error_msg - الطالب: $student_id");
            }
        }

        if (empty($error)) {
            // استخدم أسماء الملفات فقط في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO projects (student_id, section_id, title, summary, year, pdf_file, code_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            if ($stmt) {
                $stmt->bind_param("iisssss", $student_id, $section_id, $title, $summary, $year, $pdf_file_name, $code_file_name);

                if ($stmt->execute()) {
                    $success = "تم رفع المشروع بنجاح! سيتم مراجعته قريباً.";
                    logInfo("تم رفع المشروع بنجاح في قاعدة البيانات - الطالب: $student_id - المشروع: $title - PDF: $pdf_file_name - Code: $code_file_name");
                    // إعادة تعيين المتغيرات لعرض النموذج فارغ
                    $title = $summary = $year = $section_id = '';
                } else {
                    $error = "خطأ في قاعدة البيانات: " . $stmt->error;
                    logError("خطأ في تنفيذ استعلام قاعدة البيانات: " . $stmt->error . " - الطالب: $student_id");
                }
                $stmt->close();
            } else {
                $error = "خطأ في إعداد استعلام قاعدة البيانات";
                logError("فشل في إعداد استعلام قاعدة البيانات: " . $conn->error . " - الطالب: $student_id");
            }
        }
    }
}

// دالة لفحص حالة النظام
function checkSystemStatus() {
    $status = [
        'logs_dir' => is_writable('logs/') || is_writable(sys_get_temp_dir()),
        'uploads_dir' => is_writable('uploads/') && is_writable('uploads/pdfs/') && is_writable('uploads/code/'),
        'max_upload_size' => min(ini_get('upload_max_filesize'), ini_get('post_max_size'))
    ];
    
    return $status;
}

// فحص حالة النظام
$system_status = checkSystemStatus();
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

    .file-requirements {
      font-size: 0.8rem;
      color: var(--gray);
      margin-top: 5px;
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
      <!-- إضافة حالة النظام للمساعدة في التشخيص -->
      <div class="system-status">
        <h4><i class="fas fa-info-circle"></i> حالة النظام:</h4>
        <div class="status-item <?php echo $system_status['logs_dir'] ? 'good' : 'bad'; ?>">
          <i class="fas fa-<?php echo $system_status['logs_dir'] ? 'check' : 'times'; ?>"></i>
          مجلد السجلات: <?php echo $system_status['logs_dir'] ? 'جاهز' : 'غير قابل للكتابة'; ?>
        </div>
        <div class="status-item <?php echo $system_status['uploads_dir'] ? 'good' : 'bad'; ?>">
          <i class="fas fa-<?php echo $system_status['uploads_dir'] ? 'check' : 'times'; ?>"></i>
          مجلد الرفع: <?php echo $system_status['uploads_dir'] ? 'جاهز' : 'غير قابل للكتابة'; ?>
        </div>
        <div class="status-item good">
          <i class="fas fa-check"></i>
          الحد الأقصى لحجم الملف: <?php echo $system_status['max_upload_size']; ?>
        </div>
      </div>

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
                    // إعادة تعيين المؤشر لبداية النتائج
                    $result_sections->data_seek(0);
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
                <div class="file-requirements">الحد الأقصى للحجم: 10MB</div>
                <input type="file" name="pdf_file" id="pdf_file" class="file-input" accept=".pdf,application/pdf">
              </div>
            </div>

            <div class="form-group form-full-width">
              <div class="file-upload-container">
                <i class="fas fa-file-code"></i>
                <h4>رفع كود المشروع (ZIP)</h4>
                <p>يمكنك رفع ملف مضغوط يحتوي على كود المشروع</p>
                <div class="file-requirements">الحد الأقصى للحجم: 20MB - الأنواع المسموحة: ZIP, RAR, 7Z</div>
                <input type="file" name="code_file" id="code_file" class="file-input" accept=".zip,.rar,.7z,application/zip,application/x-zip-compressed,application/x-rar-compressed,application/x-7z-compressed">
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
          // إعادة النص الأصلي
          if (this.name === 'pdf_file') {
            fileInfo.innerHTML = 'يمكنك رفع ملف PDF يحتوي على توثيق المشروع';
          } else {
            fileInfo.innerHTML = 'يمكنك رفع ملف مضغوط يحتوي على كود المشروع';
          }
        }
      });
    });

    // تهيئة الخطوة الأولى
    showStep(1);
  </script>
</body>
</html>