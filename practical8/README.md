# Practical 8: StudentHub MySQL Integration

This module connects StudentHub to MySQL using a normalized schema, PDO, and prepared statements.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`.
3. Import `studenthub.sql`.
4. Test the connection at `http://localhost/practical8/test_connection.php`.
5. Log in through StudentHub, then open `http://localhost/practical8/events.php`.

The schema contains:

- `students`: StudentHub student accounts.
- `events`: Available StudentHub events.
- `registrations`: Many-to-many bridge table between students and events.

`student_id` and `event_id` are foreign keys. The unique `(student_id, event_id)` constraint prevents duplicate event registrations.
