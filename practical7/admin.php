<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
startStudentHubSession();
requireStudentHubLogin();
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('You do not have permission to access the admin dashboard.');
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub - Admin Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<header class="header"><h1>StudentHub Admin</h1><nav class="indexnav"><a href="../Index.php">Student dashboard</a><a href="logout.php">Logout</a></nav></header>
<main id="main-content">
    <section class="dashboard-hero"><div><h2>Admin dashboard</h2><p>Manage students and campus events without leaving this page.</p></div><span class="date-label">Signed in as <?= htmlspecialchars($_SESSION['user']['fullName'], ENT_QUOTES, 'UTF-8') ?></span></section>
    <div id="message" class="admin-message" role="status" hidden></div>
    <section class="overview-grid" aria-label="Statistics">
        <article class="overview-card"><p>Total users</p><div class="metric" id="stat-users">-</div></article>
        <article class="overview-card"><p>Students</p><div class="metric" id="stat-students">-</div></article>
        <article class="overview-card"><p>Events</p><div class="metric" id="stat-events">-</div></article>
    </section>
    <section class="admin-panel">
        <div class="section-heading"><h3>Student management</h3><button class="admin-button" id="student-new" type="button">Add student</button></div>
        <div class="data-controls"><label class="data-search-wrap">Search<input id="student-search" type="search" placeholder="Name, username, or email"></label><label class="data-filter-wrap">Course<select id="student-course"><option value="">All courses</option></select></label></div>
        <div class="table-wrapper"><table><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Course</th><th>Semester</th><th>Actions</th></tr></thead><tbody id="student-rows"></tbody></table></div>
    </section>
    <section class="admin-panel">
        <div class="section-heading"><h3>Event management</h3><button class="admin-button" id="event-new" type="button">Add event</button></div>
        <div class="table-wrapper"><table><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Seats</th><th>Poster</th><th>Actions</th></tr></thead><tbody id="event-rows"></tbody></table></div>
    </section>
    <section class="admin-panel"><h3>Latest registrations</h3><ul id="latest-registrations"><li>Loading...</li></ul></section>
</main>
<dialog id="editor"><form id="editor-form" method="post" enctype="multipart/form-data"><h2 id="editor-title"></h2><input type="hidden" name="entity_id"><input type="hidden" name="action">
    <div id="student-fields"><label>Full name<input name="full_name" maxlength="100" required></label><label>Username<input name="username" maxlength="30" required></label><label>Email<input name="email" type="email" maxlength="150" required></label><label>Mobile<input name="mobile" maxlength="15" required></label><label>Course<input name="course" maxlength="100" required></label><label>Semester<input name="semester" type="number" min="1" max="12" required></label></div>
    <div id="event-fields" hidden><label>Title<input name="title" maxlength="150" required></label><label>Description<textarea name="description" maxlength="2000" required></textarea></label><label>Date and time<input name="event_date" type="datetime-local" required></label><label>Location<input name="location" maxlength="150" required></label><label>Capacity<input name="capacity" type="number" min="1" required></label><label>Poster (JPG, PNG, WEBP; max 2 MB)<input name="poster" type="file" accept=".jpg,.jpeg,.png,.webp"></label></div>
    <div class="admin-form-actions"><button class="admin-button" type="submit">Save</button><button class="admin-button secondary" id="editor-cancel" type="button">Cancel</button></div>
