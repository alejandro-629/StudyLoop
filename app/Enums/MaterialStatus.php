<?php

declare(strict_types=1);

namespace App\Enums;

enum MaterialStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Parsing'),
            self::Ready => __('Ready'),
            self::Failed => __('Failed'),
        };
    }
}
