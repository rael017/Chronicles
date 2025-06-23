<?php

namespace Horus\Chronicles\Events;

use Horus\Chronicles\Core\Dispatcher;

class CustomEvent extends BaseEvent
{
    public const TYPE = 'custom';

    /**
     * @param string $name Um nome descritivo para o evento customizado.
     * @param array $data Os dados estruturados a serem registrados.
     * @param string $level O nível do evento (INFO, DEBUG, WARN, ERROR).
     */
    public function __construct(
        public string $name,
        public array $data,
        public string $level = 'INFO'
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

        return [
            'name' => $this->name,
            'data' => $limiter->limit($sanitizer->sanitize($this->data)),
            'level' => $this->level,
        ];
    }
}