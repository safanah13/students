<?php
require_once 'config/db_config.php';

// كلمة المرور الجديدة
$new_password = '123456';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشخيص وإصلاح حساب المسؤول</title>
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #1a237e; color: white; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 30px; max-width: 600px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); }
        h1 { color: #ffd700; margin-bottom: 30px; }
        p { margin-bottom: 15px; }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        a { color: #ffd700; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #ffd700; color: #1a237e; border: none; border-radius: 25px; font-size: 16px; cursor: pointer; transition: all 0.3s ease; margin-top: 20px; text-decoration: none; }
        .btn:hover { transform: scale(1.05); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>تشخيص وإصلاح حساب المسؤول</h1>
        
        <?php
        try {
            // التحقق أولاً من وجود المستخدم
            $check = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
            $check->execute();
            $admin_exists = $check->fetch();
            
            if ($admin_exists) {
                // تحديث كلمة المرور
                echo "<p>المستخدم 'admin' موجود، جاري تحديث كلمة المرور...</p>";
                $update = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                $update->execute([$hashed_password]);
                echo "<p class='success'>تم تحديث كلمة المرور بنجاح!</p>";
            } else {
                // إنشاء مستخدم جديد إذا لم يكن موجودًا
                echo "<p>المستخدم 'admin' غير موجود، جاري إنشاء المستخدم...</p>";
                $insert = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $insert->execute(['admin', $hashed_password, 'مدير النظام', 'admin']);
                echo "<p class='success'>تم إنشاء المستخدم 'admin' بنجاح!</p>";
            }
            
            $_SESSION['success_message'] = "تم إعادة تعيين حساب المسؤول بنجاح.";
            
            echo "<p>يمكنك الآن تسجيل الدخول باستخدام:</p>";
            echo "<p>اسم المستخدم: <strong>admin</strong></p>";
            echo "<p>كلمة المرور: <strong>$new_password</strong></p>";
        } catch (PDOException $e) {
            echo "<p class='error'>حدث خطأ: " . $e->getMessage() . "</p>";
        }
        ?>
        
        <a href="index.php" class="btn">العودة إلى صفحة تسجيل الدخول</a>
    </div>
</body>
</html>