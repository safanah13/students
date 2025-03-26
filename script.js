// متغيرات عامة للتطبيق
let selectedClasses = [];
let studentsData = [];

// دالة تسجيل الدخول
function login(event) {
    event.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (username === "admin" && password === "admin123") {
        localStorage.setItem('isLoggedIn', 'true');
        window.location.href = 'classes.html';
    } else {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'اسم المستخدم أو كلمة المرور غير صحيحة'
        });
    }
}

// التحقق من تسجيل الدخول
function checkLogin() {
    if (!localStorage.getItem('isLoggedIn')) {
        window.location.href = 'index.html'; // تم التعديل من login.html إلى index.html
    }
}

// تسجيل الخروج
function logout() {
    localStorage.removeItem('isLoggedIn');
    window.location.href = 'index.html'; // تم التعديل من login.html إلى index.html
}

// إضافة أو إزالة فصل من القائمة المحددة
function toggleClass(classId) {
    const card = document.getElementById(classId);
    if (card.classList.contains('selected')) {
        card.classList.remove('selected');
        selectedClasses = selectedClasses.filter(id => id !== classId);
    } else {
        card.classList.add('selected');
        selectedClasses.push(classId);
    }
    updateSelectedClassesDisplay();
}

// تحديث عرض الفصول المحددة
function updateSelectedClassesDisplay() {
    const display = document.getElementById('selectedClasses');
    if (display) {
        display.textContent = `الفصول المحددة: ${selectedClasses.join(', ')}`;
    }
}

// استيراد بيانات الطلاب من ملف Excel
function importExcel(event) {
    const file = event.target.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        studentsData = XLSX.utils.sheet_to_json(firstSheet);
        
        displayStudentsData();
        Swal.fire({
            icon: 'success',
            title: 'تم',
            text: 'تم استيراد البيانات بنجاح'
        });
    };
    
    reader.readAsArrayBuffer(file);
}

// عرض بيانات الطلاب في الجدول
function displayStudentsData() {
    const tableBody = document.getElementById('studentsTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '';
    studentsData.forEach((student, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${student.name || ''}</td>
            <td>${student.class || ''}</td>
            <td>
                <input type="checkbox" onchange="updateAttendance(${index}, this.checked)">
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// تحديث سجل الحضور
function updateAttendance(studentIndex, isPresent) {
    if (!studentsData[studentIndex].attendance) {
        studentsData[studentIndex].attendance = {};
    }
    const today = new Date().toISOString().split('T')[0];
    studentsData[studentIndex].attendance[today] = isPresent;
}

// تصدير بيانات الحضور
function exportAttendance() {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(studentsData);
    XLSX.utils.book_append_sheet(wb, ws, "Attendance");
    XLSX.writeFile(wb, `attendance_${new Date().toISOString().split('T')[0]}.xlsx`);
}

// تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    checkLogin();
    if (window.location.pathname.includes('attendance.html')) {
        displayStudentsData();
    }
});