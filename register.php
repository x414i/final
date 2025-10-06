<?php
// صفحة تسجيل طالب جديد
require_once 'db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // تحقق من وجود المستخدم مسبقاً
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
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>إنشاء حساب جديد</title>
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
    input, select { width: 100%; padding: 12px; margin-top: 7px; border-radius: 6px; border: 1px solid #b2bec3; font-size: 1em; background: #f7f7f7; transition: border-color 0.2s; }
    input:focus, select:focus { border-color: #2980b9; outline: none; }
    button { background: linear-gradient(90deg, #2980b9 0%, #1abc9c 100%); color: #fff; border: none; padding: 14px 0; border-radius: 6px; margin-top: 25px; cursor: pointer; font-size: 1.1em; font-weight: bold; width: 100%; box-shadow: 0 2px 8px #ccc; transition: background 0.2s; }
    button:hover { background: linear-gradient(90deg, #1abc9c 0%, #2980b9 100%); }
    .register-icon { display: block; text-align: center; font-size: 3em; color: #1abc9c; margin-bottom: 15px; }
    .error { color: #e74c3c; text-align: center; margin-top: 10px; }
    .success { color: #27ae60; text-align: center; margin-top: 10px; }
    footer { text-align: center; margin-top: 40px; color: #636e72; font-size: 0.95em; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <img src="6f247f96-6c0f-4c50-a15e-ccebce79a2d7.jpg" alt="شعار الكلية">
      <h1>إنشاء حساب جديد</h1>
    </header>
    <span class="register-icon"><i class="fa-solid fa-user-plus"></i></span>
    <?php if ($error): ?>
      <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="POST">
      <label for="username"><i class="fa-solid fa-user"></i> اسم المستخدم</label>
      <input type="text" id="username" name="username" required>
      <label for="email"><i class="fa-solid fa-envelope"></i> البريد الإلكتروني</label>
      <input type="email" id="email" name="email" required>
      <label for="password"><i class="fa-solid fa-key"></i> كلمة المرور</label>
      <input type="password" id="password" name="password" required>
      <label for="role"><i class="fa-solid fa-users"></i> نوع المستخدم</label>
      <select id="role" name="role" required>
        <option value="student">طالب</option>
        <option value="supervisor">مشرف</option>
      </select>
      <button type="submit"><i class="fa-solid fa-user-plus"></i> إنشاء الحساب</button>
    </form>
    <footer>
      جميع الحقوق محفوظة &copy; كلية تقنية المعلومات 2025
    </footer>
  </div>
</body>
</html>
