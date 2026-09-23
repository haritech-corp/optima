# OPTIMA ERP

MVP sistem ERP berbasis Laravel dan Supabase dengan fokus pada SSO, dashboard berbasis role, serta CRM lead-to-client.

## Fitur MVP

- Supabase Auth untuk email/password dan Google SSO.
- User provisioning dan role dasar.
- Dashboard metrik CRM dan personal follow-up queue.
- Lead CRUD, filter, duplicate warning, archive, dan conversion.
- Client list/detail, interaction timeline, dan Follow-up Center.
- PostgreSQL migration, seed data, RLS starter policy, serta GitHub Actions CI.

## Arsitektur

- Application: Laravel 13 / PHP 8.3+
- Database: Supabase PostgreSQL
- Authentication: Supabase Auth
- Repository dan CI: GitHub + GitHub Actions
- Hosting: runtime PHP/container yang menerima deployment dari GitHub

GitHub Pages tidak dapat menjalankan Laravel karena hanya melayani aset statis.

## Setup Development

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

Buka `http://localhost:8000`.

## Connection String Supabase Development

Buka **Supabase Dashboard > Project Settings > Database > Connect**. Pilih **Direct connection** bila jaringan mendukung IPv6, atau **Session pooler** untuk koneksi development yang lebih kompatibel.

Format direct connection project ini:

```text
postgresql://postgres:[DATABASE_PASSWORD]@db.wuflhovtbvocjpalblab.supabase.co:5432/postgres?sslmode=require
```

Konfigurasi Laravel:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db.wuflhovtbvocjpalblab.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=YOUR_SUPABASE_DATABASE_PASSWORD
DB_SSLMODE=require
SUPABASE_URL=https://wuflhovtbvocjpalblab.supabase.co
SUPABASE_PUBLISHABLE_KEY=YOUR_SUPABASE_PUBLISHABLE_KEY
```

Jika memakai Session pooler, salin host, port, dan user persis dari panel Connect karena region dan username pooler ditentukan Supabase. Jangan commit password database, access token, atau service-role key.

## Google SSO (langsung melalui backend Laravel)

1. Aktifkan provider Google pada **Supabase Authentication > Providers**.
2. Tambahkan **Redirect URL** `http://localhost:8000/auth/callback` di **Authentication > URL Configuration**. Untuk production, tambahkan `https://DOMAIN-ANDA/auth/callback` juga.
3. Pada Google Cloud Console, gunakan callback yang ditampilkan oleh halaman provider Google di Supabase (umumnya `https://PROJECT_REF.supabase.co/auth/v1/callback`), bukan callback Laravel.
4. Pastikan **Site URL** mengarah ke URL aplikasi yang aktif dan `APP_URL` di Laravel memakai URL yang sama.
5. Isi `SUPABASE_URL` dan `SUPABASE_PUBLISHABLE_KEY` pada `.env`, lalu jalankan `php artisan config:clear`.
6. Simpan Google Client ID dan Client Secret di Supabase, bukan repository.

Alur ini menggunakan OAuth PKCE: Laravel membuat `state` dan verifier, menukar authorization code di server, lalu menyimpan token Supabase hanya di sesi Laravel. Token tidak dikirim melalui URL atau disimpan browser. Sesi akan diperbarui otomatis memakai refresh token sebelum kedaluwarsa.

## Role Awal

- `super_admin`
- `management`
- `crm_manager`
- `crm_staff`
- `viewer`

User baru mendapat role `crm_staff`. Sebelum production, ubah provisioning menjadi invitation/domain allowlist dan tinjau role setiap user.

## Branch Flow

- `main`: production
- `develop`: integrasi dan staging
- `feature/*`: perubahan terisolasi

Lihat [catatan implementasi](docs/IMPLEMENTATION.md) untuk pekerjaan sebelum production dan backlog berikutnya.
