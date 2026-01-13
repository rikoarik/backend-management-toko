# Backend Management Toko - Laravel Implementation Plan

> **Project Type:** Point of Sale (POS) & Store Management System
> **Backend Framework:** Laravel 11 (PHP)
> **Database:** MySQL
> **Target:** REST API for mobile/web client

---

## Overview

Building a REST API backend for a store management application including POS (Point of Sale), product management, categories, sales reports, and dashboard analytics. The backend will be built using **Laravel 11** for robustness, developer speed, and its extensive ecosystem.

---

## Tech Stack

### Core Technologies

- **Framework:** Laravel 11.x
- **Language:** PHP 8.2+
- **Database:** MySQL 8.0+
- **ORM:** Eloquent ORM
- **API Authentication:** Laravel Sanctum (for SPA/Mobile token based auth)

### Additional Libraries/Packages

- **File Upload:** Laravel Storage (Local/S3)
- **Image Processing:** `intervention/image`
- **Excel Export:** `maatwebsite/excel`
- **PDF Export:** `barryvdh/laravel-dompdf` or `spatie/laravel-pdf`
- **Barcode:** `picqer/php-barcode-generator`
- **API Documentation:** `knuckleswtf/scribe` or `l5-swagger`

---

## Database Schema

### User (`users` table)

Standard Laravel User model with modifications:

- `id`: BigIncrements/UUID
- `email`: String (unique)
- `username`: String (unique)
- `password`: String (hashed)
- `name`: String
- `role`: String (default: 'kasir')
- `profile_photo_path`: String (nullable)
- `is_active`: Boolean
- `timestamps`

### Category (`categories` table)

- `id`: BigIncrements/UUID
- `name`: String (unique)
- `description`: Text (nullable)
- `timestamps`
- `softDeletes` (optional for safety)

### Product (`products` table)

- `id`: BigIncrements/UUID
- `category_id`: Foreign Key (`categories`)
- `name`: String
- `image_path`: String (nullable)
- `barcode`: String (unique, nullable)
- `purchase_price`: Decimal (10, 2)
- `retail_price`: Decimal (10, 2)
- `wholesale_price`: Decimal (10, 2)
- `stock`: Integer
- `min_stock`: Integer
- `is_active`: Boolean
- `timestamps`

### Transaction (`transactions` table)

- `id`: BigIncrements/UUID
- `transaction_code`: String (unique, e.g., "TRX-20240101-001")
- `user_id`: Foreign Key (`users`) - cashier
- `total_amount`: Decimal (12, 2)
- `discount_amount`: Decimal (12, 2)
- `final_amount`: Decimal (12, 2)
- `payment_method`: Enum ('cash', 'debit', 'credit', 'qris')
- `status`: Enum ('pending', 'completed', 'cancelled')
- `notes`: Text (nullable)
- `timestamps`

### TransactionItem (`transaction_items` table)

- `id`: BigIncrements
- `transaction_id`: Foreign Key (`transactions`)
- `product_id`: Foreign Key (`products`)
- `product_name`: String (snapshot)
- `quantity`: Integer
- `unit_price`: Decimal (12, 2)
- `subtotal`: Decimal (12, 2)
- `timestamps`

---

## API Endpoints Structure (v1)

Prefix: `/api/v1`

**Legend:**

- ❌ : Public Endpoint (No Auth Required)
- ✅ : Protected Endpoint (Auth Token Required)

### 🔐 Authentication (`/auth`)

| Method | Endpoint          | Description                | Auth |
| :----- | :---------------- | :------------------------- | :--- |
| POST   | `/register`       | Register new user (Kasir)  | ❌   |
| POST   | `/login`          | Login & get Sanctum token  | ❌   |
| POST   | `/logout`         | Revoke token               | ✅   |
| GET    | `/me`             | Get current user profile   | ✅   |
| POST   | `/password/email` | Send reset link (optional) | ❌   |
| POST   | `/password/reset` | Reset password             | ❌   |

### 👤 Users (`/users`)

| Method | Endpoint | Description    |
| :----- | :------- | :------------- |
| GET    | `/`      | List all users |
| POST   | `/`      | Create user    |
| PUT    | `/{id}`  | Update user    |
| DELETE | `/{id}`  | Delete user    |

### 📦 Categories (`/categories`)

| Method | Endpoint | Description     |
| :----- | :------- | :-------------- |
| GET    | `/`      | List categories |
| POST   | `/`      | Create category |
| PUT    | `/{id}`  | Update category |
| DELETE | `/{id}`  | Delete category |

### 🛍️ Products (`/products`)

| Method | Endpoint          | Description                                |
| :----- | :---------------- | :----------------------------------------- |
| GET    | `/`               | List products (search, filter, pagination) |
| GET    | `/{id}`           | Get product detail                         |
| POST   | `/`               | Create product (with image upload)         |
| PUT    | `/{id}`           | Update product                             |
| DELETE | `/{id}`           | Delete product                             |
| GET    | `/barcode/{code}` | Search by barcode                          |

### 💳 Transactions (`/transactions`)

| Method | Endpoint      | Description                   |
| :----- | :------------ | :---------------------------- |
| GET    | `/`           | List transactions             |
| POST   | `/`           | Create transaction (Checkout) |
| GET    | `/{id}`       | Get transaction detail        |
| DELETE | `/{id}`       | Cancel/Void transaction       |
| GET    | `/{id}/print` | Get receipt data/PDF          |

### 📊 Dashboard & Reports (`/dashboard`, `/reports`)

| Method | Endpoint             | Description                   |
| :----- | :------------------- | :---------------------------- |
| GET    | `/dashboard/summary` | Today's stats (income, count) |
| GET    | `/dashboard/chart`   | Sales chart data              |
| GET    | `/reports/daily`     | Daily report                  |
| GET    | `/reports/monthly`   | Monthly report                |
| GET    | `/reports/export`    | Export PDF/Excel              |

---

## Implementation Phases

### Phase 1: Setup ⚙️

- [ ] Install Laravel 11
- [ ] Configure DB (.env)
- [ ] Install API requirements (Sanctum, etc.)
- [ ] Setup CORS

### Phase 2: Auth & Users 🔐

- [ ] User Migration & Model
- [ ] Auth Controller (Login/Logout)
- [ ] User CRUD Controller
- [ ] Role Middleware

### Phase 3: Inventory (Category & Product) 📦

- [ ] Category CRUD
- [ ] Product Migration & Model
- [ ] Product CRUD with Image Upload
- [ ] Barcode Generation/Search

### Phase 4: POS & Transactions 💳

- [ ] Transaction Models
- [ ] Checkout Logic (Stock Validation, Deduction)
- [ ] Transaction History

### Phase 5: Reporting & Dashboard 📊

- [ ] Dashboard Queries (Aggregates)
- [ ] Export Logic (PDF/Excel)

---

## Why Laravel?

1.  **Rapid Development**: Built-in authentication, validation, and routing features save time.
2.  **Eloquent ORM**: Highly expressive and easy to simple database interactions.
3.  **Ecosystem**: Great packages for Excel, PDF, and Image handling.
4.  **Community**: Massive support and documentation.

## Deployment

- **Development**:
  - Use XAMPP for MySQL Database and PHP.
  - Run app via `php artisan serve` OR configure XAMPP Virtual Host.
- **Production**: XAMPP (Windows Server) or Nginx + PHP-FPM (VPS).

## Environment Variables (XAMPP Default)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=management_toko
DB_USERNAME=root
DB_PASSWORD=
```
