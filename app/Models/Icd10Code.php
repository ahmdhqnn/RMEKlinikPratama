<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icd10Code extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_id',
        'category',
        'is_common',
    ];

    protected function casts(): array
    {
        return [
            'is_common' => 'boolean',
        ];
    }
}
