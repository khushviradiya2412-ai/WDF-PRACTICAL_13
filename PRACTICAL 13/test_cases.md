# Practical 13 — Test Cases (Fetch CRUD + JSON API)

> Fill the **Actual** column during the lab demo (screenshots: Network tab JSON + status codes).

| # | Test | Steps | Expected | Actual |
|---|---|---|---|---|
| 1 | List students | Open `index.php`; check table + Network `GET action=list` | `200`, valid JSON `{success:true,…}`, rows render |  |
| 2 | Add valid student | Click + Add Student, fill valid data (e.g. STU009), Save | `201`, success alert, new row appears, no reload |  |
| 3 | Add invalid student | Save with bad email + 5-digit phone | `400`, error alert, no row added |  |
| 4 | Update student | Click Edit, change course/year, Save | `200`, success alert, row updates, no reload |  |
| 5 | Delete missing id | `POST action=delete` body `{}` or `{id:9999}` | `400` (missing id) / `404` (not found), error alert |  |
| 6 | Search debounce | Type `aar` quickly in search box; count Network calls | ~1 `GET action=list&q=aar` after ~300ms pause, filtered rows |  |
| 7 | Server 404 (unknown action) | Open `api/students.php?action=bogus` | `404` JSON `{success:false,…}` (never HTML) |  |
