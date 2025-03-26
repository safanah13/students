<?php
require_once 'config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'] == 'admin' ? 'مدير النظام' : 'معلم';

// إحصائيات
try {
    // عدد الفصول
    if (isAdmin()) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM classes");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM teacher_classes
            WHERE teacher_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $classes_count = $stmt->fetchColumn();
    
    // عدد الطلاب
    if (isAdmin()) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM students");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(s.id) FROM students s
            JOIN classes c ON s.class_id = c.id
            JOIN teacher_classes tc ON c.id = tc.class_id
            WHERE tc.teacher_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $students_count = $stmt->fetchColumn();
    
    // إحصائيات الحضور اليوم
    $today = date('Y-m-d');
    if (isAdmin()) {
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
        $stmt->execute([$_SESSION['user_id'], $today]);
    }
    $present_today = $stmt->fetchColumn();
    
    // عدد الغياب اليوم
    if (isAdmin()) {
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
        $stmt->execute([$_SESSION['user_id'], $today]);
    }
    $absent_today = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة الفصول والحضور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: #1a237e;
            color: white;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .user-bar {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info i {
            font-size: 20px;
            color: #ffd700;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a.active {
            background-color: #ffd700;
            color: #1a237e;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
            color: #ffd700;
        }

        .welcome-message {
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
            color: #ffd700;
        }

        .stat-title {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .quick-actions {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .quick-actions h2 {
            margin-bottom: 20px;
            color: white;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #ffd700;
            color: #1a237e;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .user-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-bar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo $user_name; ?> (<?php echo $user_role; ?>)</span>
            </div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="active">لوحة التحكم</a>
                <a href="attendance.php">الحضور والغياب</a>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </div>
        
        <h1>نظام إدارة الفصول والحضور</h1>
        
        <div class="welcome-message">
            <p>أهلاً بك، <?php echo $user_name; ?>! هذا نظام إدارة الفصول والحضور الخاص بك.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard"></i></div>
                <div class="stat-value"><?php echo $classes_count; ?></div>
                <div class="stat-title">الفصول الدراسية</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-value"><?php echo $students_count; ?></div>
                <div class="stat-title">عدد الطلاب</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $present_today; ?></div>
                <div class="stat-title">حضور اليوم</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?php echo $absent_today; ?></div>
                <div class="stat-title">غياب اليوم</div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>الإجراءات السريعة</h2>
            <div class="action-buttons">
                <a href="attendance.php" class="action-btn">
                    <i class="fas fa-clipboard-list"></i>
                    تسجيل الحضور
                </a>
                
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    تقارير وإحصائيات
                </a>
                
                <?php if (isAdmin()): ?>
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    إدارة المستخدمين
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>