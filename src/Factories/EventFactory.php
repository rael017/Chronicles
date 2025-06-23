<?php

namespace Horus\Chronicles\Factories;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Core\Dispatcher;
use InvalidArgumentException;

/**
 * Class EventFactory
 *
 * Responsável por desserializar um payload JSON da fila e reconstruir
 * o objeto de evento original. Isso desacopla a lógica da fila das
 * implementações concretas de eventos.
 */
class EventFactory
{
    /**
     * Cria uma instância de um evento a partir de um payload JSON.
     *
     * @param string $jsonPayload O payload bruto vindo da fila.
     * @return EventInterface
     * @throws InvalidArgumentException Se o payload for inválido ou a classe não existir.
     */
    public function make(string $jsonPayload): EventInterface
    {
        $payload = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Payload do evento não é um JSON válido: ' . json_last_error_msg());
        }

        if (empty($payload['class']) || !class_exists($payload['class'])) {
            throw new InvalidArgumentException("A classe do evento '{$payload['class']}' não foi encontrada.");
        }

        if (!is_subclass_of($payload['class'], EventInterface::class)) {
            throw new InvalidArgumentException("A classe '{$payload['class']}' não implementa EventInterface.");
        }

        /** @var EventInterface $eventClass */
        $eventClass = $payload['class'];
        
        return $eventClass::fromArray($payload['data']);
    }
}