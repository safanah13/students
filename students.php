<?php
require_once '../config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'الرجاء تسجيل الدخول أولاً']);
    exit;
}

// واجهة API لإدارة الطلاب
header('Content-Type: application/json; charset=utf-8');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // جلب الطلاب في فصل معين
        $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        
        if ($class_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'معرف الفصل غير صالح']);
            exit;
        }
        
        // التحقق من أن المعلم لديه صلاحية للوصول لهذا الفصل
        if (!isAdmin()) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM teacher_classes 
                WHERE teacher_id = ? AND class_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $class_id]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['error' => 'ليس لديك صلاحية للوصول إلى هذا الفصل']);
                exit;
            }
        }
        
        // جلب الطلاب
        $stmt = $conn->prepare("
            SELECT s.id, s.student_id, s.name, s.class_id, c.name AS class_name
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.class_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$class_id]);
        $students = $stmt->fetchAll();
        
        // جلب آخر سجل حضور لكل طالب
        foreach ($students as &$student) {
            $stmt = $conn->prepare("
                SELECT status, date FROM attendance 
                WHERE student_id = ? 
                ORDER BY date DESC, recorded_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$student['id']]);
            $lastAttendance = $stmt->fetch();
            
            $student['status'] = $lastAttendance ? $lastAttendance['status'] : '';
            $student['last_attendance_date'] = $lastAttendance ? $lastAttendance['date'] : '';
        }
        
        echo json_encode(['success' => true, 'students' => $students]);
        break;
        
    case 'POST':
        // إضافة طالب جديد
        $data = json_decode(file_get_contents('php://input'), true);
        $student_id = sanitize($data['student_id'] ?? '');
        $name = sanitize($data['name'] ?? '');
        $class_id = (int)($data['class_id'] ?? 0);
        
        if (empty($name) || $class_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'جميع الحقول مطلوبة']);
            exit;
        }
        
        // التحقق من أن المعلم لديه صلاحية للوصول لهذا الفصل
        if (!isAdmin()) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM teacher_classes 
                WHERE teacher_id = ? AND class_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $class_id]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['error' => 'ليس لديك صلاحية للوصول إلى هذا الفصل']);
                exit;
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO students (student_id, name, class_id) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $name, $class_id]);
            $id = $conn->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إضافة الطالب بنجاح',
                'student' => [
                    'id' => $id,
                    'student_id' => $student_id,
                    'name' => $name,
                    'class_id' => $class_id
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'فشل إضافة الطالب: ' . $e->getMessage()]);
        }
        break;
}
?>