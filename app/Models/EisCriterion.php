<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EisCriterion extends Model
{
    /** @use HasFactory<\Database\Factories\EisCriterionFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];
}
