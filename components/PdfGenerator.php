<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use Mpdf\Mpdf;

/**
 * PdfGenerator wraps mPDF to render HTML into downloadable PDF documents.
 *
 * It configures a writable temp directory under @runtime, applies sensible
 * defaults for financial reports (A4, margins, footer with page numbers), and
 * honours the active application language for right-to-left scripts such as
 * Urdu so generated reports render correctly in every supported locale.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class PdfGenerator
{
    /**
     * Renders HTML to a PDF and streams it to the browser as a download.
     *
     * @param string $html Full HTML body for the document
     * @param string $filename Suggested download filename (".pdf" appended if missing)
     * @param array $options Optional overrides: 'title', 'orientation' ('P'|'L'),
     *   'footer', 'inline' (bool, default true), 'returnContent' (bool)
     * @return string|\yii\web\Response The PDF binary when 'returnContent' is set,
     *   otherwise the configured Yii response carrying the file.
     * @throws \Mpdf\MpdfException
     */
    public function download(string $html, string $filename, array $options = [])
    {
        $mpdf = $this->makeMpdf($options);
        $mpdf->WriteHTML($html);

        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        $pdf = $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        if (!empty($options['returnContent'])) {
            return $pdf;
        }

        // Hand the binary to Yii's response so it is sent cleanly through the
        // normal response lifecycle (avoids the debug toolbar or any trailing
        // output corrupting the PDF stream).
        return Yii::$app->response->sendContentAsFile($pdf, $filename, [
            'mimeType' => 'application/pdf',
            'inline' => $options['inline'] ?? true,
        ]);
    }

    /**
     * Builds a configured Mpdf instance.
     *
     * @param array $options
     * @return Mpdf
     * @throws \Mpdf\MpdfException
     */
    private function makeMpdf(array $options): Mpdf
    {
        $tempDir = Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $isRtl = $this->isRtlLanguage();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => ($options['orientation'] ?? 'P') === 'L' ? 'A4-L' : 'A4',
            'orientation' => $options['orientation'] ?? 'P',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 16,
            'margin_bottom' => 18,
            'margin_header' => 8,
            'margin_footer' => 9,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetCreator(Yii::$app->name);
        $mpdf->SetTitle($options['title'] ?? Yii::$app->name);

        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
        }

        $footer = $options['footer'] ?? ($this->defaultFooter());
        $mpdf->SetHTMLFooter($footer);

        return $mpdf;
    }

    /**
     * Returns the default footer HTML (app name + generated date + page x/y).
     *
     * @return string
     */
    private function defaultFooter(): string
    {
        $app = htmlspecialchars(Yii::$app->name, ENT_QUOTES, 'UTF-8');
        $generated = Yii::t('app', 'Generated {date}', [
            'date' => Yii::$app->formatter->asDatetime(time(), 'medium'),
        ]);
        $generated = htmlspecialchars($generated, ENT_QUOTES, 'UTF-8');

        return '<div style="border-top:0.5px solid #ccc; padding-top:4px; font-size:8pt; color:#888;">'
            . '<table width="100%"><tr>'
            . '<td style="text-align:left;">' . $app . '</td>'
            . '<td style="text-align:center;">' . $generated . '</td>'
            . '<td style="text-align:right;">{PAGENO} / {nbpg}</td>'
            . '</tr></table></div>';
    }

    /**
     * Whether the active language is right-to-left.
     *
     * @return bool
     */
    private function isRtlLanguage(): bool
    {
        $rtl = Yii::$app->params['rtlLanguages'] ?? [];
        return in_array(Yii::$app->language, $rtl, true);
    }
}
