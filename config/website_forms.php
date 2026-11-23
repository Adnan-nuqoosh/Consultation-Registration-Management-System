<?php

return [
    'admin_email' => env('WEBSITE_FORMS_ADMIN_EMAIL', env('CONSULTATION_ADMIN_EMAIL')),
    'rate_limit_per_minute' => (int) env('WEBSITE_FORMS_RATE_LIMIT', 5),
];
