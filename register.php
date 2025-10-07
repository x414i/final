<?php
require_once 'db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'student'; // تعيين الدور كـ "طالب" بشكل افتراضي

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, active) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param('ssss', $username, $email, $hashed, $role);
        if ($stmt->execute()) {
            $success = 'تم إنشاء الحساب بنجاح! سيتم تفعيل الحساب من قبل الإدارة.';
        } else {
            $error = 'حدث خطأ أثناء إنشاء الحساب.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب جديد</title>
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
        left: 15px; /* Adjusted for RTL */
        top: 50%;
        transform: translateY(-50%);
        color: var(--border-color);
        transition: color 0.3s;
    }
    input {
        width: 100%;
        padding: 15px 45px 15px 20px; /* Padding adjusted for icon */
        border-radius: 10px;
        border: 1px solid var(--border-color);
        font-size: 1em;
        background: var(--input-bg);
        transition: all 0.3s;
        box-sizing: border-box;
    }
    input::placeholder {
        color: #95a5a6;
    }
    input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }
    input:focus ~ i { /* Use general sibling combinator */
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
        position: relative;
        overflow: hidden;
    }
    button:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .error, .success {
        text-align: center;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .error {
        color: #c0392b;
        background: #f2dede;
    }
    .success {
        color: #27ae60;
        background: #d4edda;
    }
    footer {
        text-align: center;
        margin-top: 30px;
        color: #7f8c8d;
        font-size: 0.9em;
    }
    .login-link {
        text-align: center;
        margin-top: 20px;
    }
    .login-link a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s;
    }
    .login-link a:hover {
        text-decoration: underline;
        color: var(--secondary-color);
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>إنشاء حساب جديد</h1>
    </header>
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
      <div class="login-link" style="margin-top: 20px;">
        <a href="login.php">العودة إلى صفحة تسجيل الدخول</a>
      </div>
    <?php else: ?>
    <form method="POST">
      <div class="input-group">
        <input type="text" id="username" name="username" placeholder="اسم المستخدم" required>
        <i class="fa-solid fa-user"></i>
      </div>
      <div class="input-group">
        <input type="email" id="email" name="email" placeholder="البريد الإلكتروني" required>
        <i class="fa-solid fa-envelope"></i>
      </div>
      <div class="input-group">
        <input type="password" id="password" name="password" placeholder="كلمة المرور" required>
        <i class="fa-solid fa-key"></i>
      </div>
      <input type="hidden" name="role" value="student">
      <button type="submit">إنشاء الحساب</button>
    </form>
    <div class="login-link">
      لديك حساب بالفعل؟ <a href="login.php">سجل الدخول</a>
    </div>
    <?php endif; ?>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
