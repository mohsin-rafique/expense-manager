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
use app\services\ImportService;

/**
 * ImportForm backs the bulk-import upload step.
 *
 * Captures the import type (expense/income), the uploaded spreadsheet, and the
 * import options (auto-create categories, skip duplicates).
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ImportForm extends Model
{
    /** @var string Import type: 'expense' | 'income' */
    public $type = ImportService::TYPE_EXPENSE;

    /** @var UploadedFile|null Uploaded spreadsheet */
    public $file;

    /** @var bool Create categories that don't exist yet */
    public $autoCreateCategories = true;

    /** @var bool Skip rows that match an existing transaction */
    public $skipDuplicates = true;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['type'], 'required'],
            [['type'], 'in', 'range' => [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME]],
            [['autoCreateCategories', 'skipDuplicates'], 'boolean'],
            [
                ['file'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'csv, xlsx, xls',
                'checkExtensionByMimeType' => false,
                'maxSize' => 5 * 1024 * 1024, // 5 MB
                'tooBig' => Yii::t('app', 'File size cannot exceed {limit}.', ['limit' => '5 MB']),
                'wrongExtension' => Yii::t('app', 'Only CSV and Excel (.csv, .xlsx, .xls) files are allowed.'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'type' => Yii::t('app', 'Import Type'),
            'file' => Yii::t('app', 'File'),
            'autoCreateCategories' => Yii::t('app', 'Create missing categories automatically'),
            'skipDuplicates' => Yii::t('app', 'Skip duplicate rows'),
        ];
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
            'autoCreateCategories' => (bool) $this->autoCreateCategories,
            'skipDuplicates' => (bool) $this->skipDuplicates,
        ];
    }
}
