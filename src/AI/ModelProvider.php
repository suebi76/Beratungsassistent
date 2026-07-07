<?php
declare(strict_types=1);

interface ModelProvider
{
    public function id(): string;

    public function label(): string;

    public function capabilities(): ProviderCapabilities;

    public function generateText(ModelRequest $request): array;

    public function streamText(ModelRequest $request, callable $onDelta): array;

    public function testConnection(): array;
}
