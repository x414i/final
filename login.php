<?php
session_start();
require_once 'db.php';

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

function logInfo($message) {
    $logDir = setupLogging();
    $logFile = $logDir . 'info_log_' . date('Y-m-d') . '.txt';
    $timestamp = date('Y-m-d H:i:s');
    $infoMessage = "[$timestamp] INFO: $message" . PHP_EOL;
    @file_put_contents($logFile, $infoMessage, FILE_APPEND | LOCK_EX);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['active'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                logInfo("تسجيل دخول ناجح - المستخدم: $username, الدور: {$user['role']}");
                
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } elseif ($user['role'] === 'supervisor') {
                    header('Location: supervisor_dashboard.php');
                } else {
                    header('Location: student_dashboard.php');
                }
                exit;
            } else {
                $error = 'الحساب غير مفعل، يرجى التواصل مع الإدارة.';
                logError("فشل تسجيل الدخول - الحساب غير مفعل - المستخدم: $username");
            }
        } else {
            $error = 'كلمة المرور غير صحيحة.';
            logError("فشل تسجيل الدخول - كلمة المرور غير صحيحة - المستخدم: $username");
        }
    } else {
        $error = 'اسم المستخدم غير صحيح.';
        logError("فشل تسجيل الدخول - اسم المستخدم غير موجود: $username");
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --background-color: #f4f7f6;
            --container-bg: #ffffff;
            --text-color: #333;
            --input-bg: #ecf0f1;
            --border-color: #bdc3c7;
        }
        body {
            font-family: 'Cairo', Arial, sans-serif;
            background-color: var(--background-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            background: var(--container-bg);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        header {
            text-align: center;
            margin-bottom: 25px;
        }
        header img {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }
        h1 {
            color: var(--primary-color);
            font-size: 2em;
            font-weight: 700;
            margin: 0;
        }
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--border-color);
            transition: color 0.3s;
        }
        input, select {
            width: 100%;
            padding: 15px 45px 15px 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 1em;
            background: var(--input-bg);
            transition: all 0.3s;
            box-sizing: border-box;
        }
        select {
            padding: 15px 20px;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: left 1.2em top 50%;
            background-size: .65em auto;
        }
        input::placeholder {
            color: #95a5a6;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        input:focus ~ i {
            color: var(--primary-color);
        }
        button {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            border: none;
            padding: 15px 0;
            border-radius: 10px;
            margin-top: 15px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .error {
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #c0392b;
            background: #f2dede;
        }
        footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .register-link a:hover {
            text-decoration: underline;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
            <h1>تسجيل الدخول</h1>
        </header>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder="اسم المستخدم" required>
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="كلمة المرور" required>
                <i class="fa-solid fa-key"></i>
            </div>
            <button type="submit">دخول</button>
            <div class="register-link">
                ليس لديك حساب؟ <a href="register.php">أنشئ حسابًا جديدًا</a>
            </div>
        </form>
        <footer>
            جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
        </footer>
    </div>
</body>
</html>
