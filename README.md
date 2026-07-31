# SIPERDO

**Sistem Informasi Permohonan dan Persetujuan Dokumen** — API backend untuk aplikasi pengajuan, review, dan persetujuan dokumen. Dibangun dengan **Laravel 13** (REST API) dan PostgreSQL, dilengkapi otentikasi token **Laravel Sanctum**, otorisasi berbasis peran (**Spatie Permission**), antrean pekerjaan, ekspor **Excel/PDF**, serta SPA **Vue 3 + TypeScript** sebagai frontend.

> Repo ini sedang dalam proses migrasi dari Inertia starter kit menuju SPA client-side. Backend REST API sudah berfungsi penuh; bagian SPA masih dalam pengerjaan.

---

## Fitur

- **Otentikasi**: register, login, logout, profil, ganti kata sandi, lupa/reset kata sandi (token dikirim via email dengan tautan ke SPA).
- **Manajemen permohonan**: CRUD proyek/permohonan, unggah dokumen (PDF/DOC/DOCX, maks. 5 MB), submit untuk direview.
- **Alur persetujuan**: `draft → submitted → under_review → approved/rejected/revision`, dengan putaran revisi yang kembali ke `under_review`, lengkap dengan jejak log persetujuan dan riwayat aktivitas.
- **Peran**: `applicant` (pemohon), `reviewer` (penilai).
- **Dashboard**: ringkasan statistik, seri 12 bulan, dan aktivitas terbaru (per peran).
- **Ekspor**: Excel (semua proyek) dan PDF (daftar proyek / detail proyek).
- **Pemberitahuan email**: hasil review dikirim ke pemohon.
- **Pemrosesan dokumen asinkron**: kalkulasi checksum SHA-256 lewat job antrean.
- **Demo data**: 10.000 proyek, 1.000 pemohon, 1.000 penilai.

---

## Tech Stack

| Lapisan   | Teknologi                                                        |
| --------- | ---------------------------------------------------------------- |
| Backend   | Laravel 13, PHP 8.3+                                             |
| Database  | PostgreSQL                                                       |
| Auth      | Laravel Sanctum (token Bearer)                                   |
| Otorisasi | spatie/laravel-permission (role + permission)                    |
| Ekspor    | maatwebsite/excel (XLSX), barryvdh/laravel-dompdf (PDF)          |
| Frontend  | Vue 3, TypeScript, Tailwind CSS 4 (sedang dikerjakan)            |
| CI        | GitHub Actions (PHP 8.3, Node 22, PostgreSQL)                    |

---

## Struktur Proyek

```
app/
├── Enums/ProjectStatus.php          # Status alur: draft..rejected
├── Http/
│   ├── Controllers/Api/             # Auth, Project, ProjectDocument,
│   │                                # Review, Dashboard, Reference, Export
│   ├── Requests/                    # Form Request per aksi
│   └── Resources/                   # Project*, Review*, User*Resource
├── Exports/ProjectsExport.php       # Export Excel
├── Jobs/ProcessDocumentJob.php      # Hash dokumen asinkron
├── Mail/ReviewOutcomeNotification.php
├── Models/                          # Project, Review, ProjectDocument, ...
├── Notifications/ResetPassword.php  # Tautan reset menuju SPA
├── Policies/                        # ProjectPolicy, ReviewPolicy
├── Providers/
├── Repositories/ProjectRepository.php
├── Services/                        # Auth, Project, Review, Dashboard, ...
└── Support/StatusPresenter.php      # Format status untuk frontend

routes/api.php                       # Seluruh endpoint v1
database/
├── factories/                       # User, Project, ProjectDocument
├── migrations/
└── seeders/                         # RolePermission, DemoData
tests/Feature/Api/                   # Feature test per modul
```

---

## Prasyarat

- PHP **8.3+** dengan ekstensi `pdo_pgsql`
- Composer 2
- PostgreSQL **15+** (server lokal, mis. Laragon/XAMPP atau service sistem)
- Node.js 20+ & npm (untuk frontend)
- Ekstensi PHP: `zip`, `gd`/`imagick`, `mbstring` (dibutuhkan oleh ekspor Excel/PDF)

> Catatan: proyek ini **tidak** memakai SQLite untuk pengujian. `phpunit.xml` terhubung ke PostgreSQL `siperdo_test`.

---

## Instalasi

```bash
# 1. Install dependency backend
composer install

# 2. Buat .env lalu sesuaikan kredensial PostgreSQL (lihat .env.example)
cp .env.example .env
php artisan key:generate

# 3. Konfigurasi .env — bagian database:
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5432
#    DB_DATABASE=siperdo
#    DB_USERNAME=postgres
#    DB_PASSWORD=<password>

# 4. Buat database
createdb siperdo

# 5. Migrasi + seeder (peran & data demo)
php artisan migrate --seed

# 6. (Opsional) Jalankan worker antrean untuk job dokumen & email
php artisan queue:work

# 7. Jalankan server API
php artisan serve
# Frontend dev: npm install && npm run dev
```

Alias praktis yang sudah disediakan di `composer.json`:

