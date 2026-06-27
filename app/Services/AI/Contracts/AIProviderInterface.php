<?php

namespace App\Services\AI\Contracts;

use App\Models\AIProviderSetting;

interface AIProviderInterface
{
    public function generate(AIProviderSetting $providerSetting, array $payload): array;

    public function testConnection(AIProviderSetting $providerSetting): array;
}
