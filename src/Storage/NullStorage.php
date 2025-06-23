<?php

namespace Horus\Chronicles\Storage;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Contracts\StorageInterface;

/**
 * Class NullStorage
 *
 * Um driver de armazenamento que não faz nada. Descarta os eventos.
 * Útil para testes de performance do sistema de filas/workers ou para
 * desabilitar a persistência sem desabilitar a captura.
 */
class NullStorage implements StorageInterface
{
    public function store(EventInterface $event): bool
    {
        // Faz nada, e reporta sucesso.
        return true;
    }

    public function clear(?string $eventType = null): bool
    {
        // Faz nada, e reporta sucesso.
        return true;
    }
}