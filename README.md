<p align="center">
  <img src="web/apple-touch-icon.png" alt="Expense Manager" width="120" />
</p>

<h1 align="center">Expense Manager</h1>

<p align="center">
  <strong>A modern, open-source personal finance management application built with Yii2</strong>
</p>

<p align="center">
  <a href="https://github.com/mohsin-rafique/expense-manager/releases">
    <img src="https://img.shields.io/github/v/release/mohsin-rafique/expense-manager?style=flat-square" alt="Latest Release" />
  </a>
  <a href="https://github.com/mohsin-rafique/expense-manager/blob/master/LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License" />
  </a>
  <a href="https://github.com/mohsin-rafique/expense-manager/stargazers">
    <img src="https://img.shields.io/github/stars/mohsin-rafique/expense-manager?style=flat-square" alt="Stars" />
  </a>
  <a href="https://github.com/mohsin-rafique/expense-manager/issues">
    <img src="https://img.shields.io/github/issues/mohsin-rafique/expense-manager?style=flat-square" alt="Issues" />
  </a>
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP Version" />
  <img src="https://img.shields.io/badge/Yii2-Framework-00A550?style=flat-square" alt="Yii2" />
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white" alt="Bootstrap 5" />
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#screenshots">Screenshots</a> •
  <a href="#installation">Installation</a> •
  <a href="#configuration">Configuration</a> •
  <a href="#usage">Usage</a> •
  <a href="#contributing">Contributing</a> •
  <a href="#support">Support</a> •
  <a href="#license">License</a>
</p>

---

## Overview

**Expense Manager** is a free, open-source personal finance application designed to help individuals and small businesses track income and expenses, organize transactions into categories, and gain meaningful insights into their financial activities.

Built on the robust **Yii2 PHP framework** with **Bootstrap 5**, it offers a clean, modern interface with powerful features — all while remaining lightweight and easy to self-host.

### Why Expense Manager?

- 🆓 **100% Free & Open Source** — No hidden costs, no subscriptions
- 🔒 **Self-Hosted** — Your financial data stays on your server
- 🚀 **Lightweight** — Runs on minimal server resources
- 🎨 **Modern UI** — Clean Bootstrap 5 design with dark/light themes
- 📱 **Responsive** — Works on desktop, tablet, and mobile

---

## Features

### 💰 Income Management

- Record and track all income sources
- Categorize income with custom categories
- Attach receipts and invoices (PDF, images)
- Search and filter by date, category, reference
- Export income data to Excel

### 💸 Expense Management

- Track expenses with detailed information
- Hierarchical expense categories (parent/child)
- Multiple payment methods (Cash, Card, Bank)
- File attachments for receipts
- Advanced filtering and search
- Export expense data to Excel

### 📊 Dashboard & Reports

- Financial overview dashboard
- Income vs Expense summary cards
- Monthly/yearly statistics
- Category-wise breakdown
- Visual charts and graphs
- Balance tracking

### 👤 User Management

- Secure authentication system
- User profile management
- Password reset functionality
- Remember me option
- Session management

### ⚙️ Settings & Customization

- Multi-currency support (50+ currencies)
- Customizable currency formatting
- Date and time format preferences
- Company/business branding
- Logo and favicon upload
- Timezone configuration

### 🎨 Modern UI/UX

- Clean, responsive Bootstrap 5 design
- Dark/Light theme toggle
- Mobile-friendly interface
- AJAX-powered interactions (PJAX)
- Toast notifications
- Modal-based forms

---

## Screenshots

<p align="center">
  <img src="docs/screenshots/dashboard.png" alt="Dashboard" width="100%" />
  <br><em>Dashboard — Financial overview at a glance</em>
</p>

<p align="center">
  <img src="docs/screenshots/income-category.png" alt="Income Categories" width="100%" />
  <br><em>Income Categories — Organize your income sources</em>
</p>

<p align="center">
  <img src="docs/screenshots/income.png" alt="Income" width="100%" />
  <br><em>Income — Track all your earnings</em>
</p>

