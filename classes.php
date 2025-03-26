<?php
require_once '../config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'الرجاء تسجيل الدخول أولاً']);
    exit;
}

// واجهة API لإدارة الفصول
header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // جلب الفصول المتاحة للمستخدم الحالي
        $teacher_id = $_SESSION['user_id'];
        
        // إذا كان المستخدم مسؤول، يمكنه رؤية جميع الفصول
        if (isAdmin()) {
            $stmt = $conn->prepare("SELECT id, name, description FROM classes ORDER BY name");
            $stmt->execute();
        } else {
            // جلب الفصول المرتبطة بالمعلم فقط
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.description
                FROM classes c
                JOIN teacher_classes tc ON c.id = tc.class_id
                WHERE tc.teacher_id = ?
                ORDER BY c.name
            ");
            $stmt->execute([$teacher_id]);
        }
        
        $classes = $stmt->fetchAll();
        echo json_encode(['success' => true, 'classes' => $classes]);
        break;
        
    case 'POST':
        // إضافة فصل جديد (للمسؤولين فقط)
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'ليس لديك صلاحية لإضافة فصل جديد']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $name = sanitize($data['name'] ?? '');
        $description = sanitize($data['description'] ?? '');
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'اسم الفصل مطلوب']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO classes (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $class_id = $conn->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة الفصل بنجاح', 'class_id' => $class_id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل إضافة الفصل: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'الطريقة غير مسموح بها']);
}
?>