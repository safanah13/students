<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة الفصول والحضور</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        h1 {
            color: #ffd700;
            margin-bottom: 30px;
        }
        .error-message {
            background-color: rgba(255, 0, 0, 0.2);
            color: #ffcccc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: right;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        input:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.25);
            border-color: #ffd700;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #ffd700;
            color: #1a237e;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        .success-message {
            background-color: rgba(0, 255, 0, 0.2);
            color: #ccffcc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>نظام إدارة الفصول والحضور</h1>
        <?php
        // عرض رسالة النجاح إذا كانت موجودة في الجلسة
        session_start();
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // إزالة الرسالة بعد عرضها
        }
        ?>
<form action="https://your-domain.com/login_process.php" method="POST">
    <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>