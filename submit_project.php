<?php
// استقبال البيانات من النموذج
$title = $_POST['title'];
$student = $_POST['student_name'];
$supervisor = $_POST['supervisor_name'];
$dept = $_POST['department'];
$year = $_POST['year'];
$abstract = $_POST['abstract'];

// تنسيق البيانات كسطر واحد
$line = "$title | $student | $supervisor | $dept | $year | $abstract\n";

// حفظ البيانات في ملف نصي
file_put_contents("projects.txt", $line, FILE_APPEND);

// عرض رسالة تأكيد
echo "<h3>✅ تم حفظ المشروع مؤقتًا في ملف نصي.</h3>";
echo "<a href='form.html'>إدخال مشروع آخر</a>";
?>
