-- EduCore Database Schema
CREATE DATABASE IF NOT EXISTS educore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educore;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('super_admin','faculty','student','parent','finance','librarian','tpo') NOT NULL DEFAULT 'student',
    photo VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    department VARCHAR(100),
    credits INT DEFAULT 3,
    faculty_id INT,
    FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    course_id INT,
    duration_minutes INT DEFAULT 60,
    total_marks INT DEFAULT 100,
    start_time DATETIME,
    end_time DATETIME,
    proctored TINYINT(1) DEFAULT 1,
    status ENUM('draft','scheduled','active','completed') DEFAULT 'draft',
    created_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq','short','code','essay') DEFAULT 'mcq',
    options JSON,
    correct_answer TEXT,
    marks INT DEFAULT 5,
    bloom_level VARCHAR(30),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS fee_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('tuition','hostel','transport','lab','misc') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    penalty_percent DECIMAL(5,2) DEFAULT 0,
    due_date DATE
);

CREATE TABLE IF NOT EXISTS fee_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    template_id INT,
    amount DECIMAL(10,2) NOT NULL,
    penalty DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES fee_templates(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20),
    title VARCHAR(300) NOT NULL,
    author VARCHAR(200),
    category VARCHAR(100),
    qr_code VARCHAR(100) UNIQUE,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS library_circulation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,
    returned_at DATETIME NULL,
    fine_amount DECIMAL(8,2) DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS placement_drives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    job_title VARCHAR(200) NOT NULL,
    description TEXT,
    min_cgpa DECIMAL(3,2) DEFAULT 6.00,
    package_lpa DECIMAL(8,2),
    drive_date DATE,
    status ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming'
);

CREATE TABLE IF NOT EXISTS placement_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drive_id INT NOT NULL,
    student_id INT NOT NULL,
    resume_path VARCHAR(255),
    fitment_score DECIMAL(5,2),
    status ENUM('applied','shortlisted','rejected','selected') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drive_id) REFERENCES placement_drives(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(200) NOT NULL,
    module VARCHAR(50),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Demo users (password: password123 for all)
INSERT INTO users (full_name, email, password, role, phone) VALUES
('Super Admin', 'admin@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '9876543210'),
('Dr. Priya Sharma', 'faculty@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '9876543211'),
('Krrish Jeswar', 'student@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543212'),
('Finance Officer', 'finance@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finance', '9876543213'),
('Library Manager', 'librarian@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', '9876543214'),
('TPO Head', 'tpo@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tpo', '9876543215');

INSERT INTO notices (title, content, posted_by, priority) VALUES
('Welcome to EduCore 2026', 'Unified AI-Powered ERP & LMS is now live. Explore AI Tutor, Proctored Exams, and QR Library!', 1, 'high'),
('Mid-Semester Exam Schedule', 'Exams begin March 15. Check your exam terminal for proctored sessions.', 1, 'medium'),
('Placement Drive - TechCorp', 'TechCorp hiring SDE interns. Min CGPA 7.0. Apply via Placement Portal.', 6, 'high');

INSERT INTO courses (code, title, department, credits, faculty_id) VALUES
('CS301', 'Data Structures & Algorithms', 'Computer Science', 4, 2),
('CS401', 'Machine Learning', 'Computer Science', 4, 2),
('EC201', 'Digital Signal Processing', 'Electronics', 3, 2);

INSERT INTO fee_templates (name, category, amount, penalty_percent, due_date) VALUES
('Semester Tuition Fee', 'tuition', 45000.00, 2.00, '2026-03-31'),
('Hostel Fee', 'hostel', 18000.00, 1.50, '2026-03-15'),
('Lab & Equipment Fee', 'lab', 5000.00, 0.00, '2026-04-01');

INSERT INTO fee_invoices (student_id, template_id, amount, status) VALUES
(3, 1, 45000.00, 'pending'),
(3, 2, 18000.00, 'paid'),
(3, 3, 5000.00, 'pending');

INSERT INTO library_books (isbn, title, author, category, qr_code, total_copies, available_copies, shelf_location) VALUES
('9780134685991', 'Effective Java', 'Joshua Bloch', 'Programming', 'QR-EJ-001', 3, 2, 'A-12'),
('9780132350884', 'Clean Code', 'Robert Martin', 'Programming', 'QR-CC-002', 2, 2, 'A-14'),
('9780262033848', 'Introduction to Algorithms', 'CLRS', 'Algorithms', 'QR-IA-003', 4, 3, 'B-01'),
('9780134093413', 'The C Programming Language', 'K&R', 'Programming', 'QR-C-004', 5, 4, 'A-08');

INSERT INTO placement_drives (company_name, job_title, description, min_cgpa, package_lpa, drive_date, status) VALUES
('TechCorp India', 'Software Development Engineer', 'Full-stack development with React and Node.js', 7.00, 12.00, '2026-04-15', 'upcoming'),
('DataMind Analytics', 'Data Science Intern', 'ML pipelines and data visualization', 6.50, 8.00, '2026-04-20', 'upcoming'),
('CloudNine Systems', 'DevOps Engineer', 'AWS, Docker, Kubernetes deployment', 7.50, 15.00, '2026-05-01', 'upcoming');
