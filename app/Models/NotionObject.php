<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotionObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'data',
    ];

    protected $casts = [
        'type' => 'string',
        'data' => 'json',
    ];
}
