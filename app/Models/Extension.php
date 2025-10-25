<?php

namespace App\Models;

use App\Enums\ExtensionType;
use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    protected $fillable = [
        'identifier',
        'name',
        'description',
        'version',
        'author',
        'types',
        'enabled',
        'migrations',
        'settings',
        'language_overrides',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'types' => 'array',
        'migrations' => 'array',
        'settings' => 'array',
        'language_overrides' => 'array',
    ];

    /**
     * Check if extension has a specific type.
     */
    public function hasType(ExtensionType|string $type): bool
    {
        $typeValue = $type instanceof ExtensionType ? $type->value : $type;

        return in_array($typeValue, $this->types ?? []);
    }

    /**
     * Get extension type objects.
     *
     * @return array<ExtensionType>
     */
    public function getTypeObjects(): array
    {
        return collect($this->types ?? ['plugin'])
            ->map(fn ($type) => ExtensionType::tryFrom($type))
            ->filter()
            ->toArray();
    }
}
