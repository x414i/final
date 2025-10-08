<?php
session_start();
require_once 'db.php';

// إعداد التسجيل
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
    $errorMessage = "[$timestamp] ERROR: $message" . PHP_EOL;
    @file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
}

// التحقق من الجلسة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    logError("محاولة وصول غير مصرح به إلى supervisor_dashboard.php - user_id: " . ($_SESSION['user_id'] ?? 'غير معروف'));
    header('Location: login.php');
    exit;
}

// جلب إحصائيات للمشرف
$reviewed_count_result = $conn->query("SELECT COUNT(*) as total FROM projects WHERE status = 'approved'");
if (!$reviewed_count_result) {
    logError("فشل استعلام عدد المشاريع المراجعة: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$reviewed_count = $reviewed_count_result->fetch_assoc()['total'];

$pending_count_result = $conn->query("SELECT COUNT(*) as total FROM projects WHERE status = 'pending'");
if (!$pending_count_result) {
    logError("فشل استعلام عدد المشاريع قيد الانتظار: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$pending_count = $pending_count_result->fetch_assoc()['total'];

$pending_users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE active = 0");
if (!$pending_users_result) {
    logError("فشل استعلام عدد الحسابات بانتظار التفعيل: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$pending_users = $pending_users_result->fetch_assoc()['total'];

// جلب المشاريع
$projects_to_display = [];
$sql_projects = "
    SELECT p.id, p.title, p.summary, p.year, p.status, u.username AS student_name, s.name AS section_name
    FROM projects p
    JOIN users u ON p.student_id = u.id
    JOIN sections s ON p.section_id = s.id
    WHERE p.status = 'pending' OR p.status = 'approved'
    ORDER BY p.uploaded_at DESC
";
$result_projects = $conn->query($sql_projects);
if (!$result_projects) {
    logError("فشل استعلام جلب المشاريع: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
while ($project = $result_projects->fetch_assoc()) {
    $projects_to_display[] = $project;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المشرف</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .sidebar {
            width: 250px;
            background-color: #34495e;
            color: #fff;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            position: fixed;
        }
        .main-content { margin-right: 250px; padding: 20px; flex-grow: 1; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: background-color 0.3s;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: #2980b9; }
        .projects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .project-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .project-card h4 {
            margin-top: 0;
            color: #2980b9;
            font-size: 1.2em;
        }
        .project-card p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .project-card .status {
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            font-size: 0.8em;
            width: fit-content;
            margin-top: auto;
        }
        .status.pending { background-color: #f39c12; }
        .status.approved { background-color: #27ae60; }
        .status.rejected { background-color: #e74c3c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-user-shield"></i> لوحة التحكم</h2>
        <ul>
            <li><a href="supervisor_dashboard.php" class="active"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="review_projects.php"><i class="fas fa-file-circle-check"></i> مراجعة المشاريع</a></li>
            <li><a href="activate_users.php"><i class="fas fa-user-check"></i> تفعيل الحسابات</a></li>
            <li><a href="login.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="content">
            <div class="welcome-section content-card">
                <h2>مرحباً بعودتك، <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p>هذه هي لوحة التحكم الخاصة بك لإدارة ومراجعة المشاريع وتفعيل المستخدمين.</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-check-double"></i>
                    <h3><?php echo $reviewed_count; ?></h3>
                    <p>مشاريع قمت بمراجعتها</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-hourglass-half"></i>
                    <h3><?php echo $pending_count; ?></h3>
                    <p>مشاريع قيد الانتظار</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user-clock"></i>
                    <h3><?php echo $pending_users; ?></h3>
                    <p>حسابات بانتظار التفعيل</p>
                </div>
            </div>
            <div class="widget">
                <h3><i class="fas fa-project-diagram"></i> المشاريع الخاصة بك أو قيد المراجعة</h3>
                <div class="projects-list">
                    <?php if (!empty($projects_to_display)): ?>
                        <?php foreach ($projects_to_display as $project): ?>
                            <div class="project-card">
                                <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                                <p><strong>الطالب:</strong> <?php echo htmlspecialchars($project['student_name']); ?></p>
                                <p><strong>القسم:</strong> <?php echo htmlspecialchars($project['section_name']); ?></p>
                                <p><strong>السنة:</strong> <?php echo htmlspecialchars($project['year']); ?></p>
                                <span class="status <?php echo $project['status']; ?>">
                                    <?php
                                        if ($project['status'] === 'pending') echo 'قيد الانتظار';
                                        elseif ($project['status'] === 'approved') echo 'تمت الموافقة';
                                        elseif ($project['status'] === 'rejected') echo 'تم الرفض';
                                    ?>
                                </span>
                                <a href="review_project.php?id=<?php echo $project['id']; ?>" class="button primary" style="margin-top: 10px; text-align: center;">
                                    <i class="fas fa-eye"></i> عرض وتفاصيل
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>لا توجد مشاريع لعرضها حاليًا.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```