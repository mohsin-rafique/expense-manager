# Changelog

All notable changes to **Expense Manager** are documented in this file.

This project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html) - `MAJOR.MINOR.PATCH` - and the format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

> **For business owners:** Each release represents tested, deployed improvements to reliability, security, and usability. You can trust that every version listed here is production-validated before tagging.

---

## [Unreleased]

### Planned

The following capabilities are actively being scoped and will be released in upcoming versions:

- **Recurring Transactions** - Auto-generate periodic income and expense entries on daily, weekly, or monthly schedules
- **Mobile App** - React Native companion application via a Yii2 REST API
- **Bank Account Integration** - Connect external banking APIs for automatic transaction import

---

## [1.2.0] - 2026-06-15

> **Summary:** The largest feature release since 1.0.0. Expense Manager grows from a single-user tracker into a collaborative financial platform: shared **team workspaces** with role-based access, a full **budget management** module with overspend alerts, downloadable **PDF financial reports**, **multi-language** support across 5 languages, and **bulk CSV/Excel import**. Existing data migrates automatically with no loss.

### Added

#### 🎯 Budget Management

- Per-category spending budgets across **monthly, yearly, and fiscal-year** periods, tracked against the current period automatically
- Configurable **alert threshold** per budget with color-coded progress (on-track / approaching / over-budget)
- **In-app toast alerts** the moment a saved expense pushes a category over its threshold, plus optional **email alerts**
- Dashboard **Budget Overview** widget highlighting at-risk categories; child-category spending rolls up to the parent budget

#### 👥 Multi-user / Team Workspaces

- **Shared workspaces** for collaborating on income, expenses, categories, and budgets
- **Role-based access control**: Owner (full control + delete), Admin (manage members + data), Member (manage data), Viewer (read-only)
- **Email invitations** for existing and brand-new users with token-based acceptance (new sign-ups auto-join on registration)
- One-click **workspace switcher** in the navbar; every user keeps a private personal workspace
- Server-side capability enforcement; existing records migrate into each user's personal workspace with no data loss

#### 📄 Advanced PDF Reporting

- Downloadable, professionally branded **PDF financial reports** powered by mPDF
- Four report types: **Financial Summary**, **Category Breakdown**, **Income vs Expense** trend, and **Budget Status**
- Flexible periods: any month, fiscal year, custom date range, and all-time
- Branded header, summary metric cards, percentage bars, per-page footers, and full **Unicode + right-to-left** rendering (including Urdu)

#### 🌐 Multi-language Support (i18n)

- Full UI localization in **5 languages**: English, Spanish (Español), French (Français), Urdu (اردو), and German (Deutsch)
- In-navbar **language switcher**; per-user preference saved to the database and remembered across sessions
- Automatic language detection for guests via the `Accept-Language` header, with cookie persistence
- **Right-to-left (RTL)** layout enabled automatically for Urdu
- Built on Yii2's native `Yii::t()` framework with PHP message catalogs and graceful fallback to English

#### 📥 Data Import (CSV / Excel)

- Bulk-import **expenses and income** from `.csv`, `.xlsx`, and `.xls` files
- **Preview before commit** - every row is validated and shown with an OK / duplicate / skip status before anything is written
- Header-based column mapping (order-independent); tolerant of currency symbols, thousands separators, and multiple date formats
- **Auto-create missing categories** and **skip duplicates** (toggleable per import), with a downloadable template per type

#### 💸 Expense Enhancements

- **FBR category support** for Pakistani tax tracking, shown automatically for users with a Pakistan profile
- **In-browser receipt auto-read (Beta)** - attach a receipt photo to auto-fill date, amount, payment method, reference, and description; runs entirely in the browser with nothing uploaded for processing
- **Searchable category and payment dropdowns** so the hierarchical category tree can be searched instead of scrolled

### Changed

