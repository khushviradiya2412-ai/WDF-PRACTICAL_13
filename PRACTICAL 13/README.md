# Practical 13 — PHP JSON API + Fetch CRUD (no page reload)

## 1. Purpose
Single PHP JSON endpoint (`api/students.php`) serves student data; `index.php` + `assets/app.js`
consume it with the Fetch API to list / add / update / delete / search students **without a full
page reload**. Includes Intermediate (AJAX search with debounce) and Advanced (HTTP status codes +
centralized error handling) extensions.

## 2. Setup (XAMPP, PHP 8, MySQL)
1. Start **Apache** and **MySQL** in XAMPP.
2. Copy the `PRACTICAL 13` folder into `C:\xampp\htdocs\` (e.g. `C:\xampp\htdocs\p13`).
3. Create DB + tables + seeds: open phpMyAdmin → Import → choose `database.sql`
   (creates DB `studenthub_p13`). Or via shell:
   `mysql -u root < database.sql`
4. Connection is in `db.php` (`localhost`, user `root`, empty password).
5. Open the app: `http://localhost/p13/index.php`

## 3. API endpoints (single file, `?action=`)

| Method | URL | Body | Success | Errors |
|---|---|---|---|---|
| GET | `api/students.php?action=list` | — | `200 {success,message,data[]}` | `405` wrong method, `500` server |
| GET | `api/students.php?action=list&q=abc` | — | `200` filtered rows (name/email/ID/course) | `405`, `500` |
| POST | `api/students.php?action=add` | JSON `{full_name,email,phone,student_id,course,year,gender,status}` | `201` created row | `400` validation/duplicate, `405`, `500` |
| POST | `api/students.php?action=update` | JSON `{id, …same fields…}` | `200` updated row | `400` validation/duplicate, `404` not found, `405`, `500` |
| POST | `api/students.php?action=delete` | JSON `{id}` | `200` `{id}` | `400` missing id, `404` not found, `405`, `500` |

Response shape is always `{success: bool, message: string, data: array|object|null}`
with `Content-Type: application/json`. Unknown `?action=` returns `404`.

## 4. App URL
- UI: `index.php` (table rendered by JS, add/edit Bootstrap modal, delete with confirm,
  spinner for loading, alerts for success/error, search box with ~300ms debounce).
- JS: `assets/app.js`. Theme: `assets/style.css` (copy of `PRACTICAL 7/CSS/style.css`).

## 5. Demo credentials (from `database.sql`)
- Admin — username `admin` / password `Admin@123`
- Student — username `student` / password `Student@123`
- (Login form is not part of P13; credentials seed the `users` table for audit/dashboard reuse.)

## 6. Viva answers
1. **Why JSON + status codes?** JSON is a language-neutral data format `fetch()` + `response.json()`
   can parse reliably; status codes (200 list, 201 created, 400 validation, 404 not found,
   405 wrong method, 500 server) let the frontend branch success vs error handling without
   parsing message text, and they show correctly in the Network tab.
2. **What does debounce do?** It waits until typing pauses (~300ms) before sending the AJAX
   search request, so 10 fast keystrokes produce ~1 request instead of 10 — less DB/server load.
3. **Where does validation happen?** In **both** places: quick client-side checks in `app.js`
   for UX, then authoritative **server-side validation in `api/students.php` before any DB query**
   (plus prepared statements + HTML-escaping on render), because client checks can be bypassed.
