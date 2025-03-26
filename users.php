<?php
require_once 'config/db_config.php';

// التحقق من تسجيل دخول المستخدم وصلاحياته
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'] == 'admin' ? 'مدير النظام' : 'معلم';

// استعلام عن الفصول لعرضها في نموذج إضافة مستخدم
$stmt = $conn->prepare("SELECT id, name FROM classes ORDER BY name");
$stmt->execute();
$classes = $stmt->fetchAll();

$message = '';
$error = '';

// معالجة طلبات إضافة المستخدم الجديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $full_name = sanitize($_POST['full_name']);
    $role = sanitize($_POST['role']);
    $selected_classes = isset($_POST['classes']) ? $_POST['classes'] : [];

    // التحقق من أن اسم المستخدم غير موجود بالفعل
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $error = 'اسم المستخدم موجود بالفعل، اختر اسمًا آخر';
    } else {
        try {
            // بدء المعاملة
            $conn->beginTransaction();

            // إضافة المستخدم الجديد
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $role]);
            $user_id = $conn->lastInsertId();

            // ربط المستخدم بالفصول المحددة
            if (!empty($selected_classes)) {
                $insert_class = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_id) VALUES (?, ?)");
                foreach ($selected_classes as $class_id) {
                    $insert_class->execute([$user_id, $class_id]);
                }
            }

            // تأكيد المعاملة
            $conn->commit();
            $message = 'تم إضافة المستخدم بنجاح';
        } catch (PDOException $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollBack();
            $error = 'حدث خطأ أثناء إضافة المستخدم: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - نظام إدارة الفصول والحضور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .card {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .users-table th, .users-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .users-table th {
            background-color: #ffd700;
            color: #1a237e;
        }
        
        .form-row {
            margin-bottom: 15px;
        }
        
        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-row input, .form-row select {
            width: 100%;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .form-row select option {
            background-color: #1a237e;
            color: white;
        }
        
        .form-row .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .form-row .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: #ffd700;
            color: #1a237e;
        }
        
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        
        .btn-edit {
            background-color: #2196F3;
            color: white;
        }
        
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .success {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .error {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        
        @media screen and (max-width: 768px) {
            .users-table {
                font-size: 14px;
            }
            
            .form-row .checkbox-group {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
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
                <a href="dashboard.php">لوحة التحكم</a>
                <a href="attendance.php">الحضور والغياب</a>
                <a href="reports.php">التقارير</a>
                <a href="users.php" class="active">المستخدمون</a>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </div>
        
        <h1>إدارة المستخدمين</h1>
        
        <?php if ($message): ?>
        <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h2>
            
            <form action="" method="post">
                <div class="form-row">
                    <label for="username">اسم المستخدم:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-row">
                    <label for="password">كلمة المرور:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-row">
                    <label for="full_name">الاسم الكامل:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-row">
                    <label for="role">الدور:</label>
                    <select id="role" name="role" required>
                        <option value="teacher">معلم</option>
                        <option value="admin">مدير النظام</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label>الفصول الدراسية:</label>
                    <div class="checkbox-group">
                        <?php foreach ($classes as $class): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="class_<?php echo $class['id']; ?>" name="classes[]" value="<?php echo $class['id']; ?>">
                            <label for="class_<?php echo $class['id']; ?>"><?php echo $class['name']; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" name="add_user" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة المستخدم
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-users"></i> قائمة المستخدمين</h2>
            
            <table class="users-table">
                <thead>
                    <tr>
                        <th>معرف</th>
                        <th>اسم المستخدم</th>
                        <th>الاسم الكامل</th>
                        <th>الدور</th>
                        <th>آخر تسجيل دخول</th>
                        <th>الفصول</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <!-- سيتم ملء البيانات بواسطة JavaScript -->
                    <tr>
                        <td colspan="7">جاري تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchUsers();
        });
        
        async function fetchUsers() {
            try {
                const response = await fetch('api/users.php');
                const data = await response.json();
                
                if (data.success) {
                    displayUsers(data.users);
                } else {
                    showError('فشل في جلب بيانات المستخدمين: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                showError('فشل في الاتصال بالخادم: ' + error.message);
            }
        }
        
        function displayUsers(users) {
            const tableBody = document.getElementById('users-table-body');
            tableBody.innerHTML = '';
            
            if (users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7">لا يوجد مستخدمين</td></tr>';
                return;
            }
            
            users.forEach(user => {
                const role = user.role === 'admin' ? 'مدير النظام' : 'معلم';
                const lastLogin = user.last_login || 'لم يسجل دخول بعد';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.full_name}</td>
                    <td>${role}</td>
                    <td>${lastLogin}</td>
                    <td>${user.classes || '-'}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editUser(${user.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete" onclick="deleteUser(${user.id}, '${user.username}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        function editUser(userId) {
            // هنا يمكنك تنفيذ منطق تحرير المستخدم
            // مثلًا، إظهار نموذج التحرير في نافذة منبثقة
            Swal.fire({
                title: 'تحرير المستخدم',
                text: `سيتم تنفيذ تحرير المستخدم برقم ${userId} قريبًا`,
                icon: 'info'
            });
        }
        
        function deleteUser(userId, username) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: `هل أنت متأكد من حذف المستخدم "${username}"؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    performDeleteUser(userId);
                }
            });
        }
        
        async function performDeleteUser(userId) {
            try {
                const response = await fetch('api/users.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: userId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire(
                        'تم الحذف!',
                        'تم حذف المستخدم بنجاح.',
                        'success'
                    );
                    
                    // إعادة تحميل قائمة المستخدمين
                    fetchUsers();
                } else {
                    showError('فشل في حذف المستخدم: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                showError('فشل في الاتصال بالخادم: ' + error.message);
            }
        }
        
        function showError(message) {
            Swal.fire({
                title: 'خطأ',
                text: message,
                icon: 'error'
            });
        }
    </script>
</body>
</html>