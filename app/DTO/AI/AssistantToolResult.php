<?php

namespace App\DTO\AI;

final readonly class AssistantToolResult
{
    public function __construct(
        public bool $ok,
        public string $message,
        public array $data = [],
        public array $meta = [],
    ) {}

    public static function success(string $message, array $data = [], array $meta = []): self
    {
        return new self(true, $message, $data, $meta);
    }

    public static function denied(string $message = 'This information is not available for the current account.'): self
    {
        return new self(false, $message, [], ['reason' => 'unauthorized']);
    }

    public static function invalid(string $message): self
    {
        return new self(false, $message, [], ['reason' => 'invalid_arguments']);
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }
}
