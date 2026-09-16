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

/**
 * ReconcileForm backs the bank-statement reconciliation screen.
 *
 * The user provides the statement's debit (expense) lines either by pasting
 * them into a textarea or by uploading a tab-separated file. Nothing is written
 * to the database - the statement is only compared against existing expenses.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class ReconcileForm extends Model
{
    /** @var string|null Pasted statement text (tab-separated lines) */
    public $statement;

    /** @var UploadedFile|null Optional uploaded statement file */
    public $file;

    /** @var string|null Open password for a protected PDF (used transiently). */
    public $password;

    /** @var string|null Cached raw text of the current input (lazy). */
    private ?string $_rawContentCache = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['statement'], 'string'],
            [['statement'], 'trim'],
            [
                ['file'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => 'tsv, txt, csv, pdf',
                'checkExtensionByMimeType' => false,
                'maxSize' => 5 * 1024 * 1024, // 5 MB (PDF statements can be large)
                'tooBig' => Yii::t('app', 'File size cannot exceed {limit}.', ['limit' => '5 MB']),
                'wrongExtension' => Yii::t('app', 'Only .csv, .tsv, .txt or .pdf files are allowed.'),
            ],
            [['statement'], 'validateHasInput'],
            [['password'], 'string', 'max' => 128],
            [['password'], 'trim'],
        ];
    }

    /**
     * Ensures at least one input source (paste or file) was provided.
     *
     * @param string $attribute
     */
    public function validateHasInput(string $attribute): void
    {
        if (trim((string) $this->statement) === '' && $this->file === null) {
            $this->addError($attribute, Yii::t('app', 'Paste the statement lines or upload a file.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'statement' => Yii::t('app', 'Statement lines'),
            'file' => Yii::t('app', 'Statement file'),
            'password' => Yii::t('app', 'PDF password'),
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
     * Returns the raw statement text from the uploaded file or the pasted input.
     *
     * PDF uploads are run through a text extractor first; everything else is
     * read verbatim. The result is cached so extraction happens at most once.
     *
     * @return string
     */
    public function rawContent(): string
    {
        if ($this->_rawContentCache !== null) {
            return $this->_rawContentCache;
        }

        if ($this->file !== null && is_uploaded_file($this->file->tempName)) {
            $text = $this->isPdf()
                ? $this->extractPdfText($this->file->tempName)
                : (string) file_get_contents($this->file->tempName);

            return $this->_rawContentCache = $text;
        }

        return $this->_rawContentCache = (string) $this->statement;
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
     * Whether the uploaded PDF is encrypted (carries an /Encrypt dictionary).
     * Used to tell "wrong/missing password" apart from "scanned image" when
     * text extraction comes back empty.
     *
     * @return bool
     */
    public function isEncryptedPdf(): bool
    {
        if (!$this->isPdf() || !is_uploaded_file((string) $this->file->tempName)) {
            return false;
        }
        $head = (string) file_get_contents($this->file->tempName, false, null, 0, 2 * 1024 * 1024);

        return str_contains($head, '/Encrypt');
    }

    /**
     * Extracts the text of a PDF into a newline-separated string.
     *
     * Uses `pdftotext -table` when the binary is available: it reconstructs
     * tabular bank statements far better than a plain text dump and, unlike the
     * pure-PHP smalot fallback, can open password-protected PDFs (via the
     * user-supplied open password). Falls back to smalot when the binary is not
     * configured/available and the PDF is unencrypted.
     *
     * Returns an empty string when the PDF has no extractable text (e.g. a
     * scanned image) or cannot be read; the caller surfaces a friendly error.
     *
     * @param string $path Absolute path to the uploaded PDF
     * @return string
     */
    private function extractPdfText(string $path): string
    {
        $text = $this->extractPdfViaBinary($path);
        if (trim($text) !== '') {
            return $text;
        }

        return $this->extractPdfViaSmalot($path);
    }

    /**
     * Runs `pdftotext -table` over the PDF, supplying the open password when one
     * was provided. Returns an empty string if the binary is disabled/missing,
     * the process fails, or the password is wrong.
     *
     * @param string $path Absolute path to the uploaded PDF
     * @return string
     */
    private function extractPdfViaBinary(string $path): string
    {
        $bin = Yii::$app->params['pdftotextPath'] ?? 'pdftotext';
        if (!is_string($bin) || trim($bin) === '' || !function_exists('proc_open')) {
            return '';
        }

        // `-table` keeps columns aligned, `-enc UTF-8` normalizes text, and the
        // final `-` streams the result to stdout. Password (if any) via `-upw`.
        $args = [$bin, '-table', '-enc', 'UTF-8'];
        if ((string) $this->password !== '') {
            $args[] = '-upw';
            $args[] = (string) $this->password;
        }
        $args[] = $path;
        $args[] = '-';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        try {
            // Pass argv as an array so PHP builds the command line itself
            // (PHP 7.4+). On Windows this bypasses cmd.exe, avoiding its quoting
            // pitfalls with spaced paths, and never shell-interprets the
            // password.
            $proc = @proc_open($args, $descriptors, $pipes);
            if (!is_resource($proc)) {
                return '';
            }
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);

            if ($code !== 0) {
                Yii::warning('pdftotext exited with code ' . $code . ': ' . trim((string) $err), __METHOD__);
                return '';
            }

            return (string) $out;
        } catch (\Throwable $e) {
            Yii::error('pdftotext invocation failed: ' . $e->getMessage(), __METHOD__);
            return '';
        }
    }

    /**
     * Pure-PHP fallback extraction via smalot/pdfparser. Cannot open encrypted
     * PDFs (it throws "Secured pdf file are currently not supported").
     *
     * @param string $path Absolute path to the uploaded PDF
     * @return string
     */
    private function extractPdfViaSmalot(string $path): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);

            return $pdf->getText();
        } catch (\Throwable $e) {
            Yii::error('PDF statement parse failed: ' . $e->getMessage(), __METHOD__);

            return '';
        }
    }
}
