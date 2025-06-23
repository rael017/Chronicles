<?php

namespace Horus\Chronicles\Events;

use Horus\Chronicles\Core\Dispatcher;

class HttpEvent extends BaseEvent
{
    public const TYPE = 'http';

    public function __construct(
        public string $method,
        public string $uri,
        public int $status,
        public float $duration,
        public ?string $ip,
        public array $headers = [],
        public array $payload = [],
        public array $response = []
    ) {
        parent::__construct();
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getContext(): array
    {
        $sanitizer = Dispatcher::getSanitizer();
        $limiter = Dispatcher::getPayloadLimiter();

        // Aplica sanitização e depois o limite
        $safeHeaders = $sanitizer->sanitize($this->headers);
        $safePayload = $limiter->limit($sanitizer->sanitize($this->payload));
        $safeResponse = $limiter->limit($sanitizer->sanitize($this->response));

        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'status' => $this->status,
            'duration' => $this->duration,
            'ip' => $this->ip,
            'headers' => $safeHeaders,
            'payload' => $safePayload,
            'response' => $safeResponse,
        ];
    }
}