- Replaced plain category and payment selects with **searchable dropdowns** across the Expense and Income modules
- Aligned the **Budget module UI** with the Income/Expense modules: summary cards, a collapsible Filter & Search panel, a consistent module color scheme, and a section icon
- **Documentation overhaul** - README refreshed for all new modules, with updated project structure and usage guides
- Updated copyright to **2025 - 2026** across the project
- Corrected documentation that referenced a dark/light theme toggle which is not currently available

### Fixed

- **Receipt auto-read date parsing** - prefers the labeled date line, tolerates OCR spacing, and avoids future-dated reads when the day/month order is ambiguous
- **Receipt auto-read description** - skips document headers and OCR noise when detecting the merchant name
- **Searchable category dropdowns** now always expose the search box, even for short lists such as income categories
- Themed the searchable dropdowns to match Bootstrap form controls (sizing, focus ring, and dropdown panel)

---

## [1.1.0] - 2026-04-10

> **Summary:** This release focuses on export quality and API consistency. Income and category exports were upgraded from plain CSV to fully styled XLSX files. All AJAX endpoints now return a standardized JSON envelope, making the frontend more predictable and easier to maintain.

### Added

- `ApiResponse` - a new helper component providing a standardized AJAX response structure (`{ status, message }`) used consistently across all controllers
- Export mode in `IncomeCategorySearch::search()` - disables pagination so the full dataset is always included when exporting, regardless of active filters

### Changed

- **XLSX exports for Income and Income Categories** - replaced raw CSV output with professionally formatted spreadsheets:
  - Bootstrap-branded green (`#198754`) header row with white bold text
  - Frozen header panes for easy scrolling through large datasets
  - Zebra-striped rows and thin cell borders for readability
  - Currency-formatted amount columns and human-readable dates (e.g., `Feb 25, 2026`)
  - Word-wrap applied to long-text fields (Reference, Description)
- **Unified AJAX response envelope** - `ExpenseController`, `ExpenseCategoryController`, `IncomeController`, `IncomeCategoryController`, and `ProfileController` all now return `ApiResponse`-structured JSON
- Added PHP return types to all `SiteController` action methods for static analysis compatibility

### Fixed

- **Export ignoring active filters** - exports now correctly respect all active search and filter parameters; previously, exports always returned unfiltered data regardless of what the user had searched
- **XLSX file corruption** - resolved by switching from `php://output` direct buffer streaming to a temp-file-based approach before sending the download response
- **Intelephense P1013 warning** in `ProfileController` - resolved via explicit `instanceof User` guard before accessing model properties

---

## [1.0.1] - 2026-04-05

> **Summary:** A focused security and stability release. All environment-sensitive configuration was moved to `.env`, login rate limiting was introduced, and several type-safety issues identified during code review were corrected.

### Security

- **Debug mode via `.env`** - `YII_DEBUG` and `YII_ENV` are now exclusively controlled through the environment file, defaulting to production-safe values (`false` / `prod`) - no risk of accidentally shipping a debug build
- **Session cookie hardening** - `secure` and `sameSite` flags are now configurable via `.env` (`SESSION_SECURE`, `SESSION_SAMESITE`), making HTTPS-only deployments straightforward
- **Login rate limiting** - a maximum of 5 failed login attempts per IP address per 15-minute window is now enforced, protecting against brute-force attacks
- **Database credentials in `.env`** - all connection parameters moved out of `config/db.php` into environment variables; no hardcoded credentials anywhere in the codebase

### Fixed

- `BalanceHelper::getBalance()` - explicit cast of `queryScalar()` result to `float` prevents a type error for new users who have no financial records yet
- `ResetPasswordForm::sendEmail()` - corrected a return type inconsistency that caused a static analysis failure in strict mode
- Removed Gii-generated noise from the `User` model's `@property` annotations, reducing IDE confusion and false positive warnings

### Changed

- Added PHPDoc class and method comments across components, models, widgets, and controllers - improving IDE autocomplete and onboarding for new developers
- Applied PHP 8.1 type declarations (parameter types, return types, property types) throughout the codebase for improved type safety and static analysis coverage

---

## [1.0.0] - 2026-01-08

> **Summary:** Initial stable release. A fully functional, production-ready personal finance management application covering income tracking, expense management, category organization, dashboard reporting, user profiles, and application settings.

