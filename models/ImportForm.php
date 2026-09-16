<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use app\services\BankStatementParser;
use app\services\ImportService;

/**
 * ImportForm backs the bulk-import upload step.
 *
 * Two sources are supported. A spreadsheet is the app's own template or export
 * layout, where the user controls the columns. A bank statement is the bank's
 * own monthly e-statement PDF, where the layout is fixed by the bank and the
 * importer has to derive the category, payment method and tax category itself.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ImportForm extends Model
{
    /** Upload sources */
    public const SOURCE_SPREADSHEET = 'spreadsheet';
    public const SOURCE_STATEMENT = 'statement';

    /** @var string Import type: 'expense' | 'income' */
    public $type = ImportService::TYPE_EXPENSE;

    /** @var string Upload source: 'spreadsheet' | 'statement' */
    public $source = self::SOURCE_SPREADSHEET;

    /** @var UploadedFile|null Uploaded spreadsheet or statement */
    public $file;

    /** @var bool Create categories that don't exist yet */
    public $autoCreateCategories = true;

    /** @var bool Skip rows that match an existing transaction */
    public $skipDuplicates = true;

    /**
     * @var string Which bank's statement layout to expect. Empty means detect
     *     it from the file; naming one turns a wrong upload into a clear error
     *     instead of a parse that quietly finds nothing.
     */
    public $statementFormat = '';

    /** @var string|null Open password for a protected statement PDF (transient) */
    public $password;

    /** @var int|null Bank the statement belongs to, tagged onto every row */
    public $bank_id;

    /** @var int|null Category for statement rows no rule could classify */
    public $fallbackCategoryId;

    /**
     * @var bool Import cash withdrawals and account transfers too. Off by
     *     default: those lines move money rather than spend it, so importing
     *     them double-counts against the spending they later pay for.
     */
    public $includeTransfers = false;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['type'], 'required'],
            [['type'], 'in', 'range' => [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME]],
            [['source'], 'in', 'range' => [self::SOURCE_SPREADSHEET, self::SOURCE_STATEMENT]],
            [['statementFormat'], 'in', 'range' => array_merge([''], array_keys(BankStatementParser::formats()))],
            [['autoCreateCategories', 'skipDuplicates', 'includeTransfers'], 'boolean'],

            [['password'], 'string', 'max' => 128],
            [['password'], 'trim'],

            [['bank_id', 'fallbackCategoryId'], 'integer'],
            [['bank_id'], 'exist', 'skipOnError' => true, 'targetClass' => Bank::class, 'targetAttribute' => ['bank_id' => 'id']],
            // `when` is server-side only. These rules deliberately carry no
            // `whenClient`: the wizard posts through fetch() and reports every
            // error from the JSON response, so client validation is off for
            // this form (see views/import/index.php).
            [['fallbackCategoryId'], 'required', 'when' => fn (self $m) => $m->isStatement(),
                'message' => Yii::t('app', 'Choose a category for statement rows that cannot be classified.')],
            [['fallbackCategoryId'], 'validateFallbackCategory', 'skipOnEmpty' => true],

            // The accepted extensions depend on the source: a bank statement is
            // a PDF (or its already-extracted text), a spreadsheet is not.
            [
                ['file'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'csv, xlsx, xls',
                'checkExtensionByMimeType' => false,
                'maxSize' => 5 * 1024 * 1024, // 5 MB
                'tooBig' => Yii::t('app', 'File size cannot exceed {limit}.', ['limit' => '5 MB']),
                'wrongExtension' => Yii::t('app', 'Only CSV and Excel (.csv, .xlsx, .xls) files are allowed.'),
                'when' => fn (self $m) => !$m->isStatement(),
            ],
            [
                ['file'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'pdf, txt',
                'checkExtensionByMimeType' => false,
                'maxSize' => 5 * 1024 * 1024, // 5 MB (PDF statements can be large)
                'tooBig' => Yii::t('app', 'File size cannot exceed {limit}.', ['limit' => '5 MB']),
                'wrongExtension' => Yii::t('app', 'Only a PDF statement (.pdf) or its extracted text (.txt) is allowed.'),
                'when' => fn (self $m) => $m->isStatement(),
            ],
        ];
    }

    /**
     * Ensures the fallback category belongs to the active workspace and to the
     * type being imported.
     *
     * @param string $attribute
     */
    public function validateFallbackCategory(string $attribute): void
    {
        $class = $this->type === ImportService::TYPE_INCOME ? IncomeCategory::class : ExpenseCategory::class;

        $exists = $class::find()
            ->where(['id' => $this->$attribute, 'workspace_id' => Yii::$app->workspace->getId()])
            ->exists();

        if (!$exists) {
            $this->addError($attribute, Yii::t('app', 'The selected category does not exist.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Import Type'),
            'source' => Yii::t('app', 'Source'),
            'statementFormat' => Yii::t('app', 'Statement format'),
            'file' => Yii::t('app', 'File'),
            'password' => Yii::t('app', 'PDF password'),
            'bank_id' => Yii::t('app', 'Bank'),
            'fallbackCategoryId' => Yii::t('app', 'Fallback category'),
            'autoCreateCategories' => Yii::t('app', 'Create missing categories automatically'),
            'skipDuplicates' => Yii::t('app', 'Skip duplicate rows'),
            'includeTransfers' => Yii::t('app', 'Include cash withdrawals and account transfers'),
        ];
    }

    /**
     * Whether this upload is a bank statement rather than a spreadsheet.
     *
     * @return bool
     */
    public function isStatement(): bool
    {
        return $this->source === self::SOURCE_STATEMENT;
    }

    /**
     * Whether the uploaded file is a PDF (by client-supplied extension).
     *
     * @return bool
     */
    public function isPdf(): bool
    {
        return $this->file !== null
            && strtolower((string) $this->file->extension) === 'pdf';
    }

    /**
     * Loads the uploaded file instance from the request.
     *
     * @return bool Whether a file was provided
     */
    public function loadUploadedFile(): bool
    {
        $this->file = UploadedFile::getInstance($this, 'file');
        return $this->file !== null;
    }

    /**
     * Returns the options array consumed by {@see ImportService}.
     *
     * @return array
     */
    public function options(): array
    {
        return [
            'type' => $this->type,
            'source' => $this->isStatement() ? self::SOURCE_STATEMENT : self::SOURCE_SPREADSHEET,
            'statementFormat' => (string) $this->statementFormat,
            'autoCreateCategories' => (bool) $this->autoCreateCategories,
            'skipDuplicates' => (bool) $this->skipDuplicates,
            'includeTransfers' => (bool) $this->includeTransfers,
            'bankId' => $this->bank_id === null || $this->bank_id === '' ? null : (int) $this->bank_id,
            'fallbackCategoryId' => $this->fallbackCategoryId === null || $this->fallbackCategoryId === ''
                ? null
                : (int) $this->fallbackCategoryId,
        ];
    }
}