<p align="center">
  <img src="docs/screenshots/expense-category-grid.png" alt="Expense Category Grid View" width="100%" />
  <br><em>Expense Categories — Grid view</em>
</p>

<p align="center">
  <img src="docs/screenshots/expense-category-tree.png" alt="Expense Category Tree View" width="100%" />
  <br><em>Expense Categories — Hierarchical tree view</em>
</p>

<p align="center">
  <img src="docs/screenshots/expenses.png" alt="Expenses" width="100%" />
  <br><em>Expenses — Detailed expense tracking</em>
</p>

<p align="center">
  <img src="docs/screenshots/profile.png" alt="Profile" width="100%" />
  <br><em>Profile — User settings and preferences</em>
</p>

---

## Requirements

| Requirement     | Version        |
| --------------- | -------------- |
| PHP             | 8.1 or higher  |
| MySQL / MariaDB | 5.7+ / 10.3+   |
| Composer        | 2.x            |
| Web Server      | Apache / Nginx |

### PHP Extensions Required

- `pdo_mysql`
- `mbstring`
- `intl`
- `gd` or `imagick`
- `json`
- `openssl`

---

## Installation

### Option 1: Install via Composer (Recommended)

```bash
# Create project
composer create-project mohsin-rafique/expense-manager expense-manager

# Navigate to project
cd expense-manager

# Install dependencies
composer install

# Set permissions
chmod -R 777 runtime web/assets web/uploads
```

### Option 2: Install from GitHub

```bash
# Clone repository
git clone https://github.com/mohsin-rafique/expense-manager.git

# Navigate to project
cd expense-manager

# Install dependencies
composer install

# Set permissions
chmod -R 777 runtime web/assets web/uploads
```

### Option 3: Download ZIP

