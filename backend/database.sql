-- SQLite Database Schema for Hostel Complaint Management System

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    password TEXT NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('student', 'warden')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Student table
CREATE TABLE IF NOT EXISTS student (
    student_id INTEGER PRIMARY KEY,
    block_id INTEGER,
    usn TEXT,
    room TEXT,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Warden table
CREATE TABLE IF NOT EXISTS warden (
    warden_id INTEGER PRIMARY KEY,
    block_id INTEGER,
    FOREIGN KEY (warden_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    user_id INTEGER NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'resolved', 'rejected')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sample data
INSERT OR IGNORE INTO users (user_id, full_name, email, phone, password, type) VALUES
(1, 'Test Student', 'student@test.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
(2, 'Test Warden', 'warden@test.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warden');

INSERT OR IGNORE INTO student (student_id, block_id, usn, room) VALUES
(1, 1, 'USN001', '101');

INSERT OR IGNORE INTO warden (warden_id, block_id) VALUES
(2, 1);
