<?php

namespace App\Services\PaymentGateway;

use RuntimeException;

class PaymentGatewayNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'Pembayaran QRIS belum aktif. Silakan konfigurasi credential payment gateway di environment.')
    {
        parent::__construct($message);
    }
}
