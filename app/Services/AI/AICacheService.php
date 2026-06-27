<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

class AICacheService
{
    public function key(string $module, string $model, string $promptHash): string
    {
        return 'ai:' . $module . ':' . $model . ':' . $promptHash;
    }

    public function get(string $module, string $model, string $promptHash): ?array
    {
        return Cache::store(config('ai.cache.store'))->get($this->key($module, $model, $promptHash));
    }

    public function put(string $module, string $model, string $promptHash, array $value, ?int $ttlSeconds = null): void
    {
        Cache::store(config('ai.cache.store'))->put(
            $this->key($module, $model, $promptHash),
            $value,
            now()->addSeconds($ttlSeconds ?: (int) config('ai.cache.ttl_seconds', 3600))
        );
    }

    public function forget(string $module, string $model, string $promptHash): void
    {
        Cache::store(config('ai.cache.store'))->forget($this->key($module, $model, $promptHash));
    }
}
