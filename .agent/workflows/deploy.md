---
description: Cara deploy aplikasi Laravel ke shared hosting (cPanel)
---

# Panduan Deploy Laravel ke Shared Hosting (cPanel)

## Persiapan Sebelum Deploy

### 1. Update Composer Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 2. Optimasi Laravel untuk Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Build Assets (jika ada)
```bash
npm run build
# atau
npm run production
```

### 4. Backup Database Lokal
Eksport database development Anda untuk di-import ke server production.

---

## Langkah Deploy ke cPanel

### 1. Siapkan Database di cPanel

1. Login ke **cPanel**
2. Buka **MySQL Databases**
3. Buat database baru (misal: `user_managementtoko`)
4. Buat user database baru
5. Assign user ke database dengan **ALL PRIVILEGES**
6. Catat: **Database Name**, **Database User**, **Database Password**

### 2. Import Database

1. Buka **phpMyAdmin** di cPanel
2. Pilih database yang baru dibuat
3. Import file SQL backup dari development
4. Pastikan semua tabel ter-import dengan benar

### 3. Upload File Project

**Opsi A: Via File Manager cPanel**
1. Zip seluruh project Laravel (kecuali `node_modules`, `.git`, `storage/logs/*`)
2. Upload file zip ke folder `public_html/` atau `domains/namadomain.com/`
3. Extract file zip di server

**Opsi B: Via FTP/SFTP**
1. Gunakan FileZilla atau WinSCP
2. Upload semua file project ke `public_html/` atau folder domain

**Opsi C: Via Git (jika tersedia SSH)**
```bash
cd public_html
git clone https://github.com/username/repo-name.git .
```

### 4. Atur Struktur Folder

**PENTING:** Laravel tidak bisa diakses langsung dari `public_html`. Anda perlu memisahkan folder `public` dan aplikasi.

**Struktur yang Benar:**
```
/home/username/
├── laravel-app/          # Folder aplikasi (di luar public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── public_html/          # Folder publik (document root)
    ├── index.php         # Copy dari laravel-app/public/
    ├── .htaccess         # Copy dari laravel-app/public/
    └── assets/           # Copy semua assets dari public/
```

**Cara Setting:**
1. Pindahkan seluruh folder aplikasi ke `/home/username/laravel-app/` (atau nama lain di luar public_html)
2. Copy isi folder `public/` ke `public_html/`
3. Edit file `public_html/index.php`

### 5. Edit File index.php

Buka `public_html/index.php` dan update path:

**Ubah dari:**
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Menjadi:**
```php
require __DIR__.'/../laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
```

*(Sesuaikan `laravel-app` dengan nama folder Anda)*

### 6. Konfigurasi File .env

Buat/edit file `.env` di folder aplikasi (`/home/username/laravel-app/.env`):

```env
APP_NAME="Management Toko"
APP_ENV=production
APP_KEY=base64:1SVZlHixif0MyA0SklQg9wExKwhKai9A63YO+3y5CBo=
APP_DEBUG=false
APP_URL=https://namadomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=user_managementtoko
DB_USERNAME=user_dbuser
DB_PASSWORD=password_database

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (opsional)
MAIL_MAILER=smtp
MAIL_HOST=mail.namadomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@namadomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@namadomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**PENTING:**
- Set `APP_DEBUG=false` untuk production
- Set `APP_ENV=production`
- Update `APP_URL` dengan domain Anda
- Update kredensial database

### 7. Set Permissions untuk Storage & Bootstrap

Via File Manager cPanel atau FTP, set permissions:

```
storage/                 -> 775
storage/framework/       -> 775
storage/logs/            -> 775
bootstrap/cache/         -> 775
```

Via SSH (jika tersedia):
```bash
cd /home/username/laravel-app
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 8. Generate Application Key (jika belum)

Jika Anda tidak punya `APP_KEY` atau perlu generate baru:

**Via SSH:**
```bash
cd /home/username/laravel-app
php artisan key:generate
```

