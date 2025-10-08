<?php
session_start();
include 'db.php';

ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('max_execution_time', '300');
ini_set('file_uploads', 'On');

function setupLogging() {
    $logDir = 'logs/';
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            $logDir = sys_get_temp_dir() . '/project_logs/';
            if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
                $logDir = './';
            }
        }
    }
    if (!is_writable($logDir)) {
        @chmod($logDir, 0755);
    }
    return $logDir;
}

function logError($message) {
    $logDir = setupLogging();
    $logFile = $logDir . 'error_log_' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    $tmpDir = sys_get_temp_dir();
    $tmpDirStatus = is_writable($tmpDir) ? 'قابل للكتابة' : 'غير قابل للكتابة';
    $phpSettings = "upload_max_filesize: " . ini_get('upload_max_filesize') . ", post_max_size: " . ini_get('post_max_size') . ", file_uploads: " . ini_get('file_uploads');
    $errorMessage = "[$timestamp] ERROR: $message | المجلد المؤقت: $tmpDir ($tmpDirStatus) | إعدادات PHP: $phpSettings" . PHP_EOL;
    $result = @file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
    if ($result === false) {
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

function setupUploadDirs() {
    $dirs = ['uploads/projects/pdfs/', 'uploads/projects/code/'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                logError("فشل في إنشاء مجلد الرفع: $dir");
                return false;
            }
        }
        if (!is_writable($dir)) {
            if (!chmod($dir, 0755)) {
                logError("المجلد غير قابل للكتابة: $dir");
                return false;
            }
        }
    }
    return true;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    logError("محاولة وصول غير مصرح به إلى صفحة رفع المشروع - user_id: " . ($_SESSION['user_id'] ?? 'غير معروف'));
    header("Location: login.php");
    exit();
}

if (!setupUploadDirs()) {
    $error = "هناك مشكلة في إعداد نظام الملفات. يرجى الاتصال بالدعم الفني.";
    logError("فشل في إعداد مجلدات الرفع");
}

$error = '';
$success = '';
$sections = null;

$sql_sections = "SELECT id, name FROM sections ORDER BY name ASC";
$result_sections = $conn->query($sql_sections);

