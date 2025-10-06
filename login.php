<?php
// صفحة تسجيل الدخول للمشرف أو الطالب
session_start();
require_once 'db.php'; // ملف الاتصال بقاعدة البيانات

// تحديد الدور من الرابط
$role = isset($_GET['role']) ? $_GET['role'] : '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = $_POST['password'];
  $role = isset($_POST['role']) ? $_POST['role'] : $role;

  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ? LIMIT 1");
  $stmt->bind_param('ss', $username, $role);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      if ($user['active'] == 1) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin' || $user['role'] === 'supervisor') {
          header('Location: admin_dashboard.php');
        } else {
          header('Location: student_dashboard.php');
        }
        exit;
      } else {
        $error = 'الحساب غير مفعل، يرجى التواصل مع الإدارة.';
      }
    } else {
      $error = 'كلمة المرور غير صحيحة.';
    }
  } else {
    $error = 'اسم المستخدم أو الدور غير صحيح.';
  }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تسجيل الدخول</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Cairo', Arial, sans-serif; background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); margin: 0; padding: 0; }
    .container { max-width: 500px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.12); padding: 40px 30px 30px 30px; position: relative; }
    header { text-align: center; margin-bottom: 20px; }
    header img { width: 70px; height: auto; margin-bottom: 10px; display: inline-block; }
    h1 { color: #2980b9; font-size: 2.2em; font-weight: 700; text-align: center; margin-bottom: 10px; }
    form { margin-top: 10px; }
    label { display: block; margin-top: 18px; color: #34495e; font-weight: 600; }
    input { width: 100%; padding: 12px; margin-top: 7px; border-radius: 6px; border: 1px solid #b2bec3; font-size: 1em; background: #f7f7f7; transition: border-color 0.2s; }
    input:focus { border-color: #2980b9; outline: none; }
    button { background: linear-gradient(90deg, #2980b9 0%, #1abc9c 100%); color: #fff; border: none; padding: 14px 0; border-radius: 6px; margin-top: 25px; cursor: pointer; font-size: 1.1em; font-weight: bold; width: 100%; box-shadow: 0 2px 8px #ccc; transition: background 0.2s; }
    button:hover { background: linear-gradient(90deg, #1abc9c 0%, #2980b9 100%); }
    .error { color: #e74c3c; text-align: center; margin-top: 10px; }
    .login-icon { display: block; text-align: center; font-size: 3em; color: #1abc9c; margin-bottom: 15px; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>تسجيل الدخول</h1>
    </header>
    <span class="login-icon"><i class="fa-solid fa-user-lock"></i></span>
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
      <label for="username"><i class="fa-solid fa-user"></i> اسم المستخدم</label>
      <input type="text" id="username" name="username" required>
      <label for="password"><i class="fa-solid fa-key"></i> كلمة المرور</label>
      <input type="password" id="password" name="password" required>
      <!-- الدور يظهر تلقائيًا إذا أتيت من الرابط، أو يمكن اختياره -->
      <?php if ($role): ?>
        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
        <div style="margin:10px 0; color:#2980b9; font-weight:bold; text-align:center;">الدور: <?php echo ($role=='admin'?'مشرف':'طالب'); ?></div>
      <?php else: ?>
        <label for="role"><i class="fa-solid fa-user-tag"></i> الدور</label>
        <select name="role" id="role" required>
          <option value="">اختر الدور</option>
          <option value="admin">مشرف</option>
          <option value="student">طالب</option>
        </select>
      <?php endif; ?>
      <button type="submit"><i class="fa-solid fa-sign-in-alt"></i> دخول</button>
      <div style="text-align:center; margin-top:18px;">
        <a href="register.html" style="color:#2980b9; text-decoration:underline; font-weight:bold;">إنشاء حساب جديد</a>
      </div>
    </form>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
