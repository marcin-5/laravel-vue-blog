<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Blogger = 'blogger';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Blogger => 'Blogger',
            self::User => 'Użytkownik',
        };
    }
}
