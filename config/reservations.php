<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seat Reservation Hold Duration
    |--------------------------------------------------------------------------
    |
    | Number of minutes a seat remains held before being automatically
    | released by the scheduler.
    |
    */
    'hold_minutes' => (int) env('RESERVATION_HOLD_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Default Currency & Pricing
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('DEFAULT_CURRENCY', 'MXN'),
    'default_ticket_price' => (float) env('TICKET_PRICE_MXN', 150.00),
];
