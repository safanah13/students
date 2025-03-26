<?php
require_once '../config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'الرجاء تسجيل الدخول أولاً']);
    exit;
}

// واجهة API للتقارير
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $report_type = isset($_GET['type']) ? $_GET['type'] : '';
    
    switch ($report_type) {
        case 'student':
            // تقرير طالب معين
            $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
            
            if ($student_id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'معرف الطالب مطلوب']);
                exit;
            }
            
            try {
                // معلومات الطالب
                $stmt = $conn->prepare("
                    SELECT s.id, s.student_id, s.name, s.class_id, c.name AS class_name
                    FROM students s
                    JOIN classes c ON s.class_id = c.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    http_response_code(404);
                    echo json_encode(['error' => 'الطالب غير موجود']);
                    exit;
                }
                
                // سجل الحضور
                $stmt = $conn->prepare("
                    SELECT status, date FROM attendance
                    WHERE student_id = ?
                    ORDER BY date DESC
                ");
                $stmt->execute([$student_id]);
                $attendanceRecords = $stmt->fetchAll();
                
                // إحصائيات الحضور
                $present_count = 0;
                $absent_count = 0;
                foreach ($attendanceRecords as $record) {
                    if ($record['status'] === 'حاضر') {
                        $present_count++;
                    } else if ($record['status'] === 'غائب') {
                        $absent_count++;
                    }
                }
                
                // سجل السلوك
                $stmt = $conn->prepare("
                    SELECT type, note, date FROM behaviors
                    WHERE student_id = ?
                    ORDER BY date DESC
                ");
                $stmt->execute([$student_id]);
                $behaviors = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'student' => $student,
                    'attendance' => [
                        'records' => $attendanceRecords,
                        'present_count' => $present_count,
                        'absent_count' => $absent_count
                    ],
                    'behaviors' => $behaviors
                ]);
                
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'فشل جلب التقرير: ' . $e->getMessage()]);
            }
            break;
            
        case 'class':
            // تقرير فصل دراسي
            $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
            
            if ($class_id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'معرف الفصل مطلوب']);
                exit;
            }
            
            try {
                // معلومات الفصل
                $stmt = $conn->prepare("SELECT id, name, description FROM classes WHERE id = ?");
                $stmt->execute([$class_id]);
                $class = $stmt->fetch();
                
                if (!$class) {
                    http_response_code(404);
                    echo json_encode(['error' => 'الفصل غير موجود']);
                    exit;
                }
                
                // طلاب الفصل مع إحصائيات الحضور
                $stmt = $conn->prepare("
                    SELECT s.id, s.student_id, s.name,
                        SUM(CASE WHEN a.status = 'حاضر' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'غائب' THEN 1 ELSE 0 END) as absent_count
                    FROM students s
                    LEFT JOIN attendance a ON s.id = a.student_id
                    WHERE s.class_id = ?
                    GROUP BY s.id
                    ORDER BY s.name
                ");
                $stmt->execute([$class_id]);
                $students = $stmt->fetchAll();
                
                // إجمالي الحضور والغياب
                $total_present = 0;
                $total_absent = 0;
                foreach ($students as $student) {
                    $total_present += $student['present_count'];
                    $total_absent += $student['absent_count'];
                }
                
                echo json_encode([
                    'success' => true,
                    'class' => $class,
                    'students' => $students,
                    'total_present' => $total_present,
                    'total_absent' => $total_absent
                ]);
                
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'فشل جلب التقرير: ' . $e->getMessage()]);
            }
            break;
            
        case 'dashboard':
            // تقرير للوحة القيادة
            try {
                $today = date('Y-m-d');
                $user_id = $_SESSION['user_id'];
                $is_admin = isAdmin();
                
                // عدد الفصول
                if ($is_admin) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM classes");
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) FROM teacher_classes
                        WHERE teacher_id = ?
                    ");
                    $stmt->execute([$user_id]);
                }
                $classes_count = $stmt->fetchColumn();
                
                // عدد الطلاب
                if ($is_admin) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM students");
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("
                        SELECT COUNT(s.id) FROM students s
                        JOIN classes c ON s.class_id = c.id
                        JOIN teacher_classes tc ON c.id = tc.class_id
                        WHERE tc.teacher_id = ?
                    ");
                    $stmt->execute([$user_id]);
                }
                $students_count = $stmt->fetchColumn();
                
                // إحصائيات الحضور اليوم
                if ($is_admin) {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) FROM attendance
                        WHERE date = ? AND status = 'حاضر'
                    ");
                    $stmt->execute([$today]);
                } else {
                    $stmt = $conn->prepare("
                        SELECT COUNT(a.id) FROM attendance a
                        JOIN students s ON a.student_id = s.id
                        JOIN classes c ON s.class_id = c.id
                        JOIN teacher_classes tc ON c.id = tc.class_id
                        WHERE tc.teacher_id = ? AND a.date = ? AND a.status = 'حاضر'
                    ");
                    $stmt->execute([$user_id, $today]);
                }
                $present_today = $stmt->fetchColumn();
                
                // عدد الغياب اليوم
                if ($is_admin) {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) FROM attendance
                        WHERE date = ? AND status = 'غائب'
                    ");
                    $stmt->execute([$today]);
                } else {
                    $stmt = $conn->prepare("
                        SELECT COUNT(a.id) FROM attendance a
                        JOIN students s ON a.student_id = s.id
                        JOIN classes c ON s.class_id = c.id
                        JOIN teacher_classes tc ON c.id = tc.class_id
                        WHERE tc.teacher_id = ? AND a.date = ? AND a.status = 'غائب'
                    ");
                    $stmt->execute([$user_id, $today]);
                }
                $absent_today = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'stats' => [
                        'classes_count' => $classes_count,
                        'students_count' => $students_count,
                        'present_today' => $present_today,
                        'absent_today' => $absent_today
                    ]
                ]);
                
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'فشل جلب التقرير: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'نوع تقرير غير صالح']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'الطريقة غير مسموح بها']);
}
?>