if (!$result_sections) {
    $error = "خطأ في جلب الأقسام: " . $conn->error;
    logError("فشل في جلب الأقسام: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $year = intval($_POST['year']);
    $section_id = intval($_POST['section_id']);
    $student_id = $_SESSION['user_id'];

    logInfo("بدء محاولة رفع مشروع - الطالب: $student_id, العنوان: $title");

    if (empty($title) || empty($summary) || empty($year) || empty($section_id)) {
        $error = "جميع الحقول الإلزامية مطلوبة";
        logError("بيانات ناقصة في النموذج - الطالب: $student_id");
    } else {
        $pdf_file_name = '';
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            logInfo("معالجة ملف PDF - خطأ: " . $_FILES['pdf_file']['error'] . " - الطالب: $student_id");
            
            if ($_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $pdf_target_dir = "uploads/projects/pdfs/$year/$section_id/$student_id/";
                if (!is_dir($pdf_target_dir)) {
                    if (!mkdir($pdf_target_dir, 0755, true)) {
                        $error = "فشل في إنشاء مجلد الوجهة لملف PDF. يرجى الاتصال بالدعم الفني.";
                        logError("فشل في إنشاء مجلد الوجهة: $pdf_target_dir - الطالب: $student_id");
                    }
                }
                
                $tmp_dir = sys_get_temp_dir();
                if (!is_writable($tmp_dir)) {
                    $error = "المجلد المؤقت غير قابل للكتابة. يرجى الاتصال بالدعم الفني.";
                    logError("المجلد المؤقت غير قابل للكتابة: $tmp_dir - الطالب: $student_id");
                } elseif (!file_exists($_FILES['pdf_file']['tmp_name'])) {
                    $error = "الملف المؤقت غير موجود. يرجى المحاولة مرة أخرى.";
                    logError("الملف المؤقت غير موجود: {$_FILES['pdf_file']['tmp_name']} - الطالب: $student_id");
                } else {
                    $allowed_pdf_types = ['application/pdf', 'application/octet-stream'];
                    $file_type = $_FILES['pdf_file']['type'];
                    $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_type, $allowed_pdf_types) && $file_extension !== 'pdf') {
                        $error = "نوع الملف غير مسموح به. يرجى رفع ملف PDF فقط.";
                        logError("نوع ملف PDF غير مسموح به: $file_type - الامتداد: $file_extension - الطالب: $student_id");
                    } else {
                        $max_file_size = 10 * 1024 * 1024;
                        if ($_FILES['pdf_file']['size'] > $max_file_size) {
                            $error = "حجم ملف PDF كبير جداً. الحد الأقصى هو 10MB.";
                            logError("حجم ملف PDF كبير: {$_FILES['pdf_file']['size']} - الطالب: $student_id");
                        } else {
                            $pdf_file_name_only = uniqid('pdf_') . '_' . basename($_FILES['pdf_file']['name']);
                            $pdf_file_path = $pdf_target_dir . $pdf_file_name_only;
                            
                            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdf_file_path)) {
                                $error = "فشل في رفع ملف PDF. تحقق من صلاحيات المجلد أو إعدادات الخادم.";
                                $last_error = error_get_last();
                                logError("فشل في نقل ملف PDF - الطالب: $student_id - المسار: $pdf_file_path - خطأ: " . ($last_error ? $last_error['message'] : 'غير معروف'));
                            } else {
                                $pdf_file_name = $pdf_file_path; // حفظ المسار الكامل
                                logInfo("تم رفع ملف PDF بنجاح: $pdf_file_name - الطالب: $student_id - الحجم: {$_FILES['pdf_file']['size']}");
                            }
                        }
                    }
                }
            } else {
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
                $code_target_dir = "uploads/projects/code/$year/$section_id/$student_id/";
                if (!is_dir($code_target_dir)) {
                    if (!mkdir($code_target_dir, 0755, true)) {
                        $error = "فشل في إنشاء مجلد الوجهة لملف الكود. يرجى الاتصال بالدعم الفني.";
                        logError("فشل في إنشاء مجلد الوجهة: $code_target_dir - الطالب: $student_id");
                    }
                }
                
                $tmp_dir = sys_get_temp_dir();
                if (!is_writable($tmp_dir)) {
                    $error = "المجلد المؤقت غير قابل للكتابة. يرجى الاتصال بالدعم الفني.";
                    logError("المجلد المؤقت غير قابل للكتابة: $tmp_dir - الطالب: $student_id");
                } elseif (!file_exists($_FILES['code_file']['tmp_name'])) {
                    $error = "الملف المؤقت غير موجود. يرجى المحاولة مرة أخرى.";
                    logError("الملف المؤقت غير موجود: {$_FILES['code_file']['tmp_name']} - الطالب: $student_id");
                } else {
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
                        $max_file_size = 20 * 1024 * 1024;
                        if ($_FILES['code_file']['size'] > $max_file_size) {
                            $error = "حجم ملف الكود كبير جداً. الحد الأقصى هو 20MB.";
                            logError("حجم ملف الكود كبير: {$_FILES['code_file']['size']} - الطالب: $student_id");
                        } else {
                            $code_file_name_only = uniqid('code_') . '_' . basename($_FILES['code_file']['name']);
                            $code_file_path = $code_target_dir . $code_file_name_only;
                            
                            if (!move_uploaded_file($_FILES['code_file']['tmp_name'], $code_file_path)) {
                                $error = "فشل في رفع ملف الكود. تحقق من صلاحيات المجلد أو إعدادات الخادم.";
                                $last_error = error_get_last();
                                logError("فشل في نقل ملف الكود - الطالب: $student_id - المسار: $code_file_path - خطأ: " . ($last_error ? $last_error['message'] : 'غير معروف'));
                            } else {
                                $code_file_name = $code_file_path; // حفظ المسار الكامل
                                logInfo("تم رفع ملف الكود بنجاح: $code_file_name - الطالب: $student_id - الحجم: {$_FILES['code_file']['size']}");
                            }
                        }
                    }
                }
            } else {
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
            $stmt = $conn->prepare("INSERT INTO projects (student_id, section_id, title, summary, year, pdf_file, code_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            if ($stmt) {
                $stmt->bind_param("iisssss", $student_id, $section_id, $title, $summary, $year, $pdf_file_name, $code_file_name);
                if ($stmt->execute()) {
                    $success = "تم رفع المشروع بنجاح! سيتم مراجعته قريباً.";
                    logInfo("تم رفع المشروع بنجاح في قاعدة البيانات - الطالب: $student_id - المشروع: $title - PDF: $pdf_file_name - Code: $code_file_name");
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

function checkSystemStatus() {
    $status = [
        'logs_dir' => is_writable('logs/') || is_writable(sys_get_temp_dir()),
        'uploads_dir' => is_writable('uploads/projects/') && is_writable('uploads/projects/pdfs/') && is_writable('uploads/projects/code/'),
        'max_upload_size' => min(ini_get('upload_max_filesize'), ini_get('post_max_size')),
        'tmp_dir' => is_writable(sys_get_temp_dir()) ? 'قابل للكتابة' : 'غير قابل للكتابة'
    ];
    return $status;
}

$system_status = checkSystemStatus();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع مشروع جديد</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        header {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-container img {
            width: 60px;
            height: 60px;
        }
        .header-title h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header-title p {
            margin: 5px 0 0;
            color: #666;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        nav {
            background: #4361ee;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        nav a:hover, nav a.active {
            background: #3a54d1;
        }
        nav a i {
            margin-left: 5px;
        }
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .system-status {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .system-status h4 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #333;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 5px 0;
            font-size: 14px;
        }
        .status-item.good i {
            color: #28a745;
        }
        .status-item.bad i {
            color: #dc3545;
        }
        .form-header {
            margin-bottom: 20px;
            text-align: center;
        }
        .form-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .form-header p {
            margin: 5px 0 0;
            color: #666;
        }
        .steps-indicator {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step-icon {
            width: 40px;
            height: 40px;
            background: #ddd;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-size: 18px;
        }
        .step.active .step-icon {
            background: #4361ee;
        }
        .step.completed .step-icon {
            background: #28a745;
        }
        .step-text {
            font-size: 14px;
            color: #666;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group label i {
            margin-left: 5px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #4361ee;
            outline: none;
        }
        .form-group textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .form-full-width {
            grid-column: 1 / -1;
        }
        .file-upload-container {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .file-upload-container i {
            font-size: 30px;
            color: #4361ee;
            margin-bottom: 10px;
        }
        .file-upload-container h4 {
            margin: 10px 0;
            font-size: 18px;
            color: #333;
        }
        .file-upload-container p {
            margin: 10px 0;
            color: #666;
            font-size: 14px;
        }
        .file-requirements {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        .file-input {
            display: block;
            margin: 10px auto;
        }
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .button.primary {
            background: #4361ee;
            color: #fff;
        }
        .button.primary:hover {
            background: #3a54d1;
        }
        .button.secondary {
            background: #6c757d;
            color: #fff;
        }
        .button.secondary:hover {
            background: #5a6268;
        }
        .button.secondary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .steps-indicator {
                flex-direction: column;
                gap: 10px;
            }
            .step {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .step-icon {
                margin: 0;
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
                <div class="status-item <?php echo $system_status['tmp_dir'] === 'قابل للكتابة' ? 'good' : 'bad'; ?>">
                    <i class="fas fa-<?php echo $system_status['tmp_dir'] === 'قابل للكتابة' ? 'check' : 'times'; ?>"></i>
                    المجلد المؤقت: <?php echo $system_status['tmp_dir']; ?>
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
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById('section' + step).classList.add('active');
            document.querySelectorAll('.step').forEach((stepElement, index) => {
                stepElement.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    stepElement.classList.add('completed');
                } else if (index + 1 === step) {
                    stepElement.classList.add('active');
                }
            });
            currentStep = step;
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
            }
            return true;
        }

        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function() {
                const container = this.closest('.file-upload-container');
                if (this.files.length > 0) {
                    container.style.borderColor = '#4361ee';
                    container.style.background = '#e8f4ff';
                    let fileName = this.files[0].name;
                    let fileInfo = container.querySelector('p');
                    fileInfo.innerHTML = `تم اختيار الملف: <strong>${fileName}</strong>`;
                } else {
                    container.style.borderColor = '#ddd';
                    container.style.background = '#f8f9fa';
                    let fileInfo = container.querySelector('p');
                    if (this.name === 'pdf_file') {
                        fileInfo.innerHTML = 'يمكنك رفع ملف PDF يحتوي على توثيق المشروع';
                    } else {
                        fileInfo.innerHTML = 'يمكنك رفع ملف مضغوط يحتوي على كود المشروع';
                    }
                }
            });
        });

        showStep(1);
    </script>
</body>
</html>
