<?php
require_once 'config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'] == 'admin' ? 'مدير النظام' : 'معلم';
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الفصول والحضور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- إضافة ملف CSS المنفصل -->
    <link rel="stylesheet" href="css/attendance.css">
</head>
<body>
    <!-- شريط المستخدم العلوي -->
    <?php include 'includes/user_bar.php'; ?>

    <div class="container">
        <h1>نظام إدارة الفصول والحضور</h1>
        
        <!-- قسم الفصول -->
        <div class="school-section">
            <h2 class="school-title">الفصول الدراسية</h2>
            <div class="classes-grid" id="classesGrid">
                <!-- سيتم إضافة الفصول ديناميكياً -->
            </div>
        </div>
        
        <!-- قسم الحضور والغياب -->
        <div id="attendanceSection" class="attendance-section" style="display: none;">
            <h2 id="selectedClassTitle">سجل الحضور والغياب</h2>
            
            <!-- محدد التاريخ -->
            <div class="date-selector">
                <label for="attendanceDate">التاريخ:</label>
                <input type="date" id="attendanceDate" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <!-- أزرار الإجراءات -->
            <div class="header-actions">
                <button class="btn report-btn" onclick="generateClassReport()">
                    <i class="fas fa-chart-bar"></i> إحصائية الفصل
                </button>
                <button class="btn excel-btn" onclick="downloadExcelTemplate()">
                    <i class="fas fa-file-download"></i> تحميل نموذج
                </button>
                <button class="btn import-btn" onclick="importFromExcel()">
                    <i class="fas fa-file-upload"></i> استيراد من Excel
                </button>
                <input type="file" id="excelFileInput" style="display: none" accept=".xlsx,.xls">
                <button class="btn excel-btn" onclick="exportAttendance()">
                    <i class="fas fa-file-export"></i> تصدير البيانات
                </button>
            </div>
            
            <!-- جدول الطلاب -->
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>رقم الطالب</th>
                        <th>اسم الطالب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody"></tbody>
            </table>
            
            <button class="save-button" onclick="saveAllAttendance()">
                <i class="fas fa-save"></i> حفظ الحضور
            </button>
        </div>
    </div>

    <!-- إضافة ملف JavaScript المنفصل -->
    <script src="js/attendance.js"></script>
</body>
</html>
