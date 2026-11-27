<?php

namespace App\Services\PaymentGateway;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class PaymentGatewayManager
{
    public function driver(?string $provider = null): PaymentGatewayInterface
    {
        $provider ??= (string) config('payment.provider', 'midtrans');

        return match ($provider) {
            'xendit' => new XenditQrisGateway(),
            default => new MidtransQrisGateway(),
        };
    }

    public function isQrisConfigured(): bool
    {
        return $this->driver()->isConfigured();
    }

    public function mode(): string
    {
        return (string) config('payment.mode', 'sandbox');
    }

    public function renderQrSvg(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'svgViewBoxSize' => 240,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($data);
    }
}
