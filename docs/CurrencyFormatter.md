# CurrencyFormatter Component - Usage Guide

## Overview

The `CurrencyFormatter` is a Yii2 application component that provides comprehensive currency formatting with support for:

-   **Custom symbol positions** (left, right, with/without space)
-   **User-configurable settings** (decimal places, separators)
-   **Compact notation** for dashboards (k, M, B)
-   **60+ currency symbols** built-in

## Installation

### 1. Copy Component Files

Place these files in your application:

```
components/
├── CurrencyFormatter.php    # Main component
└── CurrencyBootstrap.php    # Auto-loads user settings
```

### 2. Configure Application

In `config/web.php`:

```php
return [
    'bootstrap' => [
        'log',
        'app\components\CurrencyBootstrap', // Add this
    ],

    'components' => [
        'currency' => [
            'class' => 'app\components\CurrencyFormatter',
            'currencyCode' => 'USD',
            'symbolPosition' => 'left',
            'decimalPlaces' => 2,
            'thousandSeparator' => ',',
            'decimalSeparator' => '.',
        ],
        // ...
    ],
];
```

### 3. Add User Model Fields (Optional)

If you want per-user currency settings, add these fields to your User model/table:

```sql
ALTER TABLE `user` ADD COLUMN `currency` VARCHAR(3) DEFAULT 'USD';
ALTER TABLE `user` ADD COLUMN `currency_position` VARCHAR(20) DEFAULT 'left';
ALTER TABLE `user` ADD COLUMN `thousand_separator` VARCHAR(1) DEFAULT ',';
ALTER TABLE `user` ADD COLUMN `decimal_separator` VARCHAR(1) DEFAULT '.';
ALTER TABLE `user` ADD COLUMN `decimal_places` TINYINT DEFAULT 2;
```

---

## Usage Examples

### Basic Formatting

```php
// In any view, controller, or model:
use Yii;

// Basic currency format (uses configured settings)
Yii::$app->currency->format(1234.56);
// Output: "$1,234.56"

// Override currency
Yii::$app->currency->format(1234.56, 'EUR');
// Output: "€1,234.56"

// Override position
Yii::$app->currency->format(1234.56, 'EUR', 'right_space');
// Output: "1,234.56 €"

// Negative amounts
Yii::$app->currency->format(-500);
// Output: "-$500.00"
```

### Compact Notation (Dashboards)

```php
// Compact with symbol
Yii::$app->currency->formatCompact(1500);        // "$1.50k"
Yii::$app->currency->formatCompact(2500000);     // "$2.50M"
Yii::$app->currency->formatCompact(1500000000);  // "$1.50B"

// Compact without symbol
Yii::$app->currency->formatCompact(1500, false); // "1.50k"

// Just "k" notation
Yii::$app->currency->formatToK(25000);           // "25.00k"
```

### Number Only (No Symbol)

```php
Yii::$app->currency->formatNumber(1234.56);
// Output: "1,234.56"
```

### Get Symbol

```php
Yii::$app->currency->getSymbol();        // "$" (configured currency)
Yii::$app->currency->getSymbol('EUR');   // "€"
Yii::$app->currency->getSymbol('PKR');   // "₨"
Yii::$app->currency->getSymbol('BTC');   // "₿"
```

---

## In Views

### Income/Expense Index Views

```php
<?php
use Yii;

// Statistics cards
$totalAmount = Yii::$app->currency->format($statistics['total_amount'] ?? 0);
$avgAmount = Yii::$app->currency->format($statistics['average'] ?? 0);

// For compact display in cards
$compactTotal = Yii::$app->currency->formatCompact($statistics['total_amount'] ?? 0);
?>

<div class="stats-value"><?= $totalAmount ?></div>
```

### GridView Columns

```php
[
    'attribute' => 'amount',
    'format' => 'raw',
    'value' => function ($model) {
        return Yii::$app->currency->format($model->amount);
    },
],
```

### Model Methods

```php
// In your Income or Expense model:
public function getFormattedAmount(): string
{
    return Yii::$app->currency->format($this->amount);
}

public function getCompactAmount(): string
{
    return Yii::$app->currency->formatCompact($this->amount);
}
```

---

## Settings Form Integration

### Update Your Settings View

```php
<?php
use app\components\CurrencyFormatter;

// Replace:
// Currency::getAllCurrencyCode()
// With:
// CurrencyFormatter::getCurrencyCodes()

// Replace:
// Currency::getAllCurrencyPositions()
// With:
// CurrencyFormatter::getPositionOptions()
?>

<?= $form->field($model, 'currency')->dropDownList(
    CurrencyFormatter::getCurrencyCodes(),
    ['class' => 'form-select', 'prompt' => '— Select Currency —']
) ?>

<?= $form->field($model, 'currency_position')->dropDownList(
    CurrencyFormatter::getPositionOptions(),
    ['class' => 'form-select']
) ?>
```

