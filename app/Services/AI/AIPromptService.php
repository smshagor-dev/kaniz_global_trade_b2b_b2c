<?php

namespace App\Services\AI;

use App\Models\AIPromptTemplate;
use InvalidArgumentException;
use Illuminate\Support\Str;

class AIPromptService
{
    public function activeTemplate(string $module, ?string $name = null): AIPromptTemplate
    {
        $query = AIPromptTemplate::query()
            ->where('module', $module)
            ->where('is_active', true);

        if ($name) {
            $query->where('name', $name);
        }

        $template = $query->orderByDesc('version')->first();

        if (!$template) {
            throw new InvalidArgumentException('No active AI prompt template found for module "' . $module . '".');
        }

        return $template;
    }

    public function render(string $module, array $variables = [], ?string $name = null): array
    {
        $template = $this->activeTemplate($module, $name);

        $replace = [];
        foreach ($variables as $key => $value) {
            $replace['{' . $key . '}'] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return [
            'template' => $template,
            'system_prompt' => strtr((string) ($template->system_prompt ?? ''), $replace),
            'user_prompt' => strtr((string) $template->user_prompt_template, $replace),
            'variables' => collect((array) $template->variables)->map(fn ($item) => Str::lower((string) $item))->all(),
        ];
    }
}
