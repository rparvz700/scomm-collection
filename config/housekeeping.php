<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Housekeeping Recipient Group
    |--------------------------------------------------------------------------
    |
    | Email addresses that will receive a copy of the generated monthly
    | housekeeping & data integrity audit report document.
    |
    */
    'recipients' => [
        'hod.collection@scomm.test',
        'admin@scomm.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Agreement & BTRC License Expiry Threshold (in days)
    |--------------------------------------------------------------------------
    |
    | Expiry notification threshold. Scans for items expired or expiring
    | within 30 days.
    |
    */
    'expiry_threshold_days' => 30,
];
