# Deploy OPTIMA sebagai aplikasi Laravel publik

GitHub Pages tidak dapat dipakai karena aplikasi ini memerlukan PHP, sesi server,
dan koneksi PostgreSQL. Gunakan host yang mendukung Docker/PHP, misalnya Laravel
Cloud, Railway, Render, atau VPS yang dikelola dengan Laravel Forge.

## Siapkan repository

1. Commit dan push repository `optima/optima` ke GitHub. Jangan pernah commit
   `.env`, password database, key rahasia, atau Google Client Secret.
2. Buat layanan web baru pada host pilihan, hubungkan dengan repository, lalu
   pilih deployment dari `Dockerfile` pada root repository.
3. Set health-check path ke `/up`.

## Environment variables di hosting

Isi variabel berikut melalui dashboard hosting, bukan file repository:

```dotenv
APP_NAME="OPTIMA ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN-ANDA
APP_KEY=BASE64_KEY_DARI_PERINTAH_DI_BAWAH

DB_CONNECTION=pgsql
DB_HOST=HOST_DARI_SUPABASE_CONNECT
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=USERNAME_DARI_SUPABASE_CONNECT
DB_PASSWORD=PASSWORD_DATABASE_SUPABASE
DB_SSLMODE=require

SUPABASE_URL=https://wuflhovtbvocjpalblab.supabase.co
SUPABASE_PUBLISHABLE_KEY=sb_publishable_58URlpq7aVTG1JyFydodWw_5EiWSPWD

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
LOG_CHANNEL=stderr
```

Untuk membuat `APP_KEY`, jalankan sekali di komputer pengembangan setelah PHP
terpasang, lalu salin output-nya ke dashboard hosting:

```bash
php artisan key:generate --show
```

Gunakan host, port, dan username persis yang diberikan pada **Supabase > Project
Settings > Database > Connect**. Untuk platform cloud biasanya gunakan Session
Pooler, bukan direct connection IPv6.

## Deploy pertama

1. Set `RUN_MIGRATIONS=true` pada deploy pertama agar tabel Laravel dan CRM dibuat.
2. Setelah deployment sehat dan tabel sudah terbentuk, ubah menjadi
   `RUN_MIGRATIONS=false`. Untuk perubahan schema berikutnya, jalankan migration
   sebagai one-off/release command; jangan semua instance web menjalankannya.
3. Buka `https://DOMAIN-ANDA/up`; respons `200` menandakan Laravel berhasil boot.

## Aktifkan SSO Google di domain publik

Setelah URL hosting tersedia, update semua lokasi berikut:

1. Ubah `APP_URL` menjadi `https://DOMAIN-ANDA`.
2. Supabase > Authentication > URL Configuration:
   - Site URL: `https://DOMAIN-ANDA`
   - Redirect URL: `https://DOMAIN-ANDA/auth/callback`
3. Google Cloud Console:
   - Authorized JavaScript origins: `https://DOMAIN-ANDA`
   - Authorized redirect URI: callback Supabase yang ditampilkan di halaman
     provider Google (`https://wuflhovtbvocjpalblab.supabase.co/auth/v1/callback`).
4. Deploy ulang setelah `APP_URL` berubah, lalu uji tombol **Masuk dengan Google**.

## Verifikasi minimum

- `/up` mengembalikan 200.
- `/login` tampil melalui HTTPS.
- Login Google kembali ke `/auth/callback`, kemudian dashboard.
- User pertama ada pada tabel `public.user_profiles`; ubah role-nya menjadi
  `super_admin` melalui Supabase SQL Editor bila perlu.
