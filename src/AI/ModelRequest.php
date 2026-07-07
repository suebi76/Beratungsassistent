<?php
declare(strict_types=1);

final class ModelRequest
{
    private function __construct(
        private array $parts,
        private array $contents,
        private string $systemInstruction,
        private array $options,
        private string $purpose
    ) {
    }

    public static function textGeneration(array $parts, array $options = [], string $purpose = 'generation'): self
    {
        return new self($parts, [], '', $options, $purpose);
    }

    public static function chatStream(array $contents, string $systemInstruction, array $options = []): self
    {
        return new self([], $contents, $systemInstruction, $options, 'chat');
    }

    public function parts(): array
    {
        return $this->parts;
    }

    public function contents(): array
    {
        return $this->contents;
    }

    public function systemInstruction(): string
    {
        return $this->systemInstruction;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }
}
