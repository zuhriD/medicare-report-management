<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasLabel, HasColor
{
    case ON_TIME = 'on_time';
    case LATE = 'late';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ON_TIME => 'Tepat Waktu',
            self::LATE => 'Terlambat',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ON_TIME => 'success',
            self::LATE => 'danger',
        };
    }
}
