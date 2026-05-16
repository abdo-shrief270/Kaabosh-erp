<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Outbound email copy (English).
    |
    | Plain-text strings that drive Laravel notifications such as the
    | password-reset email. Keep one key per visible string so a translator
    | can edit copy without touching PHP code.
    |--------------------------------------------------------------------------
    */

    // Shown in the small grey "subcopy" block at the bottom of every action
    // email, including password reset. :actionText interpolates the button
    // label that was passed to MailMessage::action() — keep the placeholder.
    'trouble_clicking' => 'If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your browser:',

    'reset_password' => [
        'subject' => 'Reset your :app password',
        'greeting' => 'Hello :name,',
        'line_intro' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'line_expires' => 'This password reset link will expire in :count minutes.',
        'line_ignore' => 'If you did not request a password reset, no further action is required.',
        'salutation' => 'Regards, :app team',
    ],
];
