<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiPrompt extends AIPromptTemplate
{
    use HasFactory;

    protected $table = 'ai_prompt_templates';

    protected $appends = ['identifier', 'prompt'];

    public function getIdentifierAttribute(): string
    {
        return (string) ($this->legacy_identifier ?: $this->module . '_' . $this->name);
    }

    public function getPromptAttribute(): string
    {
        return (string) $this->user_prompt_template;
    }

    public function setPromptAttribute($value): void
    {
        $this->attributes['user_prompt_template'] = $value;
    }
}
