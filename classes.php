<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختيار الفصول الدراسية</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <button class="btn" onclick="logout()" style="float: left;">تسجيل الخروج</button>
        <h1>اختيار الفصول الدراسية</h1>
        
        <div class="school-section">
            <h2>المرحلة الابتدائية</h2>
            <div class="classes-grid">
                <div class="class-card" id="class1-1" onclick="toggleClass('class1-1')">
                    <i class="fas fa-chalkboard"></i>
                    <h3>الصف الأول - 1</h3>
                </div>
                <div class="class-card" id="class1-2" onclick="toggleClass('class1-2')">
                    <i class="fas fa-chalkboard"></i>
                    <h3>الصف الأول - 2</h3>
                </div>
                <!-- إضافة المزيد من الفصول حسب الحاجة -->
            </div>
        </div>

        <div class="school-section">
            <h2>المرحلة المتوسطة</h2>
            <div class="classes-grid">
                <div class="class-card" id="class7-1" onclick="toggleClass('class7-1')">
                    <i class="fas fa-chalkboard"></i>
                    <h3>الصف السابع - 1</h3>
                </div>
                <!-- إضافة المزيد من الفصول حسب الحاجة -->
            </div>
        </div>

        <div id="selectedClasses" class="school-section">
            الفصول المحددة:
        </div>

        <button class="btn" onclick="window.location.href='attendance.html'">متابعة</button>
    </div>
    <script src="script.js"></script>
</body>
</html>
