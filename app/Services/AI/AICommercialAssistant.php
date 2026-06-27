<?php

namespace App\Services\AI;

class AICommercialAssistant
{
    public function __construct(
        protected AIManager $manager,
        protected AIRequestService $requestService,
        protected AIPromptService $promptService
    ) {
    }

    public function enrich(string $module, array $variables, array $payload = []): array
    {
        if (!$this->manager->defaultProvider()) {
            return [
                'content' => null,
                'provider' => null,
                'model' => null,
                'used_ai' => false,
            ];
        }

        try {
            $rendered = $this->promptService->render($module, $variables);
            $result = $this->requestService->request(array_merge([
                'module' => $module,
                'system_prompt' => $rendered['system_prompt'],
                'prompt' => $rendered['user_prompt'],
            ], $payload));

            return [
                'content' => $result['content'],
                'provider' => $result['provider'],
                'model' => $result['model'],
                'used_ai' => true,
            ];
        } catch (\Throwable $throwable) {
            return [
                'content' => null,
                'provider' => null,
                'model' => null,
                'used_ai' => false,
                'error' => $throwable->getMessage(),
            ];
        }
    }
}
