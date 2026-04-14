# Contributing to Expense Manager

First off, thank you for considering contributing to Expense Manager! 🎉

It's people like you that make Expense Manager such a great tool for personal finance management.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Features](#suggesting-features)
  - [Pull Requests](#pull-requests)
- [Development Setup](#development-setup)
- [Style Guides](#style-guides)
- [Commit Messages](#commit-messages)

---

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please be respectful and constructive in all interactions.

---

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates.

**When reporting a bug, include:**

- **Clear title** describing the issue
- **Steps to reproduce** the behavior
- **Expected behavior** vs actual behavior
- **Screenshots** if applicable
- **Environment details:**
  - PHP version
  - MySQL/MariaDB version
  - Browser and version
  - Operating system

### Suggesting Features

Feature suggestions are welcome! Check our [Roadmap](#roadmap) first to see if it's already planned.

**Planned features:**
- Budget management module
- Recurring transactions
- Multi-user/team support
- Data import from CSV/Excel
- Mobile app (React Native)
- Bank account integration
- Advanced reporting with PDF export
- Multi-language support

### Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/your-bug-fix
   ```
3. **Make your changes** following our style guides
4. **Test** your changes thoroughly
5. **Commit** with clear messages
6. **Push** to your fork
7. **Open a Pull Request** against `main`

---

## Development Setup

### Prerequisites

- PHP 8.1+
- Composer 2.x
- MySQL 5.7+ or MariaDB 10.3+

### Setup Steps

```bash
# 1. Fork and clone
git clone https://github.com/YOUR-USERNAME/expense-manager.git
cd expense-manager

# 2. Install dependencies
composer install

# 3. Configure database
cp config/db.example.php config/db.php
# Edit config/db.php with your local database credentials

# 4. Run migrations
php yii migrate

# 5. Set permissions
chmod -R 777 runtime web/assets web/uploads

# 6. (Optional) Seed demo data
php yii seed/demo

# 7. Start development server
php yii serve
# Visit http://localhost:8080
```

### Demo Account

After seeding:
- **Email:** demo@example.com
- **Password:** demo123

---

## Style Guides

### PHP Code Style

We follow **PSR-12** coding standards with Yii2 conventions.

```php
<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class description
 *
 * @property int $id
 * @property string $name
 *
 * @author Your Name <your.email@example.com>
 * @since 1.0.0
 */
class MyModel extends ActiveRecord
{
    /**
     * Method description
     *
     * @param string $param Parameter description
     * @return bool Return description
     */
    public function myMethod(string $param): bool
    {
        return true;
    }
}
```

**Guidelines:**
- Use **type hints** for parameters and return types
- Write **PHPDoc blocks** for classes and methods
- Use **meaningful names** for variables and methods
- **4 spaces** for indentation (no tabs)
- Add **@author** and **@since** tags

### JavaScript Style

- Use **ES6+** features
- Prefer **const** over let
- Use the **NEM** namespace for global functions

```javascript
// Use NEM namespace
NEM.Toast.success('Record saved');

// Use const/let
const calculateTotal = (amounts) => {
    return amounts.reduce((sum, amount) => sum + amount, 0);
};
```

### CSS Style

- Use **Bootstrap 5** utilities when possible
- Custom styles in `web/css/`
- Use CSS variables for theming

---

## Commit Messages

We follow **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation |
| `style` | Code style (formatting) |
| `refactor` | Code refactoring |
| `perf` | Performance improvements |
| `test` | Adding tests |
| `chore` | Maintenance |

### Examples

```bash
feat(expense): add bulk delete functionality
fix(auth): resolve session timeout issue
docs(readme): update installation instructions
refactor(dashboard): extract widgets into components
```

---

## Questions?

Feel free to [open an issue](https://github.com/mohsin-rafique/expense-manager/issues/new) with the `question` label.

**Thank you for contributing!** 🙌