**Via Browser (buat file temporary):**
Buat file `generate-key.php` di `public_html/`:
```php
<?php
require __DIR__.'/../laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArrayInput(['command' => 'key:generate']),
    new Symfony\Component\Console\Output\BufferedOutput
);
echo "Key generated! Check your .env file.";
```
Akses via browser: `https://namadomain.com/generate-key.php`
**Hapus file ini setelah selesai!**

### 9. Jalankan Migration & Seeder

**Via SSH:**
```bash
cd /home/username/laravel-app
php artisan migrate --force
php artisan db:seed --force
```

**Via Browser (buat file temporary):**
Buat file `migrate.php` di `public_html/` (HAPUS setelah selesai):
```php
<?php
require __DIR__.'/../laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Run migration
$kernel->handle(
    new Symfony\Component\Console\Input\ArrayInput(['command' => 'migrate', '--force' => true]),
    new Symfony\Component\Console\Output\BufferedOutput
);

echo "Migration completed!<br>";

// Run seeder
$kernel->handle(
    new Symfony\Component\Console\Input\ArrayInput(['command' => 'db:seed', '--force' => true]),
    new Symfony\Component\Console\Output\BufferedOutput
);

echo "Seeding completed!";
```

### 10. Clear & Cache Configuration

**Via SSH:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Kemudian cache ulang untuk production:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting

### Error: 500 Internal Server Error

1. **Cek log error:**
   - Lihat `storage/logs/laravel.log`
   - Atau cek Error Log di cPanel

2. **Pastikan permissions benar:**
   ```bash
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

3. **Cek file .htaccess:**
   Pastikan ada di `public_html/` dengan isi:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```
   
   Atau jika struktur sudah benar:
   ```apache
   <IfModule mod_rewrite.c>
       <IfModule mod_negotiation.c>
           Options -MultiViews -Indexes
       </IfModule>

       RewriteEngine On

       # Handle Authorization Header
       RewriteCond %{HTTP:Authorization} .
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

       # Redirect Trailing Slashes If Not A Folder...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_URI} (.+)/$
       RewriteRule ^ %1 [L,R=301]

       # Send Requests To Front Controller...
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule ^ index.php [L]
   </IfModule>
   ```

### Error: Database Connection Failed

1. Pastikan database sudah dibuat di cPanel
2. Cek kredensial di `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
3. Pastikan `DB_HOST=localhost` (bukan 127.0.0.1)
4. Clear config cache: `php artisan config:clear`

### Error: Class Not Found / Autoload

Pastikan Composer dependencies ter-install:
```bash
composer install --optimize-autoloader --no-dev
composer dump-autoload
```

### Storage Link untuk Upload Files

Jika menggunakan `storage/app/public` untuk upload:
```bash
php artisan storage:link
```

Atau manual:
```bash
cd public_html
ln -s ../laravel-app/storage/app/public storage
```

---

## Checklist Deploy

- [ ] Database sudah dibuat di cPanel
- [ ] Database di-import dari development
- [ ] Files ter-upload ke server
- [ ] Struktur folder sudah benar (public terpisah)
- [ ] File `index.php` sudah di-update pathnya
- [ ] File `.env` sudah dikonfigurasi (DB, APP_URL, APP_ENV=production, APP_DEBUG=false)
- [ ] Permissions storage & bootstrap sudah 775
- [ ] Migration & seeder sudah dijalankan
- [ ] Config, route, view sudah di-cache
- [ ] Storage link sudah dibuat (jika diperlukan)
- [ ] Testing akses website berjalan normal
- [ ] Testing fitur CRUD berjalan normal
- [ ] Testing upload file (jika ada)

---

## Tips Production

1. **Selalu backup database** sebelum update/deploy
2. **Jangan pernah set `APP_DEBUG=true`** di production
3. **Gunakan HTTPS** untuk keamanan (install SSL via cPanel)
4. **Setup monitoring** untuk error logs
5. **Setup backup otomatis** database & files
6. **Gunakan queue worker** untuk job yang berat (jika server support)

---

## Update Project di Kemudian Hari

1. Backup database production
2. Upload file yang berubah via FTP/Git
3. Jalankan migration jika ada perubahan database:
   ```bash
   php artisan migrate --force
   ```
4. Clear cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```
5. Cache ulang:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
