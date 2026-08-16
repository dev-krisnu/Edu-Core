-- EduCore Database Schema
CREATE DATABASE IF NOT EXISTS educore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educore;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('super_admin','faculty','student','parent','finance','librarian','tpo') NOT NULL DEFAULT 'student',
    photo VARCHAR(255) DEFAULT 'default.png',
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    two_factor_secret VARCHAR(64) DEFAULT NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    reset_token_hash VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
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
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Super Admin', 'admin@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '948836388'),
('Faculty Demo', 'faculty@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '9000000001'),
('Mr. Lakhan Mahato', 'lakhanmahato@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '6295939450'),
('Krrish Jeswar', 'krrishjeswar@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9679469493'),
('Manish Kumar Chowdhury', 'manishkumarchowdhury@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '8327657732'),
('Komal Shaw', 'komalshaw@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '7029447905'),
('Bikram Ghosh', 'bikramghosh@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '7864813973'),
('Surjo Kanto Maji', 'surjokantomaji@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '7876500012'),
('Finance Officer', 'finance@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finance', '4956936438'),
('Library Manager', 'librarian@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', '0688659608'),
('TPO Head', 'tpo@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tpo', '9876543215'),
('Parent Demo', 'parent@educore.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', '9000000002');

INSERT INTO notices (title, content, posted_by, priority) VALUES
('Welcome to EduCore 2026', 'Unified AI-Powered ERP & LMS is now live. Explore AI Tutor, Proctored Exams, and QR Library!', 1, 'high'),
('Mid-Semester Exam Schedule', 'Exams begin March 15. Check your exam terminal for proctored sessions.', 1, 'medium'),
('Placement Drive - TechCorp', 'TechCorp hiring SDE interns. Min CGPA 7.0. Apply via Placement Portal.', 6, 'high');

INSERT INTO courses (code, title, department, credits, faculty_id) VALUES
('CS101', 'Introduction to Programming', 'Computer Science', 3, 1),
('CS202', 'Database Management Systems', 'Computer Science', 4, 3),
('CS305', 'Operating Systems', 'Computer Science', 4, 1),
('CS420', 'Artificial Intelligence', 'Computer Science', 4, 2),
('EC101', 'Basic Electronics', 'Electronics', 3, 4),
('EC302', 'Microprocessors & Microcontrollers', 'Electronics', 4, 2),
('EC405', 'Embedded Systems', 'Electronics', 3, 5),
('MA101', 'Calculus & Linear Algebra', 'Mathematics', 4, 6),
('MA204', 'Discrete Mathematics', 'Mathematics', 3, 6),
('ME102', 'Engineering Mechanics', 'Mechanical Engineering', 3, 7),
('ME301', 'Thermodynamics', 'Mechanical Engineering', 4, 7),
('PH101', 'Engineering Physics', 'Physics', 3, 8);

INSERT INTO fee_templates (name, category, amount, penalty_percent, due_date) VALUES
('Semester Tuition Fee', 'tuition', 45000.00, 2.00, '2026-03-31'),
('Hostel Fee', 'hostel', 18000.00, 1.50, '2026-03-15'),
('Lab & Equipment Fee', 'lab', 5000.00, 0.00, '2026-04-01');

INSERT INTO fee_invoices (student_id, template_id, amount, status) VALUES
(3, 1, 45000.00, 'pending'),
(3, 2, 18000.00, 'paid'),
(3, 3, 5000.00, 'pending');


INSERT INTO library_books (isbn, title, author, category, qr_code, total_copies, available_copies, shelf_location) VALUES
-- CS101: Introduction to Programming
('9780133761313', 'Introduction to Java Programming', 'Y. Daniel Liang', 'Computer Science', 'QR-CS101-01', 5, 4, 'A-01'),
('9780134444321', 'Starting Out with C++', 'Tony Gaddis', 'Computer Science', 'QR-CS101-02', 4, 3, 'A-02'),

-- CS202: Database Management Systems
('9780136086208', 'Database System Concepts', 'Abraham Silberschatz', 'Computer Science', 'QR-CS202-01', 3, 2, 'A-05'),
('9780133970777', 'Fundamentals of Database Systems', 'Ramez Elmasri', 'Computer Science', 'QR-CS202-02', 4, 4, 'A-06'),

-- CS305: Operating Systems
('9781118063330', 'Operating System Concepts', 'Abraham Silberschatz', 'Computer Science', 'QR-CS305-01', 5, 3, 'B-03'),
('9780133591620', 'Modern Operating Systems', 'Andrew S. Tanenbaum', 'Computer Science', 'QR-CS305-02', 3, 2, 'B-04'),

-- CS420: Artificial Intelligence
('9780134610993', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'Computer Science', 'QR-CS420-01', 4, 2, 'B-08'),
('9781119549048', 'Machine Learning for Dummies', 'John Paul Mueller', 'Computer Science', 'QR-CS420-02', 2, 2, 'B-09'),

-- EC101: Basic Electronics
('9780132197076', 'Electronic Devices and Circuit Theory', 'Robert Boylestad', 'Electronics', 'QR-EC101-01', 6, 5, 'C-01'),
('9789352834839', 'Principles of Electronics', 'V.K. Mehta', 'Electronics', 'QR-EC101-02', 5, 4, 'C-02'),

-- EC302: Microprocessors & Microcontrollers
('9788131732427', 'Microprocessor Architecture with the 8085', 'Ramesh Gaonkar', 'Electronics', 'QR-EC302-01', 3, 1, 'C-05'),
('9780132344142', 'The 8051 Microcontroller & Embedded Systems', 'Muhammad Ali Mazidi', 'Electronics', 'QR-EC302-02', 4, 3, 'C-06'),

-- EC405: Embedded Systems
('9780070667648', 'Embedded Systems: Architecture and Design', 'Raj Kamal', 'Electronics', 'QR-EC405-01', 3, 3, 'C-09'),
('9780070145894', 'Introduction to Embedded Systems', 'Shibu K.V', 'Electronics', 'QR-EC405-02', 5, 2, 'C-10'),

-- MA101: Calculus & Linear Algebra
('9788174091955', 'Higher Engineering Mathematics', 'B.S. Grewal', 'Mathematics', 'QR-MA101-01', 8, 6, 'D-01'),
('9780321588760', 'Thomas Calculus', 'George B. Thomas', 'Mathematics', 'QR-MA101-02', 6, 4, 'D-02'),

-- MA204: Discrete Mathematics
('9780073383095', 'Discrete Mathematics and Its Applications', 'Kenneth H. Rosen', 'Mathematics', 'QR-MA204-01', 5, 5, 'D-05'),
('9780074624708', 'Elements of Discrete Mathematics', 'C.L. Liu', 'Mathematics', 'QR-MA204-02', 3, 2, 'D-06'),

-- ME102: Engineering Mechanics
('9780133918922', 'Engineering Mechanics: Statics & Dynamics', 'R.C. Hibbeler', 'Mechanical', 'QR-ME102-01', 4, 3, 'E-01'),
('9788121926188', 'A Textbook of Engineering Mechanics', 'R.S. Khurmi', 'Mechanical', 'QR-ME102-02', 5, 5, 'E-02'),

-- ME301: Thermodynamics
('9780073398174', 'Thermodynamics: An Engineering Approach', 'Yunus A. Çengel', 'Mechanical', 'QR-ME301-01', 4, 2, 'E-05'),
('9789352606429', 'Engineering Thermodynamics', 'P.K. Nag', 'Mechanical', 'QR-ME301-02', 6, 4, 'E-06'),

-- PH101: Engineering Physics
('9789352533916', 'A Textbook of Engineering Physics', 'M.N. Avadhanulu', 'Physics', 'QR-PH101-01', 5, 4, 'F-01'),
('9780070495531', 'Concepts of Modern Physics', 'Arthur Beiser', 'Physics', 'QR-PH101-02', 4, 3, 'F-02');


INSERT INTO placement_drives (company_name, job_title, description, min_cgpa, package_lpa, drive_date, status) VALUES
-- Tier 1 Tech / Product
('Tata Consultancy Services', 'Systems Engineer (Digital)', 'Full-stack application engineering and cloud services', 6.50, 7.00, '2026-03-10', 'completed'),
('Infosys Limited', 'Specialist Programmer', 'High-performance computing and enterprise automation', 6.00, 9.50, '2026-03-15', 'completed'),
('Wipro Limited', 'Turbo Engineer', 'Advanced application development and cybersecurity modules', 6.00, 6.50, '2026-03-22', 'completed'),
('Amazon India', 'Software Development Engineer I', 'Scalable distributed systems, AWS infrastructure backend', 7.50, 32.00, '2026-04-05', 'completed'),
('Google India', 'Associate Software Engineer', 'Core engineering, infrastructure optimization, and tools development', 8.00, 38.00, '2026-04-12', 'ongoing'),
('Microsoft India', 'Support Engineer', 'Enterprise cloud troubleshooting and customer success systems', 7.00, 16.00, '2026-04-18', 'upcoming'),

-- FinTech / Banking
('Goldman Sachs', 'Engineering Analyst', 'Quantitative analysis, risk modeling infrastructure, and financial tech', 7.75, 24.00, '2026-04-25', 'upcoming'),
('JPMorgan Chase & Co.', 'Software Engineer', 'Global banking platforms, cybersecurity, and digital transactions', 7.00, 14.00, '2026-04-28', 'upcoming'),
('HDFC Bank', 'Data Analyst', 'Credit risk assessment and retail banking data pipelines', 6.50, 8.50, '2026-05-05', 'upcoming'),

-- Consulting / Global Capability Centers
('Accenture India', 'Advanced App Engineering Analyst', 'Client-side cloud custom development and agile architecture', 6.50, 6.50, '2026-05-10', 'upcoming'),
('Deloitte India', 'Technology Consultant', 'Enterprise resource planning and cybersecurity advisory services', 6.75, 8.20, '2026-05-15', 'upcoming'),
('Capgemini', 'Senior Analyst', 'Digital transformation, cloud microservices, and UI/UX design', 6.00, 4.25, '2026-05-20', 'upcoming'),

-- Semi / Core / Networks
('Intel India', 'Silicon Architecture Intern', 'VLSI design, hardware description languages, and chip verification', 8.00, 18.00, '2026-05-25', 'upcoming'),
('Cisco Systems', 'Network Engineer', 'Enterprise routing, switching fabric, and software-defined networks', 7.00, 15.00, '2026-06-02', 'upcoming'),

-- High-growth Startups / E-commerce
('Flipkart', 'SDE-1', 'Supply chain automation engines and high-throughput web architectures', 7.50, 26.00, '2026-06-10', 'upcoming');


