<?php

return [
    // Keep this endpoint on the application's own host/private network.
    'url' => env('RECEIPT_OCR_URL', 'http://127.0.0.1:8091'),
    'timeout' => 75,
];
