<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_code',
        'system_name',
        'organization_id',
        'client_id',
        'client_secret',
        'base_url',
        'auth_url',
        'environment',
        'is_active',
        'last_synced_at',
        'sync_status',
    ];

    protected $hidden = ['client_secret'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'client_secret' => 'encrypted',
        ];
    }
}
