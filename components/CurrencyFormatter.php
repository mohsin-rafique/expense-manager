<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * CurrencyFormatter Component
 *
 * A Yii2 application component that provides comprehensive currency formatting
 * with support for custom symbol positions, user preferences, and compact notation.
 *
 * This component should be registered in your application configuration and accessed
 * via `Yii::$app->currency`. It integrates with user settings to provide consistent
 * currency formatting throughout the application.
 *
 * Configuration in config/web.php:
 * ```php
 * 'components' => [
 *     'currency' => [
 *         'class' => 'app\components\CurrencyFormatter',
 *         'currencyCode' => 'USD',
 *         'symbolPosition' => 'left',
 *         'decimalPlaces' => 2,
 *     ],
 * ],
 * ```
 *
 * Usage examples:
 * ```php
 * // Basic formatting (uses configured settings)
 * Yii::$app->currency->format(1234.56);              // "$1,234.56"
 *
 * // Compact formatting for dashboards
 * Yii::$app->currency->formatCompact(1500000);       // "$1.50M"
 *
 * // Format without symbol
 * Yii::$app->currency->formatNumber(1234.56);        // "1,234.56"
 *
 * // Get symbol only
 * Yii::$app->currency->getSymbol();                  // "$"
 *
 * // Static helpers for dropdowns
 * CurrencyFormatter::getCurrencyCodes();             // ['USD' => 'US Dollar', ...]
 * CurrencyFormatter::getPositionOptions();           // ['left' => 'Left', ...]
 * ```
 *
 * @property-read string $symbol The currency symbol for the configured currency
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class CurrencyFormatter extends Component
{
    // =========================================================================
    // POSITION CONSTANTS
    // =========================================================================

    /** @var string Symbol position: left of amount (e.g., "$100") */
    public const POSITION_LEFT = 'left';

    /** @var string Symbol position: right of amount (e.g., "100$") */
    public const POSITION_RIGHT = 'right';

    /** @var string Symbol position: left with space (e.g., "$ 100") */
    public const POSITION_LEFT_SPACE = 'left_space';

    /** @var string Symbol position: right with space (e.g., "100 $") */
    public const POSITION_RIGHT_SPACE = 'right_space';

    // =========================================================================
    // CONFIGURABLE PROPERTIES
    // =========================================================================

    /**
     * @var string ISO 4217 currency code (e.g., 'USD', 'EUR', 'PKR')
     */
    public string $currencyCode = 'USD';

    /**
     * @var string Symbol position relative to amount
     * @see POSITION_LEFT, POSITION_RIGHT, POSITION_LEFT_SPACE, POSITION_RIGHT_SPACE
     */
    public string $symbolPosition = self::POSITION_LEFT;

    /**
     * @var int Number of decimal places (0-4)
     */
    public int $decimalPlaces = 2;

    /**
     * @var string Thousand separator character
     */
    public string $thousandSeparator = ',';

    /**
     * @var string Decimal separator character
     */
    public string $decimalSeparator = '.';

    // =========================================================================
    // CURRENCY SYMBOLS MAPPING
    // =========================================================================

    /**
     * @var array<string, string> Mapping of currency codes to symbols
     */
    private static array $symbols = [
        // Major World Currencies
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CNY' => '¥',
        'CHF' => 'CHF',

        // North America
        'CAD' => 'C$',
        'MXN' => 'MX$',

        // Asia Pacific
        'AUD' => 'A$',
        'NZD' => 'NZ$',
        'HKD' => 'HK$',
        'SGD' => 'S$',
        'INR' => '₹',
        'PKR' => '₨',
        'BDT' => '৳',
        'LKR' => '₨',
        'NPR' => '₨',
        'KRW' => '₩',
        'TWD' => 'NT$',
        'THB' => '฿',
        'MYR' => 'RM',
        'IDR' => 'Rp',
        'PHP' => '₱',
        'VND' => '₫',

        // Europe
        'RUB' => '₽',
        'PLN' => 'zł',
        'CZK' => 'Kč',
        'HUF' => 'Ft',
        'RON' => 'lei',
        'BGN' => 'лв',
        'UAH' => '₴',
        'TRY' => '₺',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
        'ISK' => 'kr',

        // Middle East
        'SAR' => '﷼',
        'AED' => 'د.إ',
        'QAR' => '﷼',
        'KWD' => 'د.ك',
        'BHD' => '.د.ب',
        'OMR' => '﷼',
        'JOD' => 'د.ا',
        'ILS' => '₪',
        'EGP' => 'E£',
        'LBP' => 'ل.ل',

        // Africa
        'ZAR' => 'R',
        'NGN' => '₦',
        'KES' => 'KSh',
        'GHS' => '₵',
        'MAD' => 'د.م.',
        'TZS' => 'TSh',
        'UGX' => 'USh',

        // South America
        'BRL' => 'R$',
        'ARS' => '$',
        'CLP' => '$',
        'COP' => '$',
        'PEN' => 'S/',

        // Cryptocurrency
        'BTC' => '₿',
        'ETH' => 'Ξ',
    ];

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->validateConfiguration();
    }

    /**
     * Validates the component configuration
     *
     * @throws InvalidConfigException if configuration is invalid
     */
    protected function validateConfiguration(): void
    {
        if ($this->decimalPlaces < 0 || $this->decimalPlaces > 4) {
            throw new InvalidConfigException('decimalPlaces must be between 0 and 4');
        }

        $validPositions = [
            self::POSITION_LEFT,
            self::POSITION_RIGHT,
            self::POSITION_LEFT_SPACE,
            self::POSITION_RIGHT_SPACE,
        ];

        if (!in_array($this->symbolPosition, $validPositions, true)) {
            throw new InvalidConfigException(
                'symbolPosition must be one of: ' . implode(', ', $validPositions)
            );
        }
    }

    // =========================================================================
    // PRIMARY FORMATTING METHODS
    // =========================================================================

    /**
     * Formats an amount with currency symbol using configured settings
     *
     * This is the primary method for currency formatting throughout the application.
     * It respects all configured settings including symbol position.
     *
     * @param float|int|string|null $amount The amount to format
     * @param string|null $currencyCode Override currency code (optional)
     * @param string|null $position Override symbol position (optional)
     * @return string The formatted currency string
     *
     * @example
     * ```php
     * Yii::$app->currency->format(1234.56);           // "$1,234.56"
     * Yii::$app->currency->format(1234.56, 'EUR');    // "€1,234.56"
     * Yii::$app->currency->format(-500);              // "-$500.00"
     * ```
     */
    public function format($amount, ?string $currencyCode = null, ?string $position = null): string
    {
        $amount = $this->normalizeAmount($amount);
        $symbol = $this->getSymbol($currencyCode);
        $position = $position ?? $this->symbolPosition;

        $isNegative = $amount < 0;
        $absAmount = abs($amount);

        $formattedNumber = number_format(
            $absAmount,
            $this->decimalPlaces,
            $this->decimalSeparator,
            $this->thousandSeparator
        );

        $formatted = $this->applySymbolPosition($formattedNumber, $symbol, $position);

        return $isNegative ? '-' . $formatted : $formatted;
    }

    /**
     * Formats amount without currency symbol
     *
     * Useful when you need just the number formatting without the symbol.
     *
     * @param float|int|string|null $amount The amount to format
     * @return string The formatted number
     *
     * @example
     * ```php
     * Yii::$app->currency->formatNumber(1234.56);  // "1,234.56"
     * ```
     */
    public function formatNumber($amount): string
    {
        $amount = $this->normalizeAmount($amount);

        return number_format(
            $amount,
            $this->decimalPlaces,
            $this->decimalSeparator,
            $this->thousandSeparator
        );
    }

    /**
     * Formats amount with compact notation (k, M, B)
     *
     * Ideal for dashboards, charts, and stat cards where space is limited.
     *
     * @param float|int|string|null $amount The amount to format
     * @param bool $includeSymbol Whether to include currency symbol
     * @param int $decimals Number of decimal places for compact display
     * @return string The compact formatted string
     *
     * @example
     * ```php
     * Yii::$app->currency->formatCompact(1500);        // "$1.50k"
     * Yii::$app->currency->formatCompact(2500000);     // "$2.50M"
     * Yii::$app->currency->formatCompact(1500000000);  // "$1.50B"
     * Yii::$app->currency->formatCompact(500, false);  // "500.00"
     * ```
     */
    public function formatCompact($amount, bool $includeSymbol = true, int $decimals = 2): string
    {
        $amount = $this->normalizeAmount($amount);
        $isNegative = $amount < 0;
        $absAmount = abs($amount);

        $suffix = '';
        $divisor = 1;

        if ($absAmount >= 1000000000) {
            $divisor = 1000000000;
            $suffix = 'B';
        } elseif ($absAmount >= 1000000) {
            $divisor = 1000000;
            $suffix = 'M';
        } elseif ($absAmount >= 1000) {
            $divisor = 1000;
            $suffix = 'k';
        }

        $compactValue = number_format($absAmount / $divisor, $decimals) . $suffix;
        $sign = $isNegative ? '-' : '';

        if (!$includeSymbol) {
            return $sign . $compactValue;
        }

        $symbol = $this->getSymbol();
        $formatted = $this->applySymbolPosition($compactValue, $symbol, $this->symbolPosition);

        return $sign . $formatted;
    }

    /**
     * Formats amount with "k" notation only
     *
     * @param float|int|string|null $amount The amount to format
     * @param int $decimals Number of decimal places
     * @return string The formatted string with optional 'k' suffix
     *
     * @example
     * ```php
     * Yii::$app->currency->formatToK(500);    // "500.00"
     * Yii::$app->currency->formatToK(1500);   // "1.50k"
     * Yii::$app->currency->formatToK(25000);  // "25.00k"
     * ```
     */
    public function formatToK($amount, int $decimals = 2): string
    {
        $amount = $this->normalizeAmount($amount);

        if (abs($amount) >= 1000) {
            return number_format($amount / 1000, $decimals) . 'k';
        }

        return number_format($amount, $decimals);
    }

    // =========================================================================
    // SYMBOL METHODS
    // =========================================================================

    /**
     * Gets the currency symbol for a given or configured currency code
     *
     * @param string|null $currencyCode Currency code (uses configured if null)
     * @return string The currency symbol or code if symbol not defined
     *
     * @example
     * ```php
     * Yii::$app->currency->getSymbol();       // "$" (if configured as USD)
     * Yii::$app->currency->getSymbol('EUR');  // "€"
     * Yii::$app->currency->getSymbol('XYZ');  // "XYZ" (fallback)
     * ```
     */
    public function getSymbol(?string $currencyCode = null): string
    {
        $code = strtoupper($currencyCode ?? $this->currencyCode);
        return self::$symbols[$code] ?? $code;
    }

    /**
     * Gets all available currency symbols
     *
     * @return array<string, string> Currency code => symbol mapping
     */
    public static function getAllSymbols(): array
    {
        return self::$symbols;
    }

    // =========================================================================
    // STATIC HELPER METHODS (For Forms/Dropdowns)
    // =========================================================================

    /**
     * Returns all available currency codes with localized names
     *
     * Use this for populating currency dropdown lists in forms.
     *
     * @return array<string, string> Currency code => localized name
     *
     * @example
     * ```php
     * <?= $form->field($model, 'currency')->dropDownList(
     *     CurrencyFormatter::getCurrencyCodes()
     * ) ?>
     * ```
     */
    public static function getCurrencyCodes(): array
    {
        return [
            'AED' => Yii::t('app', 'United Arab Emirates dirham'),
            'AFN' => Yii::t('app', 'Afghan afghani'),
            'ALL' => Yii::t('app', 'Albanian lek'),
            'AMD' => Yii::t('app', 'Armenian dram'),
            'ANG' => Yii::t('app', 'Netherlands Antillean guilder'),
            'AOA' => Yii::t('app', 'Angolan kwanza'),
            'ARS' => Yii::t('app', 'Argentine peso'),
            'AUD' => Yii::t('app', 'Australian dollar'),
            'AWG' => Yii::t('app', 'Aruban florin'),
            'AZN' => Yii::t('app', 'Azerbaijani manat'),
            'BAM' => Yii::t('app', 'Bosnia and Herzegovina convertible mark'),
            'BBD' => Yii::t('app', 'Barbadian dollar'),
            'BDT' => Yii::t('app', 'Bangladeshi taka'),
            'BGN' => Yii::t('app', 'Bulgarian lev'),
            'BHD' => Yii::t('app', 'Bahraini dinar'),
            'BIF' => Yii::t('app', 'Burundian franc'),
            'BMD' => Yii::t('app', 'Bermudian dollar'),
            'BND' => Yii::t('app', 'Brunei dollar'),
            'BOB' => Yii::t('app', 'Bolivian boliviano'),
            'BRL' => Yii::t('app', 'Brazilian real'),
            'BSD' => Yii::t('app', 'Bahamian dollar'),
            'BTC' => Yii::t('app', 'Bitcoin'),
            'BTN' => Yii::t('app', 'Bhutanese ngultrum'),
            'BWP' => Yii::t('app', 'Botswana pula'),
            'BYN' => Yii::t('app', 'Belarusian ruble'),
            'BZD' => Yii::t('app', 'Belize dollar'),
            'CAD' => Yii::t('app', 'Canadian dollar'),
            'CDF' => Yii::t('app', 'Congolese franc'),
            'CHF' => Yii::t('app', 'Swiss franc'),
            'CLP' => Yii::t('app', 'Chilean peso'),
            'CNY' => Yii::t('app', 'Chinese yuan'),
            'COP' => Yii::t('app', 'Colombian peso'),
            'CRC' => Yii::t('app', 'Costa Rican colón'),
            'CUC' => Yii::t('app', 'Cuban convertible peso'),
            'CUP' => Yii::t('app', 'Cuban peso'),
            'CVE' => Yii::t('app', 'Cape Verdean escudo'),
            'CZK' => Yii::t('app', 'Czech koruna'),
            'DJF' => Yii::t('app', 'Djiboutian franc'),
            'DKK' => Yii::t('app', 'Danish krone'),
            'DOP' => Yii::t('app', 'Dominican peso'),
            'DZD' => Yii::t('app', 'Algerian dinar'),
            'EGP' => Yii::t('app', 'Egyptian pound'),
            'ERN' => Yii::t('app', 'Eritrean nakfa'),
            'ETB' => Yii::t('app', 'Ethiopian birr'),
            'EUR' => Yii::t('app', 'Euro'),
            'FJD' => Yii::t('app', 'Fijian dollar'),
            'FKP' => Yii::t('app', 'Falkland Islands pound'),
            'GBP' => Yii::t('app', 'Pound sterling'),
            'GEL' => Yii::t('app', 'Georgian lari'),
            'GHS' => Yii::t('app', 'Ghana cedi'),
            'GIP' => Yii::t('app', 'Gibraltar pound'),
            'GMD' => Yii::t('app', 'Gambian dalasi'),
            'GNF' => Yii::t('app', 'Guinean franc'),
            'GTQ' => Yii::t('app', 'Guatemalan quetzal'),
            'GYD' => Yii::t('app', 'Guyanese dollar'),
            'HKD' => Yii::t('app', 'Hong Kong dollar'),
            'HNL' => Yii::t('app', 'Honduran lempira'),
            'HRK' => Yii::t('app', 'Croatian kuna'),
            'HTG' => Yii::t('app', 'Haitian gourde'),
            'HUF' => Yii::t('app', 'Hungarian forint'),
            'IDR' => Yii::t('app', 'Indonesian rupiah'),
            'ILS' => Yii::t('app', 'Israeli new shekel'),
            'INR' => Yii::t('app', 'Indian rupee'),
            'IQD' => Yii::t('app', 'Iraqi dinar'),
            'IRR' => Yii::t('app', 'Iranian rial'),
            'ISK' => Yii::t('app', 'Icelandic króna'),
            'JMD' => Yii::t('app', 'Jamaican dollar'),
            'JOD' => Yii::t('app', 'Jordanian dinar'),
            'JPY' => Yii::t('app', 'Japanese yen'),
            'KES' => Yii::t('app', 'Kenyan shilling'),
            'KGS' => Yii::t('app', 'Kyrgyzstani som'),
            'KHR' => Yii::t('app', 'Cambodian riel'),
            'KMF' => Yii::t('app', 'Comorian franc'),
            'KPW' => Yii::t('app', 'North Korean won'),
            'KRW' => Yii::t('app', 'South Korean won'),
            'KWD' => Yii::t('app', 'Kuwaiti dinar'),
            'KYD' => Yii::t('app', 'Cayman Islands dollar'),
            'KZT' => Yii::t('app', 'Kazakhstani tenge'),
            'LAK' => Yii::t('app', 'Lao kip'),
            'LBP' => Yii::t('app', 'Lebanese pound'),
            'LKR' => Yii::t('app', 'Sri Lankan rupee'),
            'LRD' => Yii::t('app', 'Liberian dollar'),
            'LSL' => Yii::t('app', 'Lesotho loti'),
            'LYD' => Yii::t('app', 'Libyan dinar'),
            'MAD' => Yii::t('app', 'Moroccan dirham'),
            'MDL' => Yii::t('app', 'Moldovan leu'),
            'MGA' => Yii::t('app', 'Malagasy ariary'),
            'MKD' => Yii::t('app', 'Macedonian denar'),
            'MMK' => Yii::t('app', 'Burmese kyat'),
            'MNT' => Yii::t('app', 'Mongolian tögrög'),
            'MOP' => Yii::t('app', 'Macanese pataca'),
            'MRU' => Yii::t('app', 'Mauritanian ouguiya'),
            'MUR' => Yii::t('app', 'Mauritian rupee'),
            'MVR' => Yii::t('app', 'Maldivian rufiyaa'),
            'MWK' => Yii::t('app', 'Malawian kwacha'),
            'MXN' => Yii::t('app', 'Mexican peso'),
            'MYR' => Yii::t('app', 'Malaysian ringgit'),
            'MZN' => Yii::t('app', 'Mozambican metical'),
            'NAD' => Yii::t('app', 'Namibian dollar'),
            'NGN' => Yii::t('app', 'Nigerian naira'),
            'NIO' => Yii::t('app', 'Nicaraguan córdoba'),
            'NOK' => Yii::t('app', 'Norwegian krone'),
            'NPR' => Yii::t('app', 'Nepalese rupee'),
            'NZD' => Yii::t('app', 'New Zealand dollar'),
            'OMR' => Yii::t('app', 'Omani rial'),
            'PAB' => Yii::t('app', 'Panamanian balboa'),
            'PEN' => Yii::t('app', 'Peruvian sol'),
            'PGK' => Yii::t('app', 'Papua New Guinean kina'),
            'PHP' => Yii::t('app', 'Philippine peso'),
            'PKR' => Yii::t('app', 'Pakistani rupee'),
            'PLN' => Yii::t('app', 'Polish złoty'),
            'PYG' => Yii::t('app', 'Paraguayan guaraní'),
            'QAR' => Yii::t('app', 'Qatari riyal'),
            'RON' => Yii::t('app', 'Romanian leu'),
            'RSD' => Yii::t('app', 'Serbian dinar'),
            'RUB' => Yii::t('app', 'Russian ruble'),
            'RWF' => Yii::t('app', 'Rwandan franc'),
            'SAR' => Yii::t('app', 'Saudi riyal'),
            'SBD' => Yii::t('app', 'Solomon Islands dollar'),
            'SCR' => Yii::t('app', 'Seychellois rupee'),
            'SDG' => Yii::t('app', 'Sudanese pound'),
            'SEK' => Yii::t('app', 'Swedish krona'),
            'SGD' => Yii::t('app', 'Singapore dollar'),
            'SHP' => Yii::t('app', 'Saint Helena pound'),
            'SLL' => Yii::t('app', 'Sierra Leonean leone'),
            'SOS' => Yii::t('app', 'Somali shilling'),
            'SRD' => Yii::t('app', 'Surinamese dollar'),
            'SSP' => Yii::t('app', 'South Sudanese pound'),
            'STN' => Yii::t('app', 'São Tomé and Príncipe dobra'),
            'SYP' => Yii::t('app', 'Syrian pound'),
            'SZL' => Yii::t('app', 'Swazi lilangeni'),
            'THB' => Yii::t('app', 'Thai baht'),
            'TJS' => Yii::t('app', 'Tajikistani somoni'),
            'TMT' => Yii::t('app', 'Turkmenistan manat'),
            'TND' => Yii::t('app', 'Tunisian dinar'),
            'TOP' => Yii::t('app', 'Tongan paʻanga'),
            'TRY' => Yii::t('app', 'Turkish lira'),
            'TTD' => Yii::t('app', 'Trinidad and Tobago dollar'),
            'TWD' => Yii::t('app', 'New Taiwan dollar'),
            'TZS' => Yii::t('app', 'Tanzanian shilling'),
            'UAH' => Yii::t('app', 'Ukrainian hryvnia'),
            'UGX' => Yii::t('app', 'Ugandan shilling'),
            'USD' => Yii::t('app', 'United States dollar'),
            'UYU' => Yii::t('app', 'Uruguayan peso'),
            'UZS' => Yii::t('app', 'Uzbekistani som'),
            'VES' => Yii::t('app', 'Venezuelan bolívar'),
            'VND' => Yii::t('app', 'Vietnamese đồng'),
            'VUV' => Yii::t('app', 'Vanuatu vatu'),
            'WST' => Yii::t('app', 'Samoan tālā'),
            'XAF' => Yii::t('app', 'Central African CFA franc'),
            'XCD' => Yii::t('app', 'East Caribbean dollar'),
            'XOF' => Yii::t('app', 'West African CFA franc'),
            'XPF' => Yii::t('app', 'CFP franc'),
            'YER' => Yii::t('app', 'Yemeni rial'),
            'ZAR' => Yii::t('app', 'South African rand'),
            'ZMW' => Yii::t('app', 'Zambian kwacha'),
        ];
    }

    /**
     * Returns all available symbol position options
     *
     * Use this for populating position dropdown lists in forms.
     *
     * @return array<string, string> Position key => localized label
     *
     * @example
     * ```php
     * <?= $form->field($model, 'currency_position')->dropDownList(
     *     CurrencyFormatter::getPositionOptions()
     * ) ?>
     * ```
     */
    public static function getPositionOptions(): array
    {
        return [
            self::POSITION_LEFT => Yii::t('app', 'Left ($100)'),
            self::POSITION_RIGHT => Yii::t('app', 'Right (100$)'),
            self::POSITION_LEFT_SPACE => Yii::t('app', 'Left with space ($ 100)'),
            self::POSITION_RIGHT_SPACE => Yii::t('app', 'Right with space (100 $)'),
        ];
    }

    // =========================================================================
    // CONFIGURATION METHODS
    // =========================================================================

    /**
     * Applies user settings to the formatter
     *
     * Call this method to update the formatter with user-specific preferences.
     * Typically called from a bootstrap component after user authentication.
     *
     * @param array $settings User settings array with keys:
     *                        - currency: string (ISO 4217 code)
     *                        - currency_position: string (position constant)
     *                        - thousand_separator: string
     *                        - decimal_separator: string
     *                        - decimal_places: int
     * @return self For method chaining
     *
     * @example
     * ```php
     * Yii::$app->currency->applyUserSettings([
     *     'currency' => 'EUR',
     *     'currency_position' => 'right_space',
     *     'decimal_places' => 2,
     * ]);
     * ```
     */
    public function applyUserSettings(array $settings): self
    {
        if (!empty($settings['currency'])) {
            $this->currencyCode = $settings['currency'];
        }

        if (!empty($settings['currency_position'])) {
            $this->symbolPosition = $settings['currency_position'];
        }

        if (!empty($settings['thousand_separator'])) {
            $this->thousandSeparator = $settings['thousand_separator'];
        }

        if (!empty($settings['decimal_separator'])) {
            $this->decimalSeparator = $settings['decimal_separator'];
        }

        if (isset($settings['decimal_places'])) {
            $this->decimalPlaces = (int) $settings['decimal_places'];
        }

        return $this;
    }

    /**
     * Gets current configuration as array
     *
     * Useful for serialization or passing to JavaScript.
     *
     * @return array Current configuration settings
     */
    public function getConfig(): array
    {
        return [
            'currencyCode' => $this->currencyCode,
            'symbol' => $this->getSymbol(),
            'symbolPosition' => $this->symbolPosition,
            'decimalPlaces' => $this->decimalPlaces,
            'thousandSeparator' => $this->thousandSeparator,
            'decimalSeparator' => $this->decimalSeparator,
        ];
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Normalizes input to a float value
     *
     * @param mixed $amount The amount to normalize
     * @return float The normalized amount
     */
    private function normalizeAmount($amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        if (is_string($amount)) {
            // Remove currency symbols and spaces
            $amount = preg_replace('/[^\d.\-]/', '', $amount);
        }

        return (float) $amount;
    }

    /**
     * Applies symbol position to formatted number
     *
     * @param string $formattedNumber The formatted number
     * @param string $symbol The currency symbol
     * @param string $position The position constant
     * @return string The combined string
     */
    private function applySymbolPosition(string $formattedNumber, string $symbol, string $position): string
    {
        return match ($position) {
            self::POSITION_RIGHT => $formattedNumber . $symbol,
            self::POSITION_LEFT_SPACE => $symbol . ' ' . $formattedNumber,
            self::POSITION_RIGHT_SPACE => $formattedNumber . ' ' . $symbol,
            default => $symbol . $formattedNumber,
        };
    }
}