</form></dialog>
<script>
const api = 'api.php';
const csrf = <?= json_encode($_SESSION['csrf_token']) ?>;
const message = document.querySelector('#message'), form = document.querySelector('#editor-form'), editor = document.querySelector('#editor');
let students = [], events = [];
function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; }
function showMessage(text, error = false) { message.textContent = text; message.className = 'admin-message ' + (error ? 'error' : 'success'); message.hidden = false; setTimeout(() => message.hidden = true, 4500); }
async function request(url, options = {}) { options.headers = {...(options.headers || {}), 'X-CSRF-Token': csrf}; const response = await fetch(url, options); const data = await response.json(); if (!response.ok || !data.success) throw new Error(data.message || 'Request failed.'); return data; }
async function loadStats() { const data = await request(api + '?resource=stats'); ['users', 'students', 'events'].forEach(key => document.querySelector('#stat-' + key).textContent = data.stats[key]); document.querySelector('#latest-registrations').innerHTML = data.latest.length ? data.latest.map(row => `<li><strong>${escapeHtml(row.full_name)}</strong> · ${escapeHtml(row.event_title)}<br><small>${escapeHtml(row.registered_at)}</small></li>`).join('') : '<li>No registrations yet.</li>'; }
async function loadStudents() { const params = new URLSearchParams({resource: 'students', search: document.querySelector('#student-search').value, course: document.querySelector('#student-course').value}); const data = await request(api + '?' + params); students = data.students; const selected = document.querySelector('#student-course').value; const courses = [...new Set(students.map(row => row.course))]; document.querySelector('#student-course').innerHTML = '<option value="">All courses</option>' + courses.map(course => `<option value="${escapeHtml(course)}">${escapeHtml(course)}</option>`).join(''); document.querySelector('#student-course').value = selected; document.querySelector('#student-rows').innerHTML = students.length ? students.map(row => `<tr><td>${escapeHtml(row.full_name)}</td><td>${escapeHtml(row.username)}</td><td>${escapeHtml(row.email)}</td><td>${escapeHtml(row.course)}</td><td>${row.semester}</td><td><button class="table-action" data-edit-student="${row.student_id}">Edit</button> <button class="table-action danger" data-delete-student="${row.student_id}">Delete</button></td></tr>`).join('') : '<tr><td colspan="6">No students found.</td></tr>'; }
async function loadEvents() { const data = await request(api + '?resource=events'); events = data.events; document.querySelector('#event-rows').innerHTML = events.length ? events.map(row => `<tr><td><strong>${escapeHtml(row.title)}</strong><br>${escapeHtml(row.description)}</td><td>${escapeHtml(row.event_date)}</td><td>${escapeHtml(row.location)}</td><td>${row.registered_count} / ${row.capacity}</td><td>${row.poster_path ? '<img class="poster-thumb" src="' + escapeHtml(row.poster_path) + '" alt="Event poster">' : 'None'}</td><td><button class="table-action" data-edit-event="${row.event_id}">Edit</button> <button class="table-action danger" data-delete-event="${row.event_id}">Delete</button></td></tr>`).join('') : '<tr><td colspan="6">No events found.</td></tr>'; }
function openEditor(entity, row = {}) { form.reset(); form.elements.action.value = entity === 'student' ? (row.student_id ? 'update_student' : 'create_student') : (row.event_id ? 'update_event' : 'create_event'); form.elements.entity_id.name = entity === 'student' ? 'student_id' : 'event_id'; form.elements.entity_id.value = row.student_id || row.event_id || ''; document.querySelector('#student-fields').hidden = entity !== 'student'; document.querySelector('#event-fields').hidden = entity !== 'event'; document.querySelector('#editor-title').textContent = (row.student_id || row.event_id) ? 'Edit ' + entity : 'Add ' + entity; Object.keys(row).forEach(key => { if (form.elements[key] && key !== 'poster_path') form.elements[key].value = key === 'event_date' ? row[key].replace(' ', 'T').slice(0, 16) : row[key]; }); editor.showModal(); }
form.addEventListener('submit', async event => { event.preventDefault(); try { const data = await request(api, {method: 'POST', body: new FormData(form)}); editor.close(); showMessage(data.message); await Promise.all([loadStats(), loadStudents(), loadEvents()]); } catch (error) { showMessage(error.message, true); }});
document.querySelector('#student-new').onclick = () => openEditor('student'); document.querySelector('#event-new').onclick = () => openEditor('event'); document.querySelector('#editor-cancel').onclick = () => editor.close(); document.querySelector('#student-search').oninput = loadStudents; document.querySelector('#student-course').onchange = loadStudents;
document.addEventListener('click', async event => { const target = event.target; try { if (target.dataset.editStudent) openEditor('student', students.find(row => row.student_id == target.dataset.editStudent)); if (target.dataset.editEvent) openEditor('event', events.find(row => row.event_id == target.dataset.editEvent)); const type = target.dataset.deleteStudent ? 'student' : target.dataset.deleteEvent ? 'event' : ''; const id = target.dataset.deleteStudent || target.dataset.deleteEvent; if (type && confirm('Delete this ' + type + '?')) { const data = await request(api, {method: 'POST', body: new URLSearchParams({action: 'delete_' + type, [type + '_id']: id})}); showMessage(data.message); await Promise.all([loadStats(), loadStudents(), loadEvents()]); } } catch (error) { showMessage(error.message, true); }});
Promise.all([loadStats(), loadStudents(), loadEvents()]).catch(error => showMessage(error.message, true));
</script>
</body>
</html>
