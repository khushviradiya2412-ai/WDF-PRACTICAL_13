/* =====================================================
   StudentHub ITUE203 — Practical 13
   assets/app.js — ALL Fetch CRUD logic (no page reload)
   - list / add / update / delete via api/students.php
   - loading / success / error states (Key Q3)
   - AJAX search with debounce ~300ms (Intermediate)
   SECURITY: escapeHtml() used on every value rendered into
   the table, so stored XSS payloads display as text.
   ===================================================== */
'use strict';

const API = 'api/students.php';
const tbody = document.getElementById('studentsBody');
const alertBox = document.getElementById('alertBox');
const loadingBox = document.getElementById('loadingBox');
const searchInput = document.getElementById('searchInput');
const form = document.getElementById('studentForm');

// ---------- UI state helpers (Key Q3: loading/success/error) ----------
function showAlert(type, msg) {
  // type: 'success' | 'danger' | 'info'
  alertBox.className = 'alert alert-' + type;
  alertBox.textContent = msg;
  alertBox.classList.remove('d-none');
}
function hideAlert() {
  alertBox.classList.add('d-none');
}
function setLoading(on) {
  loadingBox.classList.toggle('d-none', !on);
}

// SECURITY: escape every DB value before injecting into innerHTML.
function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ---------- LIST (GET ?action=list[&q=]) ----------
async function loadStudents(query = '') {
  setLoading(true); // loading state ON
  try {
    const url = API + '?action=list' + (query ? '&q=' + encodeURIComponent(query) : '');
    const res = await fetch(url, { method: 'GET', headers: { Accept: 'application/json' } });
    const json = await res.json(); // API ALWAYS returns valid JSON
    if (!res.ok || !json.success) throw new Error(json.message || ('HTTP ' + res.status));
    renderRows(json.data || []);
  } catch (err) {
    // error state: keep old rows, show message
    showAlert('danger', 'Load failed: ' + err.message);
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Failed to load. Click Refresh.</td></tr>';
  } finally {
    setLoading(false); // loading state OFF (both paths)
  }
}

function renderRows(rows) {
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No students found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((r) => `
    <tr>
      <td>${escapeHtml(r.id)}</td>
      <td>${escapeHtml(r.full_name)}</td>
      <td>${escapeHtml(r.email)}</td>
      <td>${escapeHtml(r.phone)}</td>
      <td>${escapeHtml(r.student_id)}</td>
      <td>${escapeHtml(r.course)}</td>
      <td>${escapeHtml(r.year)}</td>
      <td>${escapeHtml(r.gender)}</td>
      <td><span class="badge ${r.status === 'active' ? 'badge-success' : 'badge-warning'}">${escapeHtml(r.status)}</span></td>
      <td class="text-nowrap">
        <button class="btn-sm btn-sm-edit" data-edit='${escapeHtml(r.id)}'>Edit</button>
        <button class="btn-sm btn-sm-delete" data-del='${escapeHtml(r.id)}'>Delete</button>
      </td>
    </tr>`).join('');
}

// ---------- SEARCH with debounce ~300ms (Intermediate) ----------
// Debounce: wait until the user STOPS typing for 300ms before
// firing the AJAX request — avoids one request per keystroke.
let debounceTimer = null;
searchInput.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadStudents(searchInput.value.trim()), 300);
});

document.getElementById('refreshBtn').addEventListener('click', () => {
  searchInput.value = '';
  hideAlert();
  loadStudents();
});

// ---------- ADD / UPDATE (POST JSON) ----------
function readForm() {
  return {
    id: document.getElementById('f_id').value.trim(),
    full_name: document.getElementById('f_name').value.trim(),
    email: document.getElementById('f_email').value.trim(),
    phone: document.getElementById('f_phone').value.trim(),
    student_id: document.getElementById('f_sid').value.trim(),
    course: document.getElementById('f_course').value.trim(),
    year: document.getElementById('f_year').value.trim(),
    gender: document.getElementById('f_gender').value,
    status: document.getElementById('f_status').value
  };
}

function clientValidate(d) {
  if (d.full_name.length < 3) return 'Full name must be at least 3 characters.';
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(d.email)) return 'Valid email is required.';
  if (!/^[0-9]{10}$/.test(d.phone)) return 'Phone must be exactly 10 digits.';
  if (!/^STU[0-9]{3,10}$/i.test(d.student_id)) return 'Student ID must look like STU009.';
  if (!d.course) return 'Course is required.';
  const y = Number(d.year);
  if (!Number.isInteger(y) || y < 1 || y > 4) return 'Year must be 1–4.';
  return null;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const d = readForm();
  const err = clientValidate(d); // fast UX check; PHP validates again server-side
  if (err) { showAlert('danger', err); return; }

  const isEdit = d.id !== '';
  const action = isEdit ? 'update' : 'add';
  try {
    const res = await fetch(API + '?action=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(isEdit ? d : {
        full_name: d.full_name, email: d.email, phone: d.phone,
        student_id: d.student_id, course: d.course, year: d.year,
        gender: d.gender, status: d.status
      })
    });
    const json = await res.json();
    if (!res.ok || !json.success) throw new Error(json.message || ('HTTP ' + res.status));
    // success state (201 created / 200 updated)
    showAlert('success', json.message);
    bootstrap.Modal.getInstance(document.getElementById('studentModal')).hide();
    form.reset();
    loadStudents(searchInput.value.trim()); // refresh table, NO page reload
  } catch (ex) {
    showAlert('danger', 'Save failed: ' + ex.message); // error state (400/404/500)
  }
});

// Reset modal for "Add"
document.getElementById('addBtn').addEventListener('click', () => {
  document.getElementById('modalTitle').textContent = 'Add Student';
  form.reset();
  document.getElementById('f_id').value = '';
  hideAlert();
});

// ---------- EDIT + DELETE (event delegation, no reload) ----------
tbody.addEventListener('click', async (e) => {
  const editId = e.target.getAttribute('data-edit');
  const delId = e.target.getAttribute('data-del');

  if (editId) {
    // Fill modal from the already-loaded row (no extra request needed).
    const cells = e.target.closest('tr').children;
    document.getElementById('modalTitle').textContent = 'Edit Student #' + editId;
    document.getElementById('f_id').value = editId;
    document.getElementById('f_name').value = cells[1].textContent;
    document.getElementById('f_email').value = cells[2].textContent;
    document.getElementById('f_phone').value = cells[3].textContent;
    document.getElementById('f_sid').value = cells[4].textContent;
    document.getElementById('f_course').value = cells[5].textContent;
    document.getElementById('f_year').value = cells[6].textContent;
    document.getElementById('f_gender').value = cells[7].textContent;
    document.getElementById('f_status').value = cells[8].textContent.trim();
    new bootstrap.Modal(document.getElementById('studentModal')).show();
  }

  if (delId) {
    if (!confirm('Delete student #' + delId + '?')) return; // delete confirm
    try {
      const res = await fetch(API + '?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ id: delId })
      });
      const json = await res.json();
      if (!res.ok || !json.success) throw new Error(json.message || ('HTTP ' + res.status));
      showAlert('success', json.message); // success state
      loadStudents(searchInput.value.trim()); // NO page reload
    } catch (ex) {
      showAlert('danger', 'Delete failed: ' + ex.message); // error state (400/404)
    }
  }
});

// Initial load
loadStudents();
