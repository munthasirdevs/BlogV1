<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'model_name', 'prompt', 'generated_content',
        'generation_type', 'token_usage', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'token_usage' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
