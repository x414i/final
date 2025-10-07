<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "graduation_platform";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
