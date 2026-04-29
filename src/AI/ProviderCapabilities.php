<?php
declare(strict_types=1);

final class ProviderCapabilities
{
    public function __construct(
        public bool $streaming,
        public bool $pdfInput,
        public bool $jsonMode,
        public bool $embeddings,
        public int $maxContextTokens = 0
    ) {
    }

    public function toArray(): array
    {
        return [
            'streaming' => $this->streaming,
            'pdf_input' => $this->pdfInput,
            'json_mode' => $this->jsonMode,
            'embeddings' => $this->embeddings,
            'max_context_tokens' => $this->maxContextTokens,
        ];
    }
}
