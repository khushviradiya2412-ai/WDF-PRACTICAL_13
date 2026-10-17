<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub — Practical 13: Fetch CRUD (JSON API)</title>
  <!-- Bootstrap 5 via CDN (required by spec) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Shared StudentHub theme copied from PRACTICAL 7 -->
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#2c3e50;">
  <div class="container">
    <a class="navbar-brand" href="#">StudentHub · P13 API + Fetch</a>
    <span class="navbar-text text-light small">PHP JSON API · No page reload</span>
  </div>
</nav>

<div class="container py-4" style="max-width:1100px;">
  <h1 class="h3">Students — Fetch CRUD without reload</h1>
  <p class="text-muted">Data comes from <code>api/students.php</code> as JSON. All add / edit / delete actions use <code>fetch()</code>.</p>

  <!-- Alert states: success + error (Key Q3) -->
  <div id="alertBox" class="alert d-none" role="alert"></div>

  <div class="row g-2 align-items-center mb-3">
    <div class="col-md-6">
      <!-- AJAX search with debounce (Intermediate requirement) -->
      <input id="searchInput" type="search" class="form-control"
             placeholder="Search name, email, ID, course… (AJAX + debounce)"
             autocomplete="off">
      <div class="form-text">Debounced ~300ms → <code>GET api/students.php?action=list&amp;q=…</code></div>
    </div>
    <div class="col-md-6 text-md-end">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" id="addBtn">+ Add Student</button>
      <button class="btn btn-outline-secondary" id="refreshBtn">Refresh</button>
    </div>
  </div>

  <!-- Loading spinner state (Key Q3) -->
  <div id="loadingBox" class="text-center my-4 d-none">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>
    <p class="mt-2 text-muted">Loading students…</p>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
          <th>Student ID</th><th>Course</th><th>Year</th><th>Gender</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <!-- Table body is rendered by assets/app.js (no server-side rows) -->
      <tbody id="studentsBody">
        <tr><td colspan="10" class="text-center text-muted">No data yet — loading…</td></tr>
      </tbody>
    </table>
  </div>
  <p class="text-muted small">Open DevTools → Network tab to see JSON requests and status codes (200 / 201 / 400 / 404 / 405 / 500).</p>
</div>

<!-- Add / Edit modal (single form, no page reload) -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="studentForm" class="modal-content" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="f_id">
        <div class="mb-2"><label class="form-label">Full Name</label><input id="f_name" class="form-control" required minlength="3"></div>
        <div class="mb-2"><label class="form-label">Email</label><input id="f_email" type="email" class="form-control" required></div>
        <div class="row">
          <div class="col-6 mb-2"><label class="form-label">Phone (10 digits)</label><input id="f_phone" class="form-control" required pattern="[0-9]{10}"></div>
          <div class="col-6 mb-2"><label class="form-label">Student ID (e.g. STU009)</label><input id="f_sid" class="form-control" required></div>
        </div>
        <div class="row">
          <div class="col-6 mb-2"><label class="form-label">Course</label><input id="f_course" class="form-control" required></div>
          <div class="col-6 mb-2"><label class="form-label">Year (1–4)</label><input id="f_year" type="number" min="1" max="4" class="form-control" required></div>
        </div>
        <div class="row">
          <div class="col-6 mb-2"><label class="form-label">Gender</label>
            <select id="f_gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select>
          </div>
          <div class="col-6 mb-2"><label class="form-label">Status</label>
            <select id="f_status" class="form-select"><option value="active">active</option><option value="inactive">inactive</option></select>
          </div>
        </div>
        <div class="form-text">Client-side checks run first; PHP re-validates everything server-side before any DB write.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
