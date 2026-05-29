# Fashion Model Portfolio — Laravel 12

A premium fashion model portfolio website with a public-facing site and an admin panel to manage portfolio images, about content, social links, and contact messages.

## Requirements

- PHP 8.2+ (8.3 recommended)
- Composer
- MySQL 5.7+ / MariaDB
- Node.js (optional, for asset building)

## Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Blade templates
- Bootstrap 5
- Session-based admin authentication

## Installation

### 1. Clone / navigate to project

```bash
cd c:\xampp\htdocs\model-portfolio
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=model_portfolio
DB_USERNAME=root
DB_PASSWORD=
```

Create the MySQL database:

```sql
CREATE DATABASE model_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5. Start the development server

```bash
php artisan serve
```

Visit:
- **Public site:** http://127.0.0.1:8000
- **Admin panel:** http://127.0.0.1:8000/admin/login

### XAMPP (Apache)

Point your virtual host document root to `model-portfolio/public`, or access via:

```
http://localhost/model-portfolio/public
```

Ensure `mod_rewrite` is enabled and `AllowOverride All` is set for the directory.

## Default Admin Credentials

| Field    | Value              |
|----------|--------------------|
| Email    | admin@example.com  |
| Password | password           |

**Change these immediately after first login in production.**

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin CRUD controllers
│   │   └── Public/         # Public page controllers
│   ├── Middleware/
│   │   └── EnsureUserIsAdmin.php
│   └── Requests/           # Form validation
├── Models/
├── Services/
│   └── ImageUploadService.php
database/
├── migrations/
└── seeders/
resources/views/
├── layouts/                # public.blade.php, admin.blade.php
├── public/                 # Home, Portfolio, About, Contact
└── admin/                  # Admin panel views
public/
├── css/                    # public.css, admin.css
└── js/                     # public.js
```

## Features

### Public Website
- Hero section with model name, title, and background image
- Portfolio grid with category filters and lightbox preview
- Image skeleton loaders while loading
- About page with story and images
- Contact page with WhatsApp button and contact form
- SEO meta tags from admin settings
- Responsive luxury UI (black, gold, beige palette)

### Admin Panel
- Secure login (admin users only)
- Dashboard with statistics and quick actions
- Portfolio image CRUD with drag-and-drop reorder
- Category management
- About content editor
- Social media & WhatsApp settings
- Contact message inbox (read/unread)

## Image Uploads

Images are stored in `storage/app/public` and served via the public disk:

```bash
php artisan storage:link
```

Allowed formats: JPG, JPEG, PNG, WEBP (max 5MB)

## Default Categories (Seeded)

- Fashion
- Casual
- Sportswear
- Commercial
- Editorial

## License

MIT
