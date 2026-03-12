<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    protected $table = 'fuzzy_rules';

    protected $fillable = [
        'intent',
        'pattern',
        'confidence_threshold',
        'action',
        'response_template',
        'is_active',
    ];

    protected $casts = [
        'confidence_threshold' => 'float',
        'is_active' => 'boolean',
    ];
}
