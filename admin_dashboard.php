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
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supervisor')) {
    logError("محاولة وصول غير مصرح به إلى admin_dashboard.php - user_id: " . ($_SESSION['user_id'] ?? 'غير معروف'));
    header('Location: login.php');
    exit;
}

// جلب إحصائيات للمسؤول
$total_projects_result = $conn->query("SELECT COUNT(*) as total FROM projects");
if (!$total_projects_result) {
    logError("فشل استعلام إجمالي المشاريع: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$total_projects = $total_projects_result->fetch_assoc()['total'];

$pending_projects_result = $conn->query("SELECT COUNT(*) as total FROM projects WHERE status = 'pending'");
if (!$pending_projects_result) {
    logError("فشل استعلام المشاريع قيد الانتظار: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$pending_projects = $pending_projects_result->fetch_assoc()['total'];

$approved_projects_result = $conn->query("SELECT COUNT(*) as total FROM projects WHERE status = 'approved'");
if (!$approved_projects_result) {
    logError("فشل استعلام المشاريع المعتمدة: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$approved_projects = $approved_projects_result->fetch_assoc()['total'];

$total_users_result = $conn->query("SELECT COUNT(*) as total FROM users");
if (!$total_users_result) {
    logError("فشل استعلام إجمالي المستخدمين: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$total_users = $total_users_result->fetch_assoc()['total'];

$pending_users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE active = 0");
if (!$pending_users_result) {
    logError("فشل استعلام عدد الحسابات بانتظار التفعيل: " . $conn->error);
    die("خطأ في قاعدة البيانات. يرجى التواصل مع الدعم الفني.");
}
$pending_users = $pending_users_result->fetch_assoc()['total'];

// جلب المشاريع لعرضها في لوحة التحكم
$projects_to_display = [];
$sql_projects = "
    SELECT p.id, p.title, p.summary, p.year, p.status, u.username AS student_name, s.name AS section_name
    FROM projects p
    JOIN users u ON p.student_id = u.id
    JOIN sections s ON p.section_id = s.id
    ORDER BY p.uploaded_at DESC
    LIMIT 5
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #7b1fa2;
            --background-color: #f4f6f9;
            --widget-bg: #ffffff;
            --text-color: #2c3e50;
            --header-bg: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background-color: var(--header-bg);
            color: #fff;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            position: fixed;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: var(--primary-color);
            transform: translateX(5px);
        }

        .main-content {
            flex-grow: 1;
            margin-right: 260px;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: var(--widget-bg);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        header .welcome-message h3 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.2em;
        }

        header .user-info a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        header .user-info a:hover {
            color: #1565c0;
        }

        .content {
            padding: 30px;
            flex-grow: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-item {
            background-color: var(--widget-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-bottom: 4px solid var(--primary-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-item i {
            font-size: 2.2em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-item h3 {
            font-size: 1.8em;
            margin: 0;
            color: var(--text-color);
        }

        .stat-item p {
            margin: 5px 0 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .widget {
            background-color: var(--widget-bg);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .widget h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .projects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .project-card {
            background-color: var(--widget-bg);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .project-card h4 {
            margin-top: 0;
            color: var(--primary-color);
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

        .status.pending { background-color: var(--warning-color); }
        .status.approved { background-color: var(--success-color); }
        .status.rejected { background-color: var(--danger-color); }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .action-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            min-width: 200px;
            max-width: 250px;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .action-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .action-card span {
            font-size: 1.1em;
            font-weight: 600;
            display: block;
        }

        .button {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .button:hover {
            background-color: #1565c0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-right: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .action-card {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fa-solid fa-shield-halved"></i> لوحة التحكم</h2>
        <ul>
            <li><a href="admin_dashboard.php" class="active"><i class="fa-solid fa-house"></i> الرئيسية</a></li>
            <li><a href="activate_users.php"><i class="fa-solid fa-user-check"></i> تفعيل الحسابات</a></li>
            <li><a href="view_users.php"><i class="fa-solid fa-users"></i> عرض المستخدمين</a></li>
            <li><a href="add_user.php"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم</a></li>
            <li><a href="review_projects.php"><i class="fa-solid fa-file-circle-check"></i> مراجعة المشاريع</a></li>
            <li><a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <div class="welcome-message">
                <h3>أهلاً بك، <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
            </div>
            <div class="user-info">
                <a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
            </div>
        </header>
        <div class="content">
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-project-diagram"></i>
                    <h3><?php echo $total_projects; ?></h3>
                    <p>إجمالي المشاريع</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-hourglass-half"></i>
                    <h3><?php echo $pending_projects; ?></h3>
                    <p>مشاريع قيد الانتظار</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $approved_projects; ?></h3>
                    <p>مشاريع معتمدة</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $total_users; ?></h3>
                    <p>إجمالي المستخدمين</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user-clock"></i>
                    <h3><?php echo $pending_users; ?></h3>
                    <p>حسابات بانتظار التفعيل</p>
                </div>
            </div>

            <div class="widget">
                <h3><i class="fas fa-list-alt"></i> آخر المشاريع المضافة</h3>
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
                                <a href="review_project.php?id=<?php echo $project['id']; ?>" class="button" style="margin-top: 10px; text-align: center;">
                                    <i class="fas fa-eye"></i> عرض وتفاصيل
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>لا توجد مشاريع لعرضها حاليًا.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="widget">
                <h3><i class="fas fa-bolt"></i> الإجراءات السريعة</h3>
                <div class="quick-actions">
                    <a href="activate_users.php" class="action-card">
                        <i class="fa-solid fa-user-check"></i>
                        <span>تفعيل الحسابات</span>
                    </a>
                    <a href="view_users.php" class="action-card">
                        <i class="fa-solid fa-users"></i>
                        <span>عرض المستخدمين</span>
                    </a>
                    <a href="add_user.php" class="action-card">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>إضافة مستخدم</span>
                    </a>
                    <a href="review_projects.php" class="action-card">
                        <i class="fa-solid fa-file-circle-check"></i>
                        <span>مراجعة المشاريع</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>