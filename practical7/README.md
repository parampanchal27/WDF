# Practical 7: PHP Form Processing

This practical demonstrates PHP `POST` handling, server-side validation, input sanitization, safe JSON storage, output escaping, success/error messages, and CSRF token validation.

## Run with XAMPP

1. Copy this `practical7` folder into `C:\xampp\htdocs\`.
2. Start **Apache** from the XAMPP Control Panel.
3. Open `http://localhost/practical7/` in a browser.
4. Submit a valid form and open **View stored records**.

MySQL is not required because the application stores records in `data/submissions.json`.

## Files

- `index.php`: Form and validation-error display.
- `process.php`: POST check, sanitization, validation, CSRF verification, and JSON writing.
- `success.php`: Success response after a saved submission.
- `records.php`: Reads and displays stored JSON records safely.
- `data/submissions.json`: Generated JSON storage file.
- `register.php`: Processes StudentHub registration and stores hashed passwords.
- `login.php`: Verifies StudentHub credentials and creates a PHP session.
- `data/users.json`: StudentHub account storage with password hashes, not plain-text passwords.
- `data/.htaccess`: Prevents direct browser access to stored submissions.

## Test cases

| Test | Input | Expected result |
| --- | --- | --- |
| Valid submission | Valid name, email, phone, and message | Record is saved in JSON and success page is shown |
| Empty name | Leave name blank | Name error is displayed and nothing is saved |
| Invalid email | `abc.com` | Email error is displayed |
| Invalid phone | `12abc` | Phone error is displayed |
| Empty message | Leave message blank | Message error is displayed |
| Long message | More than 1000 characters | Length error is displayed |
| HTML input | `<script>alert('test')</script>` in a text field | Input is escaped when displayed |
| Direct processor request | Open `process.php` with GET | Method error is returned |
| Invalid CSRF token | Alter the hidden token | Request is rejected with status 403 |

## StudentHub integration

The existing root `register.html` now submits to `practical7/register.php`, and `Login.html` submits to `practical7/login.php`. Registration data is validated on the server, and passwords are stored using `password_hash()`. Login uses `password_verify()` and creates a PHP session.
