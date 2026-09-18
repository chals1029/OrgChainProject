<?php

return [
    'office_login_path' => env('OFFICE_LOGIN_PATH', '/orgchain-office-access-a9e2f71c4b83'),

    /*
    | When true (local E2E only), OTP send responses may include debug_code and
    | Cache holds auth_e2e_otp:{sr_code}. Never enable in production.
    */
    'expose_test_otp' => (bool) env('EXPOSE_TEST_OTP', false),
];