1. Download from [GitHub Releases](https://github.com/mohsin-rafique/expense-manager/releases)
2. Extract to your web root
3. Run `composer install`
4. Set directory permissions

---

## Configuration

### 1. Database Setup

Create a new MySQL database:

```sql
CREATE DATABASE expense_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure database connection — copy the example env file and fill in your credentials:

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_DSN=mysql:host=localhost;dbname=expense_manager
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

> Your `.env` file is gitignored — your credentials are never committed to the repository.

### 2. Environment Configuration

Configure the full `.env` file for your environment:

```env
# Application
YII_DEBUG=false
YII_ENV=prod

# Database
DB_DSN=mysql:host=localhost;dbname=expense_manager
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4

# Session Security (set SESSION_SECURE=true when using HTTPS)
SESSION_SECURE=false
SESSION_SAMESITE=Lax
```

> ⚠️ Never set `YII_DEBUG=true` in production — it exposes stack traces and file paths.

### 3. Run Migrations

```bash
# Run all migrations
php yii migrate

# This creates all required tables:
# - user
# - profile
# - settings
# - income_categories
# - incomes
# - expense_categories
# - expenses
```

### 4. Seed Demo Data (Optional)

```bash
php yii seed/demo
```

This creates a demo account with sample data so you can explore the app immediately.

| Field    | Value            |
| -------- | ---------------- |
| Email    | demo@example.com |
| Password | demo123          |

> ⚠️ **Important:** Change or remove the demo account before using in production.

### 5. Configure Application

Update `config/web.php` with a unique cookie validation key:

```php
'request' => [
    'cookieValidationKey' => 'your-unique-random-string-here',
],
```

### 6. Web Server Configuration

#### Apache (.htaccess)

The `.htaccess` file is included in the `web/` directory. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/expense-manager/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    location ~ /\.(ht|git) {
        deny all;
    }
}
```

---

## Usage

### Getting Started

1. **Access the application**: Navigate to `http://your-domain.com` or `http://localhost/expense-manager/web/`

2. **Login with demo account** or **Register a new account**
    - Demo: `demo@example.com` / `demo123` (if you ran `php yii seed/demo`)

3. **Configure settings**: Set up your currency, timezone, and company details

4. **Create categories**: Add income and expense categories

5. **Start tracking**: Record your income and expenses

### Managing Income

1. Navigate to **Income → All Income**
2. Click **Add Income** to create a new record
3. Fill in the details:
    - Select date
    - Choose category
    - Enter amount
    - Add reference/description (optional)
    - Attach receipt (optional)
4. Use filters to search and organize records
5. Export to Excel for external reporting

### Managing Expenses

1. Navigate to **Expenses → All Expenses**
2. Click **Add Expense** to create a new record
3. Fill in the details:
    - Select date
    - Choose category
    - Enter amount
    - Select payment method (Cash/Card/Bank)
    - Add reference/description (optional)
    - Attach receipt (optional)
4. Use filters to search and organize records
5. Export to Excel for external reporting

### Managing Categories

**Income Categories:**

- Navigate to **Income → Categories**
- Add, edit, or delete categories
- Customize with icons and colors

**Expense Categories:**

- Navigate to **Expenses → Categories**
- Supports hierarchical structure (parent/child)
- Drag-and-drop organization
- Customize with icons and colors

### Dashboard & Reports

- **Dashboard**: Overview of financial status with summary cards
- **Reports**: Detailed financial reports with charts
- **Widgets**: Quick stats for income, expenses, and balance

---

## Project Structure

```
expense-manager/
├── assets/                 # Asset bundles
├── commands/               # Console commands
├── components/             # Application components
│   ├── BalanceHelper.php
│   ├── CurrencyFormatter.php
│   └── ...
├── config/                 # Configuration files
│   ├── db.php
│   ├── params.php
│   └── web.php
├── controllers/            # Web controllers
│   ├── ExpenseController.php
│   ├── IncomeController.php
│   └── ...
├── migrations/             # Database migrations
├── models/                 # ActiveRecord models
│   ├── Expense.php
│   ├── Income.php
│   └── ...
├── runtime/                # Runtime files (logs, cache)
├── views/                  # View templates
│   ├── expenses/
│   ├── incomes/
│   ├── layouts/
│   └── ...
├── web/                    # Web root
│   ├── css/
│   ├── js/
│   ├── uploads/
│   └── index.php
├── widgets/                # Custom widgets
├── composer.json
├── LICENSE
└── README.md
```

---

## Development

### Code Style

This project follows [Yii2 Coding Standards](https://github.com/yiisoft/yii2-coding-standards):

```bash
# Install Yii2 coding standards
composer require --dev yiisoft/yii2-coding-standards

# Check code style
php vendor/bin/phpcs --standard=Yii2 controllers models components widgets

# Fix code style
php vendor/bin/phpcbf --standard=Yii2 controllers models components widgets
```

### Debug Mode

Debug mode is controlled via your `.env` file — it defaults to production-safe values:

```env
YII_DEBUG=true
YII_ENV=dev
```

> ⚠️ Never set `YII_DEBUG=true` in production — it exposes stack traces and internal file paths.

---

## Contributing

Contributions are welcome and greatly appreciated!

### How to Contribute

1. **Fork** the repository
2. **Clone** your fork: `git clone https://github.com/YOUR-USERNAME/expense-manager.git`
3. **Create** a feature branch: `git checkout -b feature/amazing-feature`
4. **Commit** your changes: `git commit -m 'Add amazing feature'`
5. **Push** to branch: `git push origin feature/amazing-feature`
6. **Open** a Pull Request

### Contribution Guidelines

- Follow [Yii2 coding standards](https://github.com/yiisoft/yii2-coding-standards)
- Write meaningful commit messages
- Update documentation as needed
- Be respectful in discussions

### Reporting Issues

Found a bug? Please [open an issue](https://github.com/mohsin-rafique/expense-manager/issues/new) with:

- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- Screenshots (if applicable)
- Environment details (PHP version, OS, etc.)

---

## Support

If this project helps you, consider supporting its development:

<p align="center">
  <a href="https://wise.com/pay/me/mohsinr301">
    <img src="https://img.shields.io/badge/Donate-Wise-00B9FF?style=for-the-badge&logo=wise&logoColor=white" alt="Donate via Wise" />
  </a>
</p>

### Other Ways to Support

- ⭐ **Star** this repository
- 🐛 **Report bugs** and suggest features
- 📖 **Improve documentation**
- 📢 **Share** with others who might find it useful

---

## Roadmap

- [ ] Multi-language support (i18n)
- [ ] Data import from CSV/Excel
- [ ] Advanced reporting with PDF export
- [ ] Budget management module
- [ ] Multi-user/team support
- [ ] API Development (Yii2 REST)
- [ ] Mobile app (React Native)

---

## Changelog

### v1.0.1 — 2026-04-05

#### Security

- **Debug mode** now controlled via `.env` (`YII_DEBUG`, `YII_ENV`) — defaults to production-safe `false` for self-hosted deployments
- **Session cookies** hardened — `secure` and `sameSite` now configurable via `.env` (`SESSION_SECURE`, `SESSION_SAMESITE`)
- **Login rate limiting** — max 5 failed attempts per IP per 15 minutes to prevent brute-force attacks
- **Database credentials** moved to `.env` — no more hardcoded values in `config/db.php`

#### Code Quality

- Added PHPDoc class-level and method-level comments across all components, models, widgets, and controllers following Yii2 coding standards
- Added PHP 8.1 type declarations (property types, parameter types, return types) throughout the codebase
- Fixed `BalanceHelper::getBalance()` — now casts `queryScalar()` result to `float` to handle new users with no data
- Fixed `ResetPasswordForm::sendEmail()` — corrected return type inconsistency
- Removed Gii-generated noise from `User` model `@property` annotations

### v1.0.0 — Initial Release

- Initial public release

---

## Changelog

### v1.0.1 — 2026-04-05

#### Security

- **Debug mode** now controlled via `.env` (`YII_DEBUG`, `YII_ENV`) — defaults to production-safe `false` for self-hosted deployments
- **Session cookies** hardened — `secure` and `sameSite` now configurable via `.env` (`SESSION_SECURE`, `SESSION_SAMESITE`)
- **Login rate limiting** — max 5 failed attempts per IP per 15 minutes to prevent brute-force attacks
- **Database credentials** moved to `.env` — no more hardcoded values in `config/db.php`

#### Code Quality

- Added PHPDoc class-level and method-level comments across all components, models, widgets, and controllers following Yii2 coding standards
- Added PHP 8.1 type declarations (property types, parameter types, return types) throughout the codebase
- Fixed `BalanceHelper::getBalance()` — casts `queryScalar()` result to `float` to handle users with no transactions
- Fixed `ResetPasswordForm::sendEmail()` — corrected return type inconsistency
- Cleaned up Gii-generated noise from `User` model `@property` annotations

### v1.0.0 — Initial Release

- Initial public release

---

## License

This project is open-source software licensed under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2025 Mohsin Rafique

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## Acknowledgments

- [Yii Framework](https://www.yiiframework.com/) — The fast, secure, and professional PHP framework
- [Bootstrap](https://getbootstrap.com/) — The world's most popular front-end toolkit
- [Bootstrap Icons](https://icons.getbootstrap.com/) — Free, high-quality icons
- All [contributors](https://github.com/mohsin-rafique/expense-manager/graphs/contributors) who help improve this project

---

## Author

<p align="center">
  <a href="https://github.com/mohsin-rafique">
    <img src="https://avatars.githubusercontent.com/u/993323" alt="Mohsin Rafique" width="100" style="border-radius: 50%;" />
  </a>
</p>

<p align="center">
  <strong>Mohsin Rafique</strong><br>
  Full Stack Developer
</p>

<p align="center">
  <a href="https://github.com/mohsin-rafique">GitHub</a> •
  <a href="https://mohsinrafique.com">Website</a> •
  <a href="mailto:mohsin.rafique@gmail.com">Email</a>
</p>

---

<p align="center">
  ⭐ Star this repository if you find it useful!
</p>

<p align="center">
  <a href="#expense-manager">Back to Top ↑</a>
</p>
