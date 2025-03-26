-- جدول المستخدمين (المعلمين)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('admin', 'teacher') DEFAULT 'teacher',
  last_login DATETIME
);

-- جدول الفصول الدراسية
CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  description VARCHAR(255)
);

-- جدول المعلمين والفصول (علاقة)
CREATE TABLE teacher_classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  class_id INT NOT NULL,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- جدول الطلاب
CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  class_id INT NOT NULL,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- جدول سجل الحضور
CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  status ENUM('حاضر', 'غائب') NOT NULL,
  date DATE NOT NULL,
  recorded_by INT NOT NULL,
  recorded_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول سجل السلوك
CREATE TABLE behaviors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type ENUM('إيجابي', 'سلبي') NOT NULL,
  note TEXT,
  date DATE NOT NULL,
  recorded_by INT NOT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- إضافة بيانات مبدئية
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$7CZP7RrdrRwmY/XRYfFyUu/A7Fc5oKMoXOHwaxmIURG2xXS1dPRu2', 'مدير النظام', 'admin');
-- كلمة المرور هي: admin123

INSERT INTO classes (name, description) VALUES
('الصف الأول', 'الصف الأول الابتدائي'),
('الصف الثاني', 'الصف الثاني الابتدائي'),
('الصف الثالث', 'الصف الثالث الابتدائي');

-- ربط المدير بجميع الفصول
INSERT INTO teacher_classes (teacher_id, class_id) VALUES
(1, 1), (1, 2), (1, 3);