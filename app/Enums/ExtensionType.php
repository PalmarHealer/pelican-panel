<?php

namespace App\Enums;

enum ExtensionType: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';
    case LanguagePack = 'language-pack';

    public function label(): string
    {
        return match ($this) {
            self::Plugin => 'Plugin',
            self::Theme => 'Theme',
            self::LanguagePack => 'Language Pack',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Plugin => 'tabler-puzzle',
            self::Theme => 'tabler-palette',
            self::LanguagePack => 'tabler-language',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Plugin => 'primary',
            self::Theme => 'info',
            self::LanguagePack => 'success',
        };
    }
}
