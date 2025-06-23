<?php

namespace Horus\Chronicles\Events;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Core\Dispatcher;
use Horus\Chronicles\Utils\UUID;

/**
 * Class BaseEvent
 *
 * Classe base abstrata para todos os eventos. Lida com a lógica comum
 * como geração de ID, timestamp e serialização básica.
 */
abstract class BaseEvent implements EventInterface
{
    protected string $id;
    protected int $timestamp;

    public function __construct()
    {
        $this->id = UUID::v4();
        $this->timestamp = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Combina o contexto específico do evento com os dados base.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getType(),
            'timestamp' => $this->timestamp,
            'context' => $this->getContext(),
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function toJsonPayload(): string
    {
        return json_encode([
            'class' => static::class,
            'data' => $this->toArray(),
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public static function fromArray(array $payload): self
    {
        $context = $payload['context'] ?? [];
        $event = new static(...array_values($context));
        $event->id = $payload['id'];
        $event->timestamp = $payload['timestamp'];
        return $event;
    }
}