<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    protected $fillable = [
        'identifier',
        'name',
        'version',
        'author',
        'enabled',
        'migrations',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'migrations' => 'array',
        'settings' => 'array',
    ];
}
