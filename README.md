# Kimsreng Portfolio — Setup & Feature Guide

A modern full-stack developer portfolio featuring user authentication, Google OAuth sign-in & registration, password hashing (bcrypt), an admin management dashboard, and Dark/Light mode theme switching.

---

## 🌟 Key Features

1. **Google Sign-In & Register Integration**:
   - Seamless one-click Google Sign-in and Google Registration via Google Identity Services (GIS).
   - Auto-creation of user accounts from verified Google profiles with avatar support.
   - Configurable Google Client ID in `includes/config.php`.

2. **Secure Password Hashing & Transparent Auto-Upgrade**:
   - Secure passwords using PHP's native `password_hash()` and `password_verify()` with `bcrypt`.
   - Transparent legacy upgrade: automatically upgrades unhashed/plaintext passwords to bcrypt on successful login.

3. **Dark / Light Mode Theme Switching**:
   - Instant theme toggle button in navigation with smooth Sun/Moon icon transitions.
   - Zero flash of unstyled content (no FOUC) via inline `localStorage` theme loader in `<head>`.
   - Theme-aware SweetAlert2 dialogs, tables, cards, hero, and admin dashboard.

4. **Authenticated Contact System & Email Notification**:
   - Condition: Users must log in or register before they can send messages to Admin.
   - Contact inquiries are saved to database and forwarded immediately to Admin Email (`songkimsreng001@gmail.com`).
   - Admin can respond directly via email from the Admin Dashboard with full message thread history.

5. **Admin Dashboard**:
   - Track total users, Google SSO accounts, total contact inquiries, and pending replies.
   - Manage users with safety checks (prevents self-deletion).
   - View, reply to, and delete user messages with one-click Email App integration.

---

## 📋 Requirements
- PHP 7.4+ (PHP 8.x recommended)
- MySQL 5.7+ / MariaDB
- XAMPP / WAMP / MAMP or Apache/Nginx with PHP & MySQL

---

## 🚀 Setup Steps

### 1. Place the Project
Copy the `portfolio/` folder into your local web server root:
- **XAMPP** → `C:/xampp/htdocs/portfolio` or `D:/xampp/htdocs/portfolio`
- **WAMP** → `C:/wamp64/www/portfolio`

Then visit: `http://localhost/portfolio/`

### 2. Import Database
1. Open phpMyAdmin → `http://localhost/phpmyadmin`
2. Create or select a database named `portfolio` (or `portfolio_db`)
3. Import `sql/portfolio.sql`

*Note: The application automatically checks and updates table columns (`google_id`, `avatar`, `updated_at`) on launch.*

### 3. Configure Database & Google OAuth
Open `includes/db.php` and `includes/config.php`:
```php
// includes/db.php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'portfolio');
```

```php
// includes/config.php
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
```

#### How to get a Google Client ID (optional for production):
1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
2. Create a project → Go to **APIs & Services** > **Credentials**.
3. Create **OAuth 2.0 Client ID** (Web application).
4. Add `http://localhost` and `http://localhost/portfolio` to **Authorized JavaScript origins**.
5. Paste your Client ID into `includes/config.php`.

---

## 🔐 Default Admin Account
- **Email:** `admin@portfolio.com`
- **Password:** `Admin@1234`

---

## 📁 File Structure
```
portfolio/
├── index.php                  ← Main portfolio page (Home, About, Skills, Projects, Contact)
├── login.php                  ← Login with Email & Google Sign-In
├── register.php               ← Register with Email & Google Sign-Up
├── logout.php                 ← Session destroy and redirect
├── google-auth.php            ← Backend Google OAuth token verification & SSO handler
├── oauth/
│   └── callback.php           ← Google OAuth 2.0 callback handler
├── dashboard/
│   └── admin.php              ← Admin panel (stats, users table, delete user)
├── includes/
│   ├── config.php             ← App settings & Google Client ID configuration
│   ├── db.php                 ← DB connection & auto-migration
│   ├── auth.php               ← Session helpers (isLoggedIn, isAdmin, getCurrentUser...)
│   ├── navbar.php             ← Navigation bar with Dark/Light toggle and profile
│   ├── header.php             ← Head tag, inline theme loader, Google GIS script, CSS
│   └── footer.php             ← Footer & script bundle
├── assets/
│   ├── css/style.css          ← Dark & Light mode styles, UI tokens, animations
│   ├── js/main.js             ← Theme manager, animations, scroll effects
│   ├── images/                ← Profile images and assets
│   └── cv/                    ← CV / Resume download
└── sql/
    └── portfolio.sql          ← Database schema & default admin
```
