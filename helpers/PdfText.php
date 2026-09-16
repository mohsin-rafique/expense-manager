<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\helpers;

use Yii;

/**
 * PdfText extracts the text of a PDF, preserving column alignment.
 *
 * Bank statements are tables, so the column layout carries meaning: it is what
 * lets a parser tell the Debit column from the Credit column. `pdftotext -table`
 * reconstructs that alignment and, unlike the pure-PHP fallback, can open
 * password-protected files. The smalot fallback exists for hosts without the
 * binary and only handles unencrypted PDFs.
 *
 * Shared by the statement reconciliation screen and the bank-statement import.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.3.0
 */
class PdfText
{
    /**
     * Extracts a PDF's text as newline-separated, column-aligned lines.
     *
     * Returns an empty string when the PDF has no extractable text (a scanned
     * image, say), cannot be read, or is encrypted with a password other than
     * the one supplied. Callers distinguish those cases with {@see isEncrypted()}.
     *
     * @param string $path Absolute path to the PDF
     * @param string|null $password Open password, when the file is protected
     * @return string
     */
    public static function extract(string $path, ?string $password = null): string
    {
        $text = self::viaBinary($path, $password);
        if (trim($text) !== '') {
            return $text;
        }

        return self::viaSmalot($path);
    }

    /**
     * Whether the PDF carries an /Encrypt dictionary.
     *
     * Used to tell "wrong or missing password" apart from "scanned image" when
     * extraction comes back empty.
     *
     * @param string $path Absolute path to the PDF
     * @return bool
     */
    public static function isEncrypted(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $head = (string) file_get_contents($path, false, null, 0, 2 * 1024 * 1024);

        return str_contains($head, '/Encrypt');
    }

    /**
     * Runs `pdftotext -table` over the PDF, supplying the open password when one
     * was given. Returns an empty string if the binary is disabled or missing,
     * the process fails, or the password is wrong.
     *
     * @param string $path Absolute path to the PDF
     * @param string|null $password
     * @return string
     */
    private static function viaBinary(string $path, ?string $password): string
    {
        $bin = Yii::$app->params['pdftotextPath'] ?? 'pdftotext';
        if (!is_string($bin) || trim($bin) === '' || !function_exists('proc_open')) {
            return '';
        }

        // `-table` keeps columns aligned, `-enc UTF-8` normalizes text, and the
        // final `-` streams the result to stdout. Password (if any) via `-upw`.
        $args = [$bin, '-table', '-enc', 'UTF-8'];
        if ((string) $password !== '') {
            $args[] = '-upw';
            $args[] = (string) $password;
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
     * PDFs (it throws "Secured pdf file are currently not supported") and does
     * not preserve column alignment as reliably as the binary.
     *
     * @param string $path Absolute path to the PDF
     * @return string
     */
    private static function viaSmalot(string $path): string
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
