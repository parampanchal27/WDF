CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studenthub;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    course VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(150) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrations (
    registration_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_registrations_student
        FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_student_event UNIQUE (student_id, event_id)
) ENGINE=InnoDB;

INSERT INTO students
    (full_name, username, email, mobile, course, semester, password_hash)
VALUES
    ('Param Panchal', 'param2026', 'param.panchal@studenthub.edu', '+919876543210',
     'B.Tech Computer Science', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCzUqkCqv0H8Q0N7lT6'),
    ('Asha Patel', 'asha2026', 'asha@example.com', '9876543210',
     'B.Tech Information Technology', 3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCzUqkCqv0H8Q0N7lT6')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO events (title, description, event_date, location, capacity)
SELECT 'Web Development Workshop', 'Hands-on HTML, CSS, JavaScript, and PHP workshop.', '2026-10-20 10:00:00', 'Computer Lab 1', 40
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Web Development Workshop');

INSERT INTO events (title, description, event_date, location, capacity)
SELECT 'StudentHub Hackathon', 'Build a useful student-focused web application in teams.', '2026-11-05 09:00:00', 'Innovation Hall', 60
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'StudentHub Hackathon');

INSERT INTO events (title, description, event_date, location, capacity)
SELECT 'Technical Seminar', 'A seminar on secure database-backed web applications.', '2026-11-18 14:00:00', 'Seminar Room A', 100
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Technical Seminar');
