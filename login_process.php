<?php
require_once 'config/db_config.php';

// تلقي بيانات النموذج
$username = sanitize($_POST['username']);
$password = $_POST['password'];

try {
    // البحث عن المستخدم في قاعدة البيانات
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // تسجيل الدخول ناجح
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // التوجيه إلى لوحة التحكم
        header("Location: dashboard.php");
        exit;
    } else {
        // تسجيل الدخول فاشل
        $_SESSION['error'] = "اسم المستخدم أو كلمة المرور غير صحيحة";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "حدث خطأ في النظام. الرجاء المحاولة مرة أخرى لاحقاً.";
    header("Location: index.php");
    exit;
}
?>