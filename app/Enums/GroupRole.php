<?php

namespace App\Enums;

enum GroupRole: string
{
    case Member = 'member';
    case Moderator = 'moderator';
    case Contributor = 'contributor';
    case Maintainer = 'maintainer';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Członek',
            self::Moderator => 'Moderator',
            self::Contributor => 'Współpracownik',
            self::Maintainer => 'Koordynator',
        };
    }

    /** @return list<string> */
    public function abilities(): array
    {
        return match ($this) {
            self::Member => [],
            self::Moderator => ['moderate-group'],
            self::Contributor => ['contribute-group'],
            self::Maintainer => ['moderate-group', 'contribute-group', 'manage-group'],
        };
    }
}
