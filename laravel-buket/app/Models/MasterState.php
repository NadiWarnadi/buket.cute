<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterState extends Model
{
    protected $fillable = [
        'name', 'type', 'prompt_text', 'input_key',
        'validation_rules', 'fuzzy_context',
        'next_state_id', 'fallback_state_id',
        'prerequisite_keys', 'resume_message',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'prerequisite_keys' => 'array',
    ];

    public function nextState()
    {
        return $this->belongsTo(MasterState::class, 'next_state_id');
    }

    public function fallbackState()
    {
        return $this->belongsTo(MasterState::class, 'fallback_state_id');
    }
}