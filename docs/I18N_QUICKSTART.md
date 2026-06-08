# Multi-Language Support - Quick Start Guide

## What's New

The Expense Manager now includes full multi-language support (i18n) with support for 5 languages out of the box:

- 🇬🇧 English (English)
- 🇪🇸 Spanish (Español)
- 🇫🇷 French (Français)
- 🇵🇰 Urdu (اردو)
- 🇩🇪 German (Deutsch)

## Getting Started

### 1. Run the Database Migration

Add the language field to the settings table:

```bash
php yii migrate
```

This creates the `language` column in the `settings` table with a default value of 'en'.

### 2. Language Switcher

A language switcher dropdown now appears in the navigation bar. Users can:

- **Click the globe icon** (🌐) in the top-right corner
- **Select their preferred language** from the dropdown
- **Changes are saved automatically** (for authenticated users)

### 3. How Language is Determined

**For Logged-In Users:**
- Language is loaded from their settings (saved in database)
- Can be changed via the language switcher

**For Guests:**
- Browser's preferred language is detected automatically
- Falls back to English if browser language isn't supported

## Using Translations in Your Code

### In View Files

```php
<?= Yii::t('app', 'Login') ?>
<?= Yii::t('app', 'Dashboard') ?>
```

### In Controllers

```php
Yii::$app->session->setFlash('success', Yii::t('app', 'Operation completed successfully.'));
```

### In Models

```php
public function attributeLabels(): array
{
    return [
        'username' => Yii::t('app', 'Username'),
        'email' => Yii::t('app', 'Email'),
    ];
}
```

## Adding Translations for New Strings

When you add new strings to your application:

1. **Use the translation function:**
   ```php
   echo Yii::t('app', 'New string');
   ```

2. **Add the translation to all language files:**
   - `messages/en/app.php`
   - `messages/es/app.php`
   - `messages/fr/app.php`
   - `messages/ur/app.php`
   - `messages/de/app.php`

   ```php
   'New string' => 'Translated text in that language',
   ```

## Adding a New Language

To support an additional language (e.g., Portuguese):

### Step 1: Create Language Files

Create a new directory and file:

```
messages/pt/app.php
```

### Step 2: Add Translations

Copy from an existing language file and translate all strings:

```php
<?php
return [
    'Login' => 'Entrar',
    'Dashboard' => 'Painel de Controle',
    // ... all other translations
];
```

### Step 3: Update Configuration

**Edit `config/params.php`:**

```php
'supportedLanguages' => [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'ur' => 'اردو',
    'de' => 'Deutsch',
    'pt' => 'Português',  // Add this
],
```

**Edit `config/web.php`:**

```php
'bootstrap' => [
    // ...
    [
        'class' => 'app\components\LanguageSelector',
        'supportedLanguages' => ['en', 'es', 'fr', 'ur', 'de', 'pt'],  // Add 'pt'
    ],
    // ...
],
```

### Step 4: Done!

The new language will appear in the language switcher dropdown.

## File Structure

New files created for i18n support:

```
messages/
├── en/app.php          ✓ English translations
├── es/app.php          ✓ Spanish translations
├── fr/app.php          ✓ French translations
├── ur/app.php          ✓ Urdu translations
└── de/app.php          ✓ German translations

components/
├── LanguageSelector.php ✓ (Updated) Handles language detection

widgets/
└── LanguageSwitcher.php ✓ (New) Language dropdown widget

controllers/
├── SiteController.php   ✓ (Updated) Added changeLanguage action

models/
├── Settings.php         ✓ (Updated) Added language field

views/layouts/
├── _navbar_right.php    ✓ (Updated) Added language switcher

docs/
└── i18n.md             ✓ (New) Complete i18n documentation

migrations/
└── m260608_000000_add_language_to_settings.php ✓ (New)

config/
├── params.php           ✓ (Updated) Added supported languages
└── web.php              ✓ (Updated) Bootstrap languages config
```

## Key Components

### LanguageSelector Component

**Location:** `components/LanguageSelector.php`

Automatically:
- Reads user's language preference from settings table
- Detects browser language for guests
- Falls back to default language
- Applies formatter settings (dates, currency, etc.)

### LanguageSwitcher Widget

**Location:** `widgets/LanguageSwitcher.php`

Display options:
- **Dropdown style** (default) - Compact dropdown menu
- **Inline style** - Button group layout

Usage:
```php
<?= LanguageSwitcher::widget(['style' => 'dropdown']) ?>
```

### Change Language Action

**Location:** `controllers/SiteController.php::actionChangeLanguage()`

- Updates user's language preference in database (authenticated users)
- Sets language cookie (guests)
- Handles language validation

## Supported Languages

| Code | Language | Native Name | Flag |
|------|----------|-------------|------|
| en | English | English | 🇬🇧 |
| es | Spanish | Español | 🇪🇸 |
| fr | French | Français | 🇫🇷 |
| ur | Urdu | اردو | 🇵🇰 |
| de | German | Deutsch | 🇩🇪 |

## Translation Example

Here's an example of what a translation looks like in the message files:

**English (messages/en/app.php):**
```php
'Login' => 'Login',
'Add Expense' => 'Add Expense',
'Total Balance' => 'Total Balance',
```

**Spanish (messages/es/app.php):**
```php
'Login' => 'Iniciar Sesión',
'Add Expense' => 'Añadir Gasto',
'Total Balance' => 'Saldo Total',
```

## Troubleshooting

### Translations Not Showing

1. Check that the string is in the message file
2. Ensure exact spelling and case match
3. Run migration: `php yii migrate`
4. Clear cache: `rm -rf runtime/cache/*`

### Language Not Changing

1. Verify the language is in `supportedLanguages` config
2. Check user settings table has the language field
3. Try clearing browser cookies
4. Check browser's Accept-Language header for guests

### Special Characters Display Issues

1. Ensure message files are saved as UTF-8
2. Check database charset is utf8mb4
3. Verify HTML meta charset tag is present

## Best Practices

✅ **DO:**
- Use `Yii::t('app', 'message')` for all user-facing strings
- Keep translation keys descriptive
- Group related translations with comments
- Test in multiple languages before deployment
- Use UTF-8 encoding for all files

❌ **DON'T:**
- Concatenate translated strings
- Use hardcoded text in views
- Mix translation keys with dynamic content
- Forget to add translations in all language files
- Change language file structure after deployment

## More Information

For detailed i18n documentation, see: [`docs/i18n.md`](i18n.md)

---

**Version:** 1.0.0  
**Last Updated:** June 2025  
**Author:** Mohsin Rafique
