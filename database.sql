-- =========================================================
-- Database NextIntern - Aktor Admin Sistem
-- Untuk project PHP + MySQL
-- Login demo Admin Sistem:
-- Email    : sysadmin@test.com
-- Password : password
-- Role     : admin_website
-- =========================================================

DROP DATABASE IF EXISTS nextintern_db;
CREATE DATABASE nextintern_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nextintern_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS admin_menu_items;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS active_interns;
DROP TABLE IF EXISTS application_documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS internship_fields;
DROP TABLE IF EXISTS internship_requirements;
DROP TABLE IF EXISTS internships;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. ROLE & USER LOGIN
-- =========================================================

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_key VARCHAR(50) NOT NULL UNIQUE,
  role_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  avatar VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nim VARCHAR(50) NULL,
  university VARCHAR(150) NOT NULL,
  study_program VARCHAR(150) NULL,
  semester INT NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  CONSTRAINT fk_students_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 2. DATA PERUSAHAAN
-- =========================================================

CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  industry VARCHAR(100) NOT NULL,
  location VARCHAR(120) NOT NULL,
  description TEXT NULL,
  logo VARCHAR(255) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  partnership_since DATE NULL,
  partnership_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3. DATA LOWONGAN
-- =========================================================

CREATE TABLE internships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NULL,
  position VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(120) NOT NULL,
  duration VARCHAR(50) NOT NULL,
  deadline DATE NOT NULL,
  slots INT NOT NULL DEFAULT 1,
  posted_date DATE NOT NULL,
  status ENUM('active','closed','draft') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_internships_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE internship_requirements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  internship_id INT NOT NULL,
  requirement_text VARCHAR(255) NOT NULL,
  CONSTRAINT fk_requirements_internship
    FOREIGN KEY (internship_id) REFERENCES internships(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE internship_fields (
  id INT AUTO_INCREMENT PRIMARY KEY,
  internship_id INT NOT NULL,
  field_name VARCHAR(100) NOT NULL,
  CONSTRAINT fk_fields_internship
    FOREIGN KEY (internship_id) REFERENCES internships(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 4. DATA LAMARAN
-- =========================================================

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NULL,
  student_name VARCHAR(150) NOT NULL,
  internship_id INT NULL,
  position VARCHAR(180) NOT NULL,
  company_id INT NULL,
  company_name VARCHAR(180) NOT NULL,
  status ENUM(
    'pending',
    'university_review',
    'university_approved',
    'admin_approved',
    'company_review',
    'accepted',
    'rejected'
  ) NOT NULL DEFAULT 'pending',
  applied_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_applications_student
    FOREIGN KEY (student_id) REFERENCES students(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_applications_internship
    FOREIGN KEY (internship_id) REFERENCES internships(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_applications_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE application_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  document_type ENUM('cv','transcript','cover_letter','portfolio','surat_pengantar','other') NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_application_documents_application
    FOREIGN KEY (application_id) REFERENCES applications(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. DATA MAGANG AKTIF
-- =========================================================

CREATE TABLE active_interns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NULL,
  student_name VARCHAR(150) NOT NULL,
  university VARCHAR(150) NOT NULL,
  company_id INT NULL,
  company_name VARCHAR(180) NOT NULL,
  internship_id INT NULL,
  position VARCHAR(180) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('ongoing','completed','terminated') NOT NULL DEFAULT 'ongoing',
  progress INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_active_interns_student
    FOREIGN KEY (student_id) REFERENCES students(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_active_interns_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_active_interns_internship
    FOREIGN KEY (internship_id) REFERENCES internships(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT chk_active_progress CHECK (progress BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- =========================================================
-- 6. DATA SERTIFIKAT
-- =========================================================

CREATE TABLE certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  active_intern_id INT NULL,
  student_name VARCHAR(150) NOT NULL,
  university VARCHAR(150) NOT NULL,
  company_name VARCHAR(180) NOT NULL,
  position VARCHAR(180) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  issued_date DATE NULL,
  certificate_number VARCHAR(80) NOT NULL UNIQUE,
  status ENUM('issued','pending','revoked') NOT NULL DEFAULT 'pending',
  file_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_certificates_active_intern
    FOREIGN KEY (active_intern_id) REFERENCES active_interns(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 7. DATA DOKUMEN
-- =========================================================

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file_name VARCHAR(255) NOT NULL,
  document_type VARCHAR(100) NOT NULL,
  student_name VARCHAR(150) NOT NULL,
  company_name VARCHAR(180) NOT NULL,
  upload_date DATE NOT NULL,
  file_size VARCHAR(50) NOT NULL,
  status ENUM('verified','pending','rejected') NOT NULL DEFAULT 'pending',
  file_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 8. MENU ADMIN SISTEM
-- Sesuai menu aktif desain Admin Sistem:
-- Beranda, Data Pengguna, Data Perusahaan, Data Lowongan,
-- Data Lamaran, Data Magang Aktif, Data Sertifikat, Data Dokumen
-- =========================================================

CREATE TABLE admin_menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(100) NOT NULL,
  route VARCHAR(150) NOT NULL UNIQUE,
  icon VARCHAR(50) NULL,
  sort_order INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  table_name VARCHAR(100) NULL,
  record_id VARCHAR(100) NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- Password semua akun demo = password
-- Hash di bawah kompatibel dengan password_verify() PHP.
-- =========================================================

INSERT INTO roles (id, role_key, role_name) VALUES
(1, 'mahasiswa', 'Mahasiswa'),
(2, 'perusahaan', 'Perusahaan'),
(3, 'admin_universitas', 'Admin Universitas'),
(4, 'admin_website', 'Admin Sistem');

INSERT INTO users (id, name, email, password_hash, role_id, status, created_at) VALUES
(1, 'Ahmad Fauzi', 'mahasiswa@test.com', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2024-01-15 08:00:00'),
(2, 'PT Tech Indonesia', 'perusahaan@test.com', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 2, 'active', '2024-02-01 08:00:00'),
(3, 'Admin Universitas', 'admin@test.com', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 3, 'active', '2023-12-01 08:00:00'),
(4, 'System Admin', 'sysadmin@test.com', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 4, 'active', '2023-11-01 08:00:00'),
(5, 'Budi Santoso', 'budi@student.test', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2026-01-01 08:00:00'),
(6, 'Siti Rahayu', 'siti@student.test', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2026-01-02 08:00:00'),
(7, 'Ahmad Wijaya', 'ahmadwijaya@student.test', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2026-01-03 08:00:00'),
(8, 'Dewi Lestari', 'dewi@student.test', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2026-01-04 08:00:00'),
(9, 'Rina Putri', 'rina@student.test', '$2y$10$WuEvXgu5YG0T6BV3EucYkO9EawrVYmb/FkKjdHQr1UAXP7pOQqX1G', 1, 'active', '2026-01-05 08:00:00');

INSERT INTO students (id, user_id, nim, university, study_program, semester) VALUES
(1, 1, 'MHS001', 'Universitas Indonesia', 'Informatika', 6),
(2, 5, 'MHS002', 'Universitas Indonesia', 'Informatika', 6),
(3, 6, 'MHS003', 'Institut Teknologi Bandung', 'Desain Komunikasi Visual', 6),
(4, 7, 'MHS004', 'Universitas Gadjah Mada', 'Teknik Komputer', 7),
(5, 8, 'MHS005', 'Universitas Brawijaya', 'Statistika', 6),
(6, 9, 'MHS006', 'Universitas Indonesia', 'Sistem Informasi', 6);

INSERT INTO companies (id, name, email, industry, location, description, is_verified, partnership_since, partnership_status) VALUES
(1, 'PT Teknologi Digital Indonesia', 'hr@tekdigital.co.id', 'Technology', 'Jakarta', 'Leading technology company focusing on digital transformation solutions.', 1, '2023-01-15', 'active'),
(2, 'Bank Mandiri', 'recruitment@bankmandiri.co.id', 'Banking & Finance', 'Jakarta', 'One of Indonesia''s largest state-owned banks.', 1, '2022-06-01', 'active'),
(3, 'Tokopedia', 'careers@tokopedia.com', 'E-commerce', 'Jakarta', 'Indonesia''s leading marketplace platform.', 1, '2023-03-20', 'active'),
(4, 'Gojek', 'talent@gojek.com', 'Technology', 'Jakarta', 'Super app providing on-demand services.', 1, '2023-08-10', 'active'),
(5, 'PT Tech Innovators', 'hr@techinnovators.test', 'Technology', 'Jakarta', 'Perusahaan teknologi untuk pengembangan aplikasi web dan mobile.', 1, '2024-01-10', 'active'),
(6, 'PT Digital Solutions', 'hr@digitalsolutions.test', 'Technology', 'Bandung', 'Perusahaan solusi digital dan desain produk.', 1, '2024-02-15', 'active'),
(7, 'PT Creative Studio', 'hr@creativestudio.test', 'Creative Technology', 'Yogyakarta', 'Studio kreatif untuk pengembangan backend dan produk digital.', 1, '2024-03-01', 'active'),
(8, 'PT Startup Hub', 'hr@startuphub.test', 'Startup Incubator', 'Malang', 'Inkubator startup dengan fokus data dan bisnis digital.', 1, '2024-04-20', 'active');

INSERT INTO internships (id, company_id, position, description, location, duration, deadline, slots, posted_date, status) VALUES
(1, 1, 'Frontend Developer Intern', 'Kami mencari mahasiswa yang passionate di bidang frontend development untuk bergabung dengan tim kami.', 'Jakarta', '6 bulan', '2026-04-30', 3, '2026-03-01', 'active'),
(2, 2, 'Data Analyst Intern', 'Kesempatan magang di divisi Business Intelligence untuk menganalisis data perbankan.', 'Jakarta', '3 bulan', '2026-04-15', 2, '2026-02-28', 'active'),
(3, 3, 'Mobile Developer Intern', 'Join our mobile team to build features used by millions of users across Indonesia.', 'Jakarta', '6 bulan', '2026-05-01', 4, '2026-03-05', 'active'),
(4, 4, 'UI/UX Design Intern', 'Help us design delightful experiences for our users across various product lines.', 'Jakarta', '4 bulan', '2026-04-20', 2, '2026-03-10', 'active');

INSERT INTO internship_requirements (internship_id, requirement_text) VALUES
(1, 'Mahasiswa aktif semester 5 atau lebih'),
(1, 'Menguasai React.js dan TypeScript'),
(1, 'Memahami konsep responsive design'),
(1, 'Mampu bekerja dalam tim'),
(2, 'IPK minimal 3.0'),
(2, 'Menguasai SQL dan Python'),
(2, 'Familiar dengan tools visualisasi data'),
(2, 'Kemampuan analisis yang baik'),
(3, 'Strong knowledge in Kotlin or Swift'),
(3, 'Understanding of mobile app architecture'),
(3, 'Experience with RESTful APIs'),
(3, 'Portfolio of mobile projects'),
(4, 'Proficient in Figma'),
(4, 'Understanding of design principles'),
(4, 'Portfolio demonstrating design skills'),
(4, 'Good communication skills');

INSERT INTO internship_fields (internship_id, field_name) VALUES
(1, 'Informatika'),
(1, 'Sistem Informasi'),
(1, 'Teknik Komputer'),
(2, 'Informatika'),
(2, 'Sistem Informasi'),
(2, 'Statistika'),
(3, 'Informatika'),
(3, 'Teknik Komputer'),
(4, 'Desain Komunikasi Visual'),
(4, 'Informatika'),
(4, 'Sistem Informasi');

INSERT INTO applications (id, student_id, student_name, internship_id, position, company_id, company_name, status, applied_date) VALUES
(1, 1, 'Ahmad Fauzi', 1, 'Frontend Developer Intern', 1, 'PT Teknologi Digital Indonesia', 'university_review', '2026-03-15'),
(2, 2, 'Budi Santoso', 1, 'Frontend Developer Intern', 5, 'PT Tech Innovators', 'pending', '2026-04-10'),
(3, 3, 'Siti Rahayu', 4, 'UI/UX Designer Intern', 6, 'PT Digital Solutions', 'company_review', '2026-04-12'),
(4, 4, 'Ahmad Wijaya', 1, 'Backend Developer Intern', 7, 'PT Creative Studio', 'accepted', '2026-04-11'),
(5, 5, 'Dewi Lestari', 2, 'Data Analyst Intern', 8, 'PT Startup Hub', 'rejected', '2026-04-14');

INSERT INTO application_documents (application_id, document_type, file_name) VALUES
(1, 'cv', 'cv_ahmad.pdf'),
(1, 'transcript', 'transcript_ahmad.pdf'),
(1, 'cover_letter', 'cover_letter.pdf'),
(2, 'cv', 'CV_BudiSantoso.pdf'),
(2, 'surat_pengantar', 'SuratPengantar_BudiSantoso.pdf'),
(3, 'portfolio', 'Portfolio_SitiRahayu.pdf'),
(4, 'transcript', 'Transkrip_AhmadWijaya.pdf'),
(5, 'cv', 'CV_DewiLestari.pdf');

INSERT INTO active_interns (id, student_id, student_name, university, company_id, company_name, internship_id, position, start_date, end_date, status, progress) VALUES
(1, 2, 'Budi Santoso', 'Universitas Indonesia', 5, 'PT Tech Innovators', 1, 'Frontend Developer Intern', '2026-01-15', '2026-04-15', 'ongoing', 75),
(2, 3, 'Siti Rahayu', 'Institut Teknologi Bandung', 6, 'PT Digital Solutions', 4, 'UI/UX Designer Intern', '2026-02-01', '2026-05-01', 'ongoing', 60),
(3, 4, 'Ahmad Wijaya', 'Universitas Gadjah Mada', 7, 'PT Creative Studio', NULL, 'Backend Developer Intern', '2025-11-01', '2026-02-01', 'completed', 100),
(4, 5, 'Dewi Lestari', 'Universitas Brawijaya', 8, 'PT Startup Hub', 2, 'Data Analyst Intern', '2026-03-01', '2026-06-01', 'ongoing', 40);

INSERT INTO certificates (id, active_intern_id, student_name, university, company_name, position, start_date, end_date, issued_date, certificate_number, status) VALUES
(1, 3, 'Ahmad Wijaya', 'Universitas Gadjah Mada', 'PT Creative Studio', 'Backend Developer Intern', '2025-11-01', '2026-02-01', '2026-02-05', 'CERT-2026-001', 'issued'),
(2, NULL, 'Rina Putri', 'Universitas Indonesia', 'PT Tech Innovators', 'Frontend Developer Intern', '2025-10-01', '2026-01-01', '2026-01-10', 'CERT-2026-002', 'issued'),
(3, 1, 'Budi Santoso', 'Universitas Indonesia', 'PT Tech Innovators', 'Frontend Developer Intern', '2026-01-15', '2026-04-15', NULL, 'CERT-2026-003', 'pending');

INSERT INTO documents (id, file_name, document_type, student_name, company_name, upload_date, file_size, status) VALUES
(1, 'CV_BudiSantoso.pdf', 'CV', 'Budi Santoso', 'PT Tech Innovators', '2026-04-10', '245 KB', 'verified'),
(2, 'SuratPengantar_BudiSantoso.pdf', 'Surat Pengantar', 'Budi Santoso', 'PT Tech Innovators', '2026-04-10', '189 KB', 'verified'),
(3, 'Portfolio_SitiRahayu.pdf', 'Portfolio', 'Siti Rahayu', 'PT Digital Solutions', '2026-04-12', '1.2 MB', 'pending'),
(4, 'Transkrip_AhmadWijaya.pdf', 'Transkrip Nilai', 'Ahmad Wijaya', 'PT Creative Studio', '2026-04-11', '321 KB', 'verified'),
(5, 'CV_DewiLestari.pdf', 'CV', 'Dewi Lestari', 'PT Startup Hub', '2026-04-14', '298 KB', 'pending');

INSERT INTO admin_menu_items (label, route, icon, sort_order, is_active) VALUES
('Beranda', '/sysadmin/dashboard', 'home', 1, 1),
('Data Pengguna', '/sysadmin/users', 'users', 2, 1),
('Data Perusahaan', '/sysadmin/companies', 'database', 3, 1),
('Data Lowongan', '/sysadmin/internships', 'briefcase', 4, 1),
('Data Lamaran', '/sysadmin/applications', 'file-text', 5, 1),
('Data Magang Aktif', '/sysadmin/active-interns', 'activity', 6, 1),
('Data Sertifikat', '/sysadmin/certificates', 'award', 7, 1),
('Data Dokumen', '/sysadmin/documents', 'file-text', 8, 1);

INSERT INTO audit_logs (user_id, action, table_name, record_id, description) VALUES
(4, 'LOGIN_READY', 'users', '4', 'Akun Admin Sistem tersedia untuk login demo.'),
(4, 'DATABASE_SEEDED', NULL, NULL, 'Database awal NextIntern berhasil dibuat.');

-- =========================================================
-- VIEW RINGKAS UNTUK DASHBOARD ADMIN SISTEM
-- =========================================================

CREATE OR REPLACE VIEW v_sysadmin_dashboard_stats AS
SELECT
  (SELECT COUNT(*) FROM users WHERE status = 'active') AS total_pengguna_aktif,
  (SELECT COUNT(*) FROM companies) AS total_perusahaan,
  (SELECT COUNT(*) FROM internships) AS total_lowongan,
  (SELECT COUNT(*) FROM applications) AS total_lamaran,
  (SELECT COUNT(*) FROM active_interns WHERE status = 'ongoing') AS magang_aktif,
  (SELECT COUNT(*) FROM certificates) AS total_sertifikat,
  (SELECT COUNT(*) FROM documents) AS total_dokumen,
  'Online' AS status_sistem,
  'Normal' AS status_database,
  'Aman' AS status_keamanan;

-- Cek hasil:
-- SELECT * FROM v_sysadmin_dashboard_stats;
-- SELECT u.id, u.name, u.email, r.role_key, r.role_name, u.status FROM users u JOIN roles r ON r.id = u.role_id;
