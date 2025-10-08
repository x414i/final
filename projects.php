<?php
// صفحة عرض المشاريع المقبولة للزوار والطلاب
require_once 'db.php';
// البحث والتصفية
$where = "WHERE p.status = 'approved'";
$params = [];
$types = '';

if (!empty($_GET['section_id'])) {
    $where .= " AND p.section_id = ?";
    $params[] = intval($_GET['section_id']);
    $types .= 'i';
}
if (!empty($_GET['year'])) {
    $where .= " AND p.year = ?";
    $params[] = intval($_GET['year']);
    $types .= 'i';
}
if (!empty($_GET['keywords'])) {
    $where .= " AND (p.title LIKE ? OR p.summary LIKE ?)";
    $kw = '%' . $_GET['keywords'] . '%';
    $params[] = $kw;
    $params[] = $kw;
    $types .= 'ss';
}

// جلب الأقسام
$sections = $conn->query("SELECT id, name FROM sections");

// جلب المشاريع المقبولة
$sql = "SELECT p.*, u.username AS student, s.name AS section FROM projects p JOIN users u ON p.student_id = u.id JOIN sections s ON p.section_id = s.id $where ORDER BY p.uploaded_at DESC";
$stmt = $conn->prepare($sql);

if (count($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$projects = $stmt->get_result();
// تحويل النتائج إلى مصفوفة
$projects_data = [];
while ($row = $projects->fetch_assoc()) {
    $projects_data[] = $row;
}

// إرجاع البيانات بصيغة JSON
header('Content-Type: application/json');
echo json_encode($projects_data);
exit;
