<?php

namespace App\Enums;

enum SeatStatus: string
{
    case Disponible = 'disponible';
    case Apartado = 'apartado';
    case Reservado = 'reservado';
    case Bloqueado = 'bloqueado';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
