<?php
require_once 'config/db_config.php';

// التحقق من تسجيل دخول المستخدم
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'] == 'admin' ? 'مدير النظام' : 'معلم';

// استعلام عن الفصول المتاحة للمستخدم
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM classes ORDER BY name");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT c.* FROM classes c
        JOIN teacher_classes tc ON c.id = tc.class_id
        WHERE tc.teacher_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
}
$classes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير والإحصائيات - نظام إدارة الفصول والحضور</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-container {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .report-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .report-filters select, .report-filters input {
            padding: 8px;
            border: 1px solid #ffd700;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 5px;
        }
        
        .export-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .report-table th, .report-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .report-table th {
            background-color: #ffd700;
            color: #1a237e;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .tab-button {
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
        }
        
        .tab-button.active {
            background-color: #ffd700;
            color: #1a237e;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
                <a href="reports.php" class="active">التقارير</a>
                <?php if (isAdmin()): ?>
                <a href="users.php">المستخدمون</a>
                <?php endif; ?>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </div>
        
        <h1>التقارير والإحصائيات</h1>
        
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('attendance-report')">تقارير الحضور</button>
                <button class="tab-button" onclick="showTab('behavior-report')">تقارير السلوك</button>
                <?php if (isAdmin()): ?>
                <button class="tab-button" onclick="showTab('users-report')">تقارير المستخدمين</button>
                <?php endif; ?>
            </div>
            
            <div id="attendance-report" class="tab-content active">
                <div class="report-container">
                    <div class="report-header">
                        <h2>تقرير الحضور والغياب</h2>
                        <button class="export-btn" onclick="exportAttendanceReport()">
                            <i class="fas fa-file-export"></i> تصدير التقرير
                        </button>
                    </div>
                    
                    <div class="report-filters">
                        <select id="class-filter" onchange="fetchAttendanceReport()">
                            <option value="">كل الفصول</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="date" id="from-date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" onchange="fetchAttendanceReport()">
                        <input type="date" id="to-date" value="<?php echo date('Y-m-d'); ?>" onchange="fetchAttendanceReport()">
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="attendance-chart"></canvas>
                    </div>
                    
                    <table class="report-table" id="attendance-table">
                        <thead>
                            <tr>
                                <th>الفصل</th>
                                <th>عدد الطلاب</th>
                                <th>الحضور</th>
                                <th>الغياب</th>
                                <th>نسبة الحضور</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table-body">
                            <tr>
                                <td colspan="5">جاري تحميل البيانات...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="behavior-report" class="tab-content">
                <div class="report-container">
                    <div class="report-header">
                        <h2>تقرير السلوكيات</h2>
                        <button class="export-btn" onclick="exportBehaviorReport()">
                            <i class="fas fa-file-export"></i> تصدير التقرير
                        </button>
                    </div>
                    
                    <div class="report-filters">
                        <select id="behavior-class-filter" onchange="fetchBehaviorReport()">
                            <option value="">كل الفصول</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="behavior-type-filter" onchange="fetchBehaviorReport()">
                            <option value="">كل السلوكيات</option>
                            <option value="إيجابي">إيجابي</option>
                            <option value="سلبي">سلبي</option>
                        </select>
                        
                        <input type="date" id="behavior-from-date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" onchange="fetchBehaviorReport()">
                        <input type="date" id="behavior-to-date" value="<?php echo date('Y-m-d'); ?>" onchange="fetchBehaviorReport()">
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="behavior-chart"></canvas>
                    </div>
                    
                    <table class="report-table" id="behavior-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الفصل</th>
                                <th>نوع السلوك</th>
                                <th>الملاحظة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody id="behavior-table-body">
                            <tr>
                                <td colspan="5">جاري تحميل البيانات...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (isAdmin()): ?>
            <div id="users-report" class="tab-content">
                <div class="report-container">
                    <div class="report-header">
                        <h2>تقرير المستخدمين</h2>
                        <button class="export-btn" onclick="exportUsersReport()">
                            <i class="fas fa-file-export"></i> تصدير التقرير
                        </button>
                    </div>
                    
                    <div class="report-filters">
                        <select id="user-role-filter" onchange="fetchUsersReport()">
                            <option value="">كل المستخدمين</option>
                            <option value="admin">مدير النظام</option>
                            <option value="teacher">معلم</option>
                        </select>
                    </div>
                    
                    <table class="report-table" id="users-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>اسم المستخدم</th>
                                <th>الدور</th>
                                <th>آخر تسجيل دخول</th>
                                <th>الفصول</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <tr>
                                <td colspan="5">جاري تحميل البيانات...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let attendanceChart = null;
        let behaviorChart = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            fetchAttendanceReport();
        });
        
        function showTab(tabId) {
            // إخفاء كل محتويات التبويبات
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // إزالة التنشيط من كل أزرار التبويبات
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // إظهار التبويب المحدد
            document.getElementById(tabId).classList.add('active');
            
            // تنشيط الزر المناسب
            document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`).classList.add('active');
            
            // تحميل البيانات المناسبة
            if (tabId === 'attendance-report') {
                fetchAttendanceReport();
            } else if (tabId === 'behavior-report') {
                fetchBehaviorReport();
            } else if (tabId === 'users-report') {
                fetchUsersReport();
            }
        }
        
        async function fetchAttendanceReport() {
            const classId = document.getElementById('class-filter').value;
            const fromDate = document.getElementById('from-date').value;
            const toDate = document.getElementById('to-date').value;
            
            try {
                const response = await fetch(`api/reports.php?type=attendance&class_id=${classId}&from_date=${fromDate}&to_date=${toDate}`);
                const data = await response.json();
                
                if (data.success) {
                    updateAttendanceChart(data);
                    updateAttendanceTable(data);
                } else {
                    showError('فشل في جلب بيانات التقرير: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                showError('فشل في الاتصال بالخادم: ' + error.message);
            }
        }
        
        function updateAttendanceChart(data) {
            const ctx = document.getElementById('attendance-chart').getContext('2d');
            
            // تدمير المخطط القديم إن وجد
            if (attendanceChart) {
                attendanceChart.destroy();
            }
            
            // إنشاء مصفوفات البيانات
            const labels = data.classes.map(c => c.name);
            const presentData = data.classes.map(c => c.present_count);
            const absentData = data.classes.map(c => c.absent_count);
            
            // إنشاء المخطط الجديد
            attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'الحضور',
                            data: presentData,
                            backgroundColor: 'rgba(76, 175, 80, 0.7)',
                            borderColor: 'rgba(76, 175, 80, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'الغياب',
                            data: absentData,
                            backgroundColor: 'rgba(244, 67, 54, 0.7)',
                            borderColor: 'rgba(244, 67, 54, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'white'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'white'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'white'
                            }
                        }
                    }
                }
            });
        }
        
        function updateAttendanceTable(data) {
            const tableBody = document.getElementById('attendance-table-body');
            tableBody.innerHTML = '';
            
            if (data.classes.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5">لا توجد بيانات متاحة</td></tr>';
                return;
            }
            
            data.classes.forEach(classData => {
                const totalAttendance = classData.present_count + classData.absent_count;
                const attendanceRate = totalAttendance > 0 ? ((classData.present_count / totalAttendance) * 100).toFixed(1) : '0';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${classData.name}</td>
                    <td>${classData.students_count}</td>
                    <td>${classData.present_count}</td>
                    <td>${classData.absent_count}</td>
                    <td>${attendanceRate}%</td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        async function fetchBehaviorReport() {
            const classId = document.getElementById('behavior-class-filter').value;
            const behaviorType = document.getElementById('behavior-type-filter').value;
            const fromDate = document.getElementById('behavior-from-date').value;
            const toDate = document.getElementById('behavior-to-date').value;
            
            try {
                const response = await fetch(`api/reports.php?type=behavior&class_id=${classId}&behavior_type=${behaviorType}&from_date=${fromDate}&to_date=${toDate}`);
                const data = await response.json();
                
                if (data.success) {
                    updateBehaviorChart(data);
                    updateBehaviorTable(data);
                } else {
                    showError('فشل في جلب بيانات التقرير: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                showError('فشل في الاتصال بالخادم: ' + error.message);
            }
        }
        
        function updateBehaviorChart(data) {
            const ctx = document.getElementById('behavior-chart').getContext('2d');
            
            // تدمير المخطط القديم إن وجد
            if (behaviorChart) {
                behaviorChart.destroy();
            }
            
            // إنشاء مصفوفات البيانات
            const labels = data.classes.map(c => c.name);
            const positiveData = data.classes.map(c => c.positive_count);
            const negativeData = data.classes.map(c => c.negative_count);
            
            // إنشاء المخطط الجديد
            behaviorChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'سلوك إيجابي',
                            data: positiveData,
                            backgroundColor: 'rgba(33, 150, 243, 0.7)',
                            borderColor: 'rgba(33, 150, 243, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'سلوك سلبي',
                            data: negativeData,
                            backgroundColor: 'rgba(255, 152, 0, 0.7)',
                            borderColor: 'rgba(255, 152, 0, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'white'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'white'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'white'
                            }
                        }
                    }
                }
            });
        }
        
        function updateBehaviorTable(data) {
            const tableBody = document.getElementById('behavior-table-body');
            tableBody.innerHTML = '';
            
            if (data.behaviors.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5">لا توجد بيانات متاحة</td></tr>';
                return;
            }
            
            data.behaviors.forEach(behavior => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${behavior.student_name}</td>
                    <td>${behavior.class_name}</td>
                    <td>${behavior.type}</td>
                    <td>${behavior.note}</td>
                    <td>${behavior.date}</td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        async function fetchUsersReport() {
            const role = document.getElementById('user-role-filter').value;
            
            try {
                const response = await fetch(`api/reports.php?type=users&role=${role}`);
                const data = await response.json();
                
                if (data.success) {
                    updateUsersTable(data);
                } else {
                    showError('فشل في جلب بيانات التقرير: ' + (data.error || 'خطأ غير معروف'));
                }
            } catch (error) {
                showError('فشل في الاتصال بالخادم: ' + error.message);
            }
        }
        
        function updateUsersTable(data) {
            const tableBody = document.getElementById('users-table-body');
            tableBody.innerHTML = '';
            
            if (data.users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5">لا توجد بيانات متاحة</td></tr>';
                return;
            }
            
            data.users.forEach(user => {
                const role = user.role === 'admin' ? 'مدير النظام' : 'معلم';
                const lastLogin = user.last_login || 'لم يسجل دخول بعد';
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.full_name}</td>
                    <td>${user.username}</td>
                    <td>${role}</td>
                    <td>${lastLogin}</td>
                    <td>${user.classes || '-'}</td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        function exportAttendanceReport() {
            window.print();
        }
        
        function exportBehaviorReport() {
            window.print();
        }
        
        function exportUsersReport() {
            window.print();
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