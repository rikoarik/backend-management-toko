# Panduan Deploy ke Rumahweb

## 1. Persiapan di cPanel Rumahweb

### Buat Database MySQL

1. Login ke cPanel Rumahweb
2. Buka **MySQL Databases**
3. Buat database baru: `username_toko`
4. Buat user baru: `username_tokouser`
5. Assign user ke database dengan **ALL PRIVILEGES**
6. Catat: database name, username, password

### Buat Subdomain (opsional)

1. Buka **Subdomains**
2. Buat subdomain: `api.yourdomain.com`
3. Document root: `/home/username/api.yourdomain.com`

---

## 2. Upload Files

### Struktur yang Benar:

```
/home/username/
├── api.yourdomain.com/          ← Subdomain root
│   └── public_html/             ← Document root (arahkan ke sini)
│       ├── index.php            ← dari folder public/
│       ├── .htaccess            ← dari folder public/
│       └── storage/             ← symlink
│
└── laravel-app/                 ← Folder private (di luar public)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── routes/
    ├── storage/
    ├── vendor/
    └── .env                     ← Copy dari .env.production.example
```

### Via File Manager / FTP:

1. Upload semua files ke `/home/username/laravel-app/`
2. Copy isi folder `public/` ke `public_html/`
3. Edit `public_html/index.php`:

```php
// Ubah path ini:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Menjadi:
require __DIR__.'/../laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
```

---

## 3. Konfigurasi .env

Copy `.env.production.example` ke `.env` di folder `laravel-app/`:

```bash
# Ganti nilai-nilai ini:
APP_URL=https://api.yourdomain.com
DB_DATABASE=username_toko
DB_USERNAME=username_tokouser
DB_PASSWORD=your_password
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

---

## 4. Generate App Key

Via SSH (jika tersedia):

```bash
cd ~/laravel-app
php artisan key:generate
```

Via cPanel Terminal atau online generator, lalu paste ke .env:

```
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

---

## 5. Migrasi Database

Via SSH:

```bash
cd ~/laravel-app
php artisan migrate --force
```

Atau import file SQL dari `database/schema/` jika ada.

---

## 6. Set Permissions

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## 7. Buat Storage Symlink

Via SSH:

```bash
cd ~/public_html
ln -s ../laravel-app/storage/app/public storage
```

Atau manual via File Manager.

---

## 8. Test API

Akses: `https://api.yourdomain.com/api/v1/auth/register`

Swagger UI: `https://api.yourdomain.com/api/documentation`

---

## Troubleshooting

### Error 500

-   Cek `storage/logs/laravel.log`
-   Pastikan permissions benar (775)

### Database Connection Error

-   Cek DB_HOST (gunakan `localhost` bukan `127.0.0.1`)
-   Pastikan user punya akses ke database

### CORS Error

-   Tambahkan domain frontend ke `SANCTUM_STATEFUL_DOMAINS`
-   Cek `config/cors.php`
