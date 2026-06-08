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
