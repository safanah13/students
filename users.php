<?php
require_once '../config/db_config.php';

// التحقق من تسجيل دخول المستخدم وصلاحياته
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'غير مصرح لك بالوصول إلى هذه البيانات']);
    exit;
}

// التعامل مع طلبات GET للحصول على قائمة المستخدمين
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // استعلام عن المستخدمين وفصولهم
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.full_name, u.role, u.last_login
            FROM users u
            ORDER BY u.id
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        // الحصول على فصول كل مستخدم
        foreach ($users as &$user) {
            $classes_stmt = $conn->prepare("
                SELECT c.name
                FROM classes c
                JOIN teacher_classes tc ON c.id = tc.class_id
                WHERE tc.teacher_id = ?
            ");
            $classes_stmt->execute([$user['id']]);
            $classes = $classes_stmt->fetchAll(PDO::FETCH_COLUMN);
            $user['classes'] = implode(', ', $classes);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
// التعامل مع طلبات DELETE لحذف مستخدم
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // قراءة بيانات الطلب
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['id']) || empty($data['id'])) {
            throw new Exception('مُعرف المستخدم مطلوب');
        }
        
        $userId = (int) $data['id'];
        
        // حماية من حذف المستخدم الحالي
        if ($userId === (int) $_SESSION['user_id']) {
            throw new Exception('لا يمكنك حذف حسابك الحالي');
        }
        
        // بدء معاملة قاعدة البيانات
        $conn->beginTransaction();
        
        // حذف علاقات المستخدم بالفصول أولاً
        $stmt = $conn->prepare("DELETE FROM teacher_classes WHERE teacher_id = ?");
        $stmt->execute([$userId]);
        
        // حذف المستخدم نفسه
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // تأكيد المعاملة
        $conn->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
// التعامل مع طلبات أخرى غير مدعومة
else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'طريقة الطلب غير مدعومة']);
}
?>