```bash
composer dev        # serve API + queue:listen + vite sekaligus
composer setup      # instal lengkap dari nol (deps, .env, migrate, build)
composer test       # config:clear + pint --test + phpstan + php artisan test
composer ci:check   # lint JS + format + types + composer test (dipakai CI)
```

---

## Akun Demo (Seeder)

| Peran    | Email              | Password   |
| -------- | ------------------ | ---------- |
| Pemohon  | dihasilkan dari demo data | `password` |
| Penilai  | dihasilkan dari demo data | `password` |

Seeder `DemoDataSeeder` membuat 1.000 pemohon dan 1.000 penilai (email pola `*@example.org`, kata sandi `password`). Contoh penilai: `psaragih@example.org` / `password`.

---

## Menjalankan Pengujian

Pengujian memakai database PostgreSQL terpisah `siperdo_test`. Buat dulu:

```sql
CREATE DATABASE siperdo_test;
```

Lalu jalankan:

```bash
php artisan test                     # seluruh suite
php artisan test --filter ProjectTest # subset
composer types:check                 # PHPStan level 7
composer lint                        # format dengan Pint
```

CI (`composer ci:check`) menyediakan service PostgreSQL otomatis, jadi tidak perlu setup manual.

---

## API

Base URL: `http://localhost:8000/api/v1`

Otentikasi memakai **Bearer token**. Login/register mengembalikan `token`; kirimkan pada header `Authorization: Bearer <token>` untuk endpoint terproteksi.

### Autentikasi

| Method | Endpoint                     | Deskripsi                        |
| ------ | ---------------------------- | -------------------------------- |
| POST   | `/auth/register`             | Daftar sebagai pemohon           |
| POST   | `/auth/login`                | Login, mengembalikan token       |
| POST   | `/auth/logout`               | Hapus token saat ini             |
| GET    | `/auth/me`                   | Profil pengguna (dengan peran)   |
| PUT    | `/auth/profile`              | Perbarui nama/email/password     |
| POST   | `/auth/forgot-password`      | Kirim tautan reset ke email      |
| POST   | `/auth/reset-password`       | Reset kata sandi (token + email) |

### Permohonan & Dokumen

| Method | Endpoint                                            | Deskripsi                                   |
| ------ | --------------------------------------------------- | ------------------------------------------- |
| GET    | `/projects`                                         | Daftar permohonan (filter: `search`, `status`, `applicant_id`, `reviewer_id`, `date_from`, `date_to`, `sort`) |
| POST   | `/projects`                                         | Buat permohonan baru (draft)                |
| GET    | `/projects/{project}`                               | Detail permohonan (dokumen + review)        |
| PUT    | `/projects/{project}`                               | Ubah detail (hanya draft, pemilik)          |
| DELETE | `/projects/{project}`                               | Hapus (hanya draft, pemilik)                |
| POST   | `/projects/{project}/submit`                        | Submit untuk direview (wajib ada dokumen)   |
| POST   | `/projects/{project}/start-review`                  | Reviewer mengklaim review                  |
| GET    | `/projects/{project}/history`                       | Riwayat persetujuan & aktivitas            |
| POST   | `/projects/{project}/documents`                     | Unggah dokumen (array `documents`, PDF/DOC/DOCX, maks 5 MB) |
| DELETE | `/projects/{project}/documents/{document}`          | Hapus dokumen (draft/revision)             |
| GET    | `/projects/{project}/documents/{document}/download` | Unduh dokumen                               |

### Review & Dashboard

| Method | Endpoint      | Deskripsi                                                       |
| ------ | ------------- | --------------------------------------------------------------- |
| GET    | `/reviews`    | Reviewer: antrean pending + riwayat. Pemohon: review proyeknya  |
| POST   | `/reviews`    | Keputusan `approved` / `rejected` / `revision` (wajib `notes`), hanya saat proyek `under_review` |
| GET    | `/dashboard`  | Ringkasan statistik, seri bulanan, aktivitas terbaru            |
| GET    | `/references?type=applicants\|reviewers` | Opsi daftar untuk filter                        |

### Ekspor

| Method | Endpoint                           | Deskripsi                                    |
| ------ | ---------------------------------- | -------------------------------------------- |
| GET    | `/projects/export/excel`           | Semua proyek dalam XLSX (reviewer)          |
| GET    | `/projects/export/pdf`             | Daftar proyek dalam PDF, maks. 500 baris     |
| GET    | `/projects/{project}/pdf`          | Detail satu proyek dalam PDF (pemilik/role)  |

### Contoh Alur

```bash
# 1. Registrasi & login
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"name":"Budi","email":"budi@example.com","password":"password123","password_confirmation":"password123"}' \
  | jq -r .token)

# 2. Buat permohonan
curl -s -X POST http://localhost:8000/api/v1/projects \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"project_name":"Permohonan Sertifikat Tanah","description":"..."}'

# 3. Unggah dokumen (multipart)
curl -s -X POST http://localhost:8000/api/v1/projects/1/documents \
  -H "Authorization: Bearer $TOKEN" \
  -F "documents[]=@berkas.pdf"

# 4. Submit
curl -s -X POST http://localhost:8000/api/v1/projects/1/submit \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

### Format Respons

Koleksi menggunakan struktur pagination Laravel:

```json
{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "path": "...", "per_page": 15, "to": 10, "total": 10 }
}
```

Objek status diformat untuk frontend:

```json
{ "value": "approved", "label": "Approved", "color": { "badge": "...", "dot": "..." } }
```

Error menggunakan format standar Laravel (`message` + `errors` untuk validasi). Kode status: `200`/`201` sukses, `401` belum login, `403` tidak punya izin, `404` tidak ditemukan, `422` validasi gagal.

---

## ERD

```
┌─────────────┐        ┌──────────────────┐       ┌───────────────────┐
│    users    │        │    projects      │       │ project_documents │
├─────────────┤        ├──────────────────┤       ├───────────────────┤
│ id          │        │ id               │       │ id                │
│ name        │1     N │ project_code     │1     N│ project_id ───────┘
│ email       ├───────▶│ project_name     ├──────▶│ original_name     │
│ password    │        │ description      │       │ stored_name       │
│ ...         │        │ applicant_id ────┘       │ path              │
│ (roles via  │        │ status (enum)    │       │ mime_type         │
│  model_has_ │        │ submitted_at     │       │ size              │
│  roles)     │        │ created_at       │       │ hash              │
└─────────────┘        │ updated_at       │       │ processed_at      │
                       └──────────────────┘       │ uploaded_by ──────┘
                              │ 1
                              │ N
                       ┌──────▼───────────┐       ┌───────────────────┐
                       │    reviews       │       │  approval_logs    │
                       ├──────────────────┤       ├───────────────────┤
                       │ id               │       │ id                │
                       │ project_id ──────┘       │ project_id ───────┘
                       │ reviewer_id ─────┘       │ user_id ──────────┘
                       │ status (enum)     │       │ action            │
                       │ notes             │       │ old_status        │
                       │ created_at        │       │ new_status        │
                       └──────────────────┘       │ notes             │
                       ┌──────────────────┐       │ created_at        │
                       │  activity_logs   │       └───────────────────┘
                       ├──────────────────┤
                       │ id               │
                       │ user_id ─────────┘
                       │ project_id ──────┘
                       │ activity         │
                       │ created_at       │
                       └──────────────────┘
```

Relasi kunci:

- `projects.applicant_id → users.id`
- `reviews.project_id → projects.id`, `reviews.reviewer_id → users.id`
- `project_documents.project_id → projects.id`
- `approval_logs.project_id → projects.id`, `approval_logs.user_id → users.id`
- `activity_logs.user_id → users.id`, `activity_logs.project_id → projects.id`

---

## Alur Persetujuan

```
Draft ──submit──▶ Submitted ──start-review──▶ Under Review
                                                 │
                        ┌────────────────────────┼────────────────────────┐
                        ▼                        ▼                        ▼
                    Approved                Rejected                  Revision
                 (log "publish")                                        │
                            ▲                          edit + unggah ulang │
                            │                                            ▼
                          Final                                    (pemohon) submit ulang
                                                                        │
                                                                        ▼
                                                               Under Review (lagi)
```

Aturan utama:

1. **Submit** hanya oleh pemilik dan wajib memiliki minimal satu dokumen. Dari `draft` → `submitted`; submit ulang dari `revision` → langsung `under_review` (memasuki siklus review kembali).
2. **Start review** hanya oleh `reviewer`, dan hanya dari status `submitted` → `under_review`.
3. **Keputusan** (`approved`/`rejected`/`revision`) hanya oleh `reviewer`, dan hanya saat proyek sedang `under_review`. `notes` wajib diisi.
4. Saat **Approved**, selain log `approve` juga dicatat log `publish` (pengesahan dokumen). `Approved` dan `Rejected` bersifat final.
5. Saat **Revision**, pemohon dapat mengubah data dan mengunggah ulang dokumen, lalu submit ulang ke `under_review`.
6. Setiap transisi tercatat di `approval_logs`; pengajuan mengirim email ke `reviewer` (`ProjectSubmittedNotification`), sedangkan keputusan mengirim email ke pemohon (`ReviewOutcomeNotification`).
7. Pemohon hanya melihat/berinteraksi dengan proyek miliknya; `reviewer` melihat semua.

---

## Peran & Izin

| Permission         | Applicant | Reviewer |
| ------------------ | :-------: | :------: |
| `projects.view`    | ✅        | ✅       |
| `projects.create`  | ✅        | –        |
| `projects.update`  | ✅        | –        |
| `projects.delete`  | ✅        | –        |
| `projects.submit`  | ✅        | –        |
| `projects.export`  | –         | ✅       |
| `documents.upload` | ✅        | –        |
| `reviews.view`     | ✅        | ✅       |
| `reviews.create`   | –         | ✅       |
| `dashboard.view`   | ✅        | ✅       |

Otorisasi yang lebih halus (kepemilikan, status) diatur melalui `Policies` (`ProjectPolicy`, `ReviewPolicy`).

---

## Lisensi

Proyek ini bersumber dari skeleton resmi Laravel dan dilisensikan di bawah [MIT License](LICENSE).