### Live Preview JavaScript

```javascript
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("currency-settings-form");
    const preview = document.getElementById("currency-preview");
    const sampleAmount = 12345.67;

    // Symbol mapping (subset - add more as needed)
    const symbols = {
        USD: "$",
        EUR: "€",
        GBP: "£",
        JPY: "¥",
        PKR: "₨",
        INR: "₹",
        BDT: "৳",
        CNY: "¥",
    };

    const updatePreview = () => {
        const currency = form.querySelector('[name*="currency"]')?.value || "USD";
        const position = form.querySelector('[name*="currency_position"]')?.value || "left";
        const thousand = form.querySelector('[name*="thousand_separator"]')?.value || ",";
        const decimal = form.querySelector('[name*="decimal_separator"]')?.value || ".";
        const places = parseInt(form.querySelector('[name*="decimal_places"]')?.value) || 2;

        const symbol = symbols[currency] || currency;

        // Format number
        const parts = sampleAmount.toFixed(places).split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
        const formatted = parts.join(decimal);

        // Apply position
        let result;
        switch (position) {
            case "right":
                result = formatted + symbol;
                break;
            case "left_space":
                result = symbol + " " + formatted;
                break;
            case "right_space":
                result = formatted + " " + symbol;
                break;
            default:
                result = symbol + formatted;
        }

        preview.textContent = result;
    };

    // Attach listeners
    form.querySelectorAll("input, select").forEach((el) => {
        el.addEventListener("change", updatePreview);
        el.addEventListener("input", updatePreview);
    });
});
```

---

## Comparison: Old vs New

### Before (Helper Class)

```php
use app\helpers\Currency;

// Static calls, manual parameter passing
Currency::format($amount, 'USD', Currency::POSITION_LEFT, 2, ',', '.');
Currency::getAllCurrencyCode();
Currency::getSymbol('USD');
```

### After (Component)

```php
// Component-based, auto-configured
Yii::$app->currency->format($amount);          // Uses user settings
Yii::$app->currency->formatCompact($amount);   // Dashboard display

// Static methods still available for forms
CurrencyFormatter::getCurrencyCodes();
CurrencyFormatter::getPositionOptions();
```

---

## Migration Checklist

1. [ ] Copy `CurrencyFormatter.php` to `components/`
2. [ ] Copy `CurrencyBootstrap.php` to `components/`
3. [ ] Add configuration to `config/web.php`
4. [ ] Update User model with currency fields (if using per-user settings)
5. [ ] Update `CurrencyBootstrap::getUserCurrencySettings()` to match your User model
6. [ ] Replace `Currency::` calls in views:
    - `Currency::format()` → `Yii::$app->currency->format()`
    - `Currency::formatToK()` → `Yii::$app->currency->formatToK()`
    - `Currency::formatToReadable()` → `Yii::$app->currency->formatCompact()`
    - `Currency::getSymbol()` → `Yii::$app->currency->getSymbol()`
    - `Currency::getAllCurrencyCode()` → `CurrencyFormatter::getCurrencyCodes()`
    - `Currency::getAllCurrencyPositions()` → `CurrencyFormatter::getPositionOptions()`
7. [ ] Delete old `helpers/Currency.php` file

---

## API Reference

### Instance Methods

| Method                                                | Description            | Example       |
| ----------------------------------------------------- | ---------------------- | ------------- |
| `format($amount, $currency?, $position?)`             | Full currency format   | `"$1,234.56"` |
| `formatNumber($amount)`                               | Number only, no symbol | `"1,234.56"`  |
| `formatCompact($amount, $includeSymbol?, $decimals?)` | Compact with k/M/B     | `"$1.50M"`    |
| `formatToK($amount, $decimals?)`                      | Thousands notation     | `"25.00k"`    |
| `getSymbol($currency?)`                               | Get currency symbol    | `"$"`         |
| `getConfig()`                                         | Get current settings   | `[...]`       |
| `applyUserSettings($settings)`                        | Apply user preferences | —             |

### Static Methods

| Method                 | Description               | Use For        |
| ---------------------- | ------------------------- | -------------- |
| `getCurrencyCodes()`   | Currency dropdown options | Form dropdowns |
| `getPositionOptions()` | Position dropdown options | Form dropdowns |
| `getAllSymbols()`      | All symbol mappings       | Reference      |

### Position Constants

| Constant               | Value           | Example |
| ---------------------- | --------------- | ------- |
| `POSITION_LEFT`        | `'left'`        | `$100`  |
| `POSITION_RIGHT`       | `'right'`       | `100$`  |
| `POSITION_LEFT_SPACE`  | `'left_space'`  | `$ 100` |
| `POSITION_RIGHT_SPACE` | `'right_space'` | `100 $` |
