<?php

return [
    'paystack' => [
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'plan_code' => env('PAYSTACK_PREMIUM_PLAN_CODE')
    ]
];
