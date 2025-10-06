<?php
// ملف توليد كلمة مرور مشفرة لإضافتها في قاعدة البيانات
$password = '123456789'; // يمكنك تغييرها
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "كلمة المرور المشفرة: <br>" . $hash;
?>
