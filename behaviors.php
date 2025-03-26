<?php
require_once '../config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'الرجاء تسجيل الدخول أولاً']);
    exit;
}

// واجهة API لإدارة ملاحظات السلوك
header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // إضافة ملاحظة سلوكية جديدة
        $data = json_decode(file_get_contents('php://input'), true);
        $student_id = (int)($data['student_id'] ?? 0);
        $type = $data['type'] ?? '';
        $note = sanitize($data['note'] ?? '');
        $date = $data['date'] ?? date('Y-m-d');
        
        if ($student_id <= 0 || !in_array($type, ['إيجابي', 'سلبي'])) {
            http_response_code(400);
            echo json_encode(['error' => 'بيانات غير صالحة']);
            exit;
        }
        
        // التحقق من أن المعلم لديه صلاحية للوصول لهذا الطالب
        if (!isAdmin()) {
            $stmt = $conn->prepare("
                SELECT c.id FROM students s
                JOIN classes c ON s.class_id = c.id
                JOIN teacher_classes tc ON c.id = tc.class_id
                WHERE s.id = ? AND tc.teacher_id = ?
                LIMIT 1
            ");
            $stmt->execute([$student_id, $_SESSION['user_id']]);
            if ($stmt->rowCount() == 0) {
                http_response_code(403);
                echo json_encode(['error' => 'ليس لديك صلاحية تسجيل ملاحظة لهذا الطالب']);
                exit;
            }
        }
        
        try {
            // إضافة ملاحظة سلوكية جديدة
            $stmt = $conn->prepare("
                INSERT INTO behaviors 
                (student_id, type, note, date, recorded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $type, $note, $date, $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم تسجيل الملاحظة بنجاح',
                'behavior_id' => $conn->lastInsertId()
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل تسجيل الملاحظة: ' . $e->getMessage()]);
        }
        break;
        
    case 'GET':
        // جلب ملاحظات السلوك
        $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        
        if ($student_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'معرف الطالب مطلوب']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT b.id, b.type, b.note, b.date, 
                       u.full_name AS recorded_by_name
                FROM behaviors b
                JOIN users u ON b.recorded_by = u.id
                WHERE b.student_id = ?
                ORDER BY b.date DESC
            ");
            $stmt->execute([$student_id]);
            $behaviors = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'behaviors' => $behaviors]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل جلب الملاحظات: ' . $e->getMessage()]);
        }
        break;
}
?>