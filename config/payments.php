<?php

use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Registry
    |--------------------------------------------------------------------------
    |
    | لإضافة gateway جديد: اعمل class جديد implements PaymentGatewayInterface
    | وسجله هنا. صفر تعديل على PaymentGatewayResolver أو أي كود قديم (OCP).
    |
    */

    'gateways' => [

        'credit_card' => [
            'class' => CreditCardGateway::class,
            'config' => [
                'apiKey' => env('CREDIT_CARD_GATEWAY_API_KEY'),
                'secret' => env('CREDIT_CARD_GATEWAY_SECRET'),
            ],
        ],

        'paypal' => [
            'class' => PaypalGateway::class,
            'config' => [
                'clientId' => env('PAYPAL_CLIENT_ID'),
                'secret' => env('PAYPAL_SECRET'),
            ],
        ],

    ],

];