### Added

#### 💰 Income Management

- Record and track all income sources with date, amount, category, and optional reference
- Categorize income with fully customizable categories (name, icon, color)
- Attach receipts and invoices (PDF, JPG, PNG) directly to each income record
- Filter and search income records by date range, category, and reference keyword
- Export income data to Excel (XLSX)

#### 💸 Expense Management

- Track expenses with date, amount, category, payment method, reference, and notes
- Hierarchical expense categories with parent/child nesting for real-world business structures
- Multiple payment methods: Cash, Card, Bank Transfer
- File attachment support for receipts and invoices
- Advanced filtering, full-text search, and paginated grid view
- Export expense data to Excel (XLSX)

#### 📊 Dashboard & Reporting

- Financial overview dashboard with live summary cards (total income, total expenses, balance)
- Income vs. Expense comparison with monthly and yearly breakdowns
- Category-wise spending analysis
- Interactive charts and graphs powered by ApexCharts
- Real-time balance tracking widget

#### 👤 User Management

- Secure user registration and login with email-based authentication
- Custom avatar upload with automatic server-side image resizing
- Custom profile banner image upload
- Password reset via secure email token link
- "Remember me" persistent login sessions
- Last login timestamp and registration IP logging
- Full session lifecycle management

#### ⚙️ Settings & Customization

- 50+ currencies supported with fully configurable symbol, decimal separator, and placement
- Date and time format preferences per user
- Timezone configuration
- Company name, logo, and favicon upload for white-label branding
- Database backup and export from within the application interface

#### 🎨 Modern UI / UX

- Responsive Bootstrap 5.3 layout - works on desktop, tablet, and mobile
- Dark and Light theme toggle with preference persisted per user
- PJAX-powered navigation - fast partial page rendering without full reloads
- AJAX modal forms for all Create, Edit, View, and Delete operations
- Toast notification system (NEM Toast) with success, warning, and error states
- Bootstrap Icons throughout the interface

#### 🛠 Developer Features

- Database seeder for realistic demo data (`php yii seed/demo`)
- Complete versioned database migration suite
- PSR-4 autoloading via Composer
- Modular widget architecture for reusable UI components
- i18n-ready translation structure for future multi-language support
- Clean URL routing via Yii2 URL manager

### Technical Stack

| Layer | Technology |
|---|---|
| Framework | Yii2 v2.0.53 |
| Language | PHP 8.1+ |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | Bootstrap 5.3 |
| Icons | Bootstrap Icons |
| Charts | ApexCharts |
| XLSX Export | PhpSpreadsheet |

### Security (Baseline)

- CSRF protection on all POST forms (Yii2 built-in)
- Password hashing with bcrypt
- SQL injection prevention via PDO prepared statements
- XSS protection via Yii2 output encoding
- Secure session handling with cookie validation
- Clean URL routing - no sensitive parameters exposed in query strings

---

## Version History

| Version | Date | Highlights |
|---|---|---|
| [1.2.0](#120--2026-06-15) | 2026-06-15 | Budgets, team workspaces, PDF reporting, multi-language (i18n), CSV/Excel import |
| [1.1.0](#110--2026-04-10) | 2026-04-10 | Professional XLSX exports, unified AJAX response system, code quality improvements |
| [1.0.1](#101--2026-04-05) | 2026-04-05 | Security hardening, login rate limiting, PHPDoc, bug fixes |
| [1.0.0](#100--2026-01-08) | 2026-01-08 | Initial stable release - full income/expense/dashboard system |

---

## Upgrade Guide

### Fresh Installation

1. Clone or download the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database credentials
4. Run `php yii migrate` to create all database tables
5. *(Optional)* Run `php yii seed/demo` to populate demo data

---

[Unreleased]: https://github.com/mohsin-rafique/expense-manager/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/mohsin-rafique/expense-manager/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/mohsin-rafique/expense-manager/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/mohsin-rafique/expense-manager/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/mohsin-rafique/expense-manager/releases/tag/v1.0.0
