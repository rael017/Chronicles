<?php

namespace Horus\Chronicles\Events;

use Horus\Chronicles\Core\Dispatcher;

class SqlEvent extends BaseEvent
{
    public const TYPE = 'sql';

    public function __construct(
        public string $query,
        public array $params,
        public float $duration,
        public string $connection,
        public ?string $error = null
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
        
        return [
            'query' => $this->query,
            'params' => $sanitizer->sanitize($this->params),
            'duration' => $this->duration,
            'connection' => $this->connection,
            'error' => $this->error,
        ];
    }
}