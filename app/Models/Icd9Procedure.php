<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icd9Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_id',
        'category',
    ];
}
