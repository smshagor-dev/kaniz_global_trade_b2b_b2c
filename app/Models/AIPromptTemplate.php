<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class AIPromptTemplate extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'ai_prompt_templates';

    protected $fillable = [
        'module',
        'name',
        'legacy_identifier',
        'system_prompt',
        'user_prompt_template',
        'variables',
        'version',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'version' => 'integer',
        'is_active' => 'boolean',
    ];
}
