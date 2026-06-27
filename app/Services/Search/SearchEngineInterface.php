<?php

namespace App\Services\Search;

interface SearchEngineInterface
{
    public function search(array $payload): array;

    public function autocomplete(array $payload): array;

    public function index(string $indexName, string $documentId, array $document): void;

    public function bulkIndex(string $indexName, array $documents): void;

    public function delete(string $indexName, string $documentId): void;

    public function createIndex(string $indexName, array $schema = []): void;

    public function deleteIndex(string $indexName): void;

    public function health(string $indexName): array;
}
