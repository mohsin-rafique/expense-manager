<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use app\helpers\PdfText;
use app\services\FiscalYearService;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * FbrReturnForm backs the FBR return reconciliation screen.
 *
 * The user uploads the return PDF that IRIS generates (e.g. "114(1) (Return of
 * Income filed voluntarily for complete year)"); its Personal Expenses are then
 * compared against the workspace's expenses for the matching fiscal year.
 * Nothing is written to the database.
 *
 * The fiscal year is taken from the return's own period by default, so the
 * comparison covers exactly the year that was filed. The selector is there for
 * returns whose period line cannot be read.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class FbrReturnForm extends Model
{
    /** @var UploadedFile|null The uploaded FBR return PDF */
    public $file;

    /** @var string|null Open password for a protected PDF (used transiently). */
    public $password;

    /** @var string|null Fiscal year label, or blank to use the return's period. */
    public $fiscalYear;

    /** @var string|null Cached extracted text of the upload (lazy). */
    private ?string $_textCache = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [
                ['file'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'pdf',
                'checkExtensionByMimeType' => false,
                'maxSize' => 10 * 1024 * 1024, // 10 MB (returns run to several pages)
                'tooBig' => Yii::t('app', 'File size cannot exceed {limit}.', ['limit' => '10 MB']),
                'wrongExtension' => Yii::t('app', 'Only .pdf files are allowed.'),
                'uploadRequired' => Yii::t('app', 'Upload the return PDF you downloaded from IRIS.'),
            ],
            [['password'], 'string', 'max' => 128],
            [['password'], 'trim'],
            [['fiscalYear'], 'string', 'max' => 32],
            [['fiscalYear'], 'trim'],
            [['fiscalYear'], 'validateFiscalYear'],
        ];
    }

    /**
     * Ensures the chosen fiscal year is one the app knows about.
     *
     * @param string $attribute
     */
    public function validateFiscalYear(string $attribute): void
    {
        if ((string) $this->$attribute === '') {
            return;
        }

        if ((new FiscalYearService())->getFiscalYearByLabel((string) $this->$attribute) === null) {
            $this->addError($attribute, Yii::t('app', 'Select a fiscal year from the list.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'file' => Yii::t('app', 'FBR return PDF'),
            'password' => Yii::t('app', 'PDF password'),
            'fiscalYear' => Yii::t('app', 'Fiscal year'),
        ];
    }

    /**
     * Loads the uploaded file instance from the request, if any.
     */
    public function loadUploadedFile(): void
    {
        $this->file = UploadedFile::getInstance($this, 'file');
    }

    /**
     * Returns the return PDF's text, extracted at most once.
     *
     * @return string
     */
    public function text(): string
    {
        if ($this->_textCache !== null) {
            return $this->_textCache;
        }

        if ($this->file === null || !is_uploaded_file((string) $this->file->tempName)) {
            return $this->_textCache = '';
        }

        return $this->_textCache = PdfText::extract($this->file->tempName, $this->password);
    }

    /**
     * Whether the uploaded PDF is password-protected.
     *
     * Tells "wrong or missing password" apart from "scanned image" when the
     * extracted text comes back empty.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->file !== null
            && is_uploaded_file((string) $this->file->tempName)
            && PdfText::isEncrypted($this->file->tempName);
    }

    /**
     * Adds the error that explains why extraction produced no text.
     */
    public function addEmptyTextError(): void
    {
        if ($this->isEncrypted()) {
            $this->addError('password', (string) $this->password === ''
                ? Yii::t('app', 'This PDF is password-protected. Enter its open password and try again.')
                : Yii::t('app', 'The PDF password appears to be incorrect. Please check it and try again.'));

            return;
        }

        $this->addError('file', Yii::t('app', 'Could not read any text from this PDF. Download the return again from IRIS rather than scanning a printout.'));
    }
}
