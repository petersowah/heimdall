<?php

return [
    'path' => env('HEIMDALL_PATH', 'heimdall'),

    'middleware' => ['web', 'auth'],

    'domain' => null,

    'alert_emails' => env('HEIMDALL_ALERT_EMAILS', ''),
];
