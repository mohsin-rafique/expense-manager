<?php

return [
    'adminEmail' => 'admin@@mailintor.com',
    'senderEmail' => 'noreply@mailintor.com',
    'senderName' => 'Expense Manager Mailer',
    'bsVersion' => '5.x',
    'defaultPageSize' => 100,
    'uploadPath' => 'uploads/',
    'user.passwordResetTokenExpire' => 3600,

    /*
    |--------------------------------------------------------------------------
    | PDF text extraction (statement reconciliation)
    |--------------------------------------------------------------------------
    |
    | Path to the `pdftotext` binary (poppler or xpdf). It is used to read
    | bank-statement PDFs - including password-protected ones, which the
    | pure-PHP smalot/pdfparser fallback cannot open.
    |
    | Resolution order: a PDFTOTEXT_PATH environment variable (if set), then a
    | probe of common install locations, then the bare 'pdftotext' command from
    | the system PATH. Set an absolute path here to override. '' disables the
    | external binary and falls back to smalot (unencrypted PDFs only).
    |
    */
    'pdftotextPath' => (static function () {
        $env = getenv('PDFTOTEXT_PATH') ?: ($_ENV['PDFTOTEXT_PATH'] ?? $_SERVER['PDFTOTEXT_PATH'] ?? '');
        if (is_string($env) && $env !== '') {
            return $env;
        }
        foreach ([
            'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe',
            'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
            'C:\\poppler\\bin\\pdftotext.exe',
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return 'pdftotext';
    })(),

    /*
    |--------------------------------------------------------------------------
    | Internationalization (i18n)
    |--------------------------------------------------------------------------
    |
    | Languages available in the language switcher. The array key is the
    | locale code (used by Yii::$app->language and the message folder name)
    | and the value is the language's native display name.
    |
    | `rtlLanguages` lists the codes that should render right-to-left.
    |
    */
    'supportedLanguages' => [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'ur' => 'اردو',
        'de' => 'Deutsch',
    ],
    'defaultLanguage' => 'en',
    'rtlLanguages' => ['ur'],
];
