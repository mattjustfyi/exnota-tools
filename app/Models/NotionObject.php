<?php

namespace App\Models;

use App\Enums\NotionObjectType;
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
        'type' => NotionObjectType::class,
        'data' => 'json',
    ];
}
