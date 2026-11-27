<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik / Admin',
            self::Kasir => 'Kasir',
        };
    }
}
