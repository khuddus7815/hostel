-- new database 

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS block (
    block_id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    block_name VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS student (
    student_id INT PRIMARY KEY NOT NULL,
    block_id INT,
    usn VARCHAR(255),
    room VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (block_id) REFERENCES block(block_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS warden (
    warden_id INT PRIMARY KEY NOT NULL,
    block_id INT,
    FOREIGN KEY (warden_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (block_id) REFERENCES block(block_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS complaint (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    block_id INT,
    student_id INT,
    description TEXT,
    room VARCHAR(255),
    is_completed BOOLEAN,
    created_at DATETIME,
    assigned_at DATETIME,
    FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
    FOREIGN KEY (block_id) REFERENCES block(block_id) ON DELETE CASCADE
);