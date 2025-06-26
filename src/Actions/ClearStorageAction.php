<?php

namespace Horus\Chronicles\Actions;

use Horus\Chronicles\Contracts\StorageInterface;

class ClearStorageAction
{
    public function __construct(private StorageInterface $storage)
    {
    }

    /**
     * Limpa o armazenamento e retorna um booleano de sucesso.
     *
     * @param string|null $eventType O tipo de evento a ser limpo.
     * @return bool
     */
    public function execute(?string $eventType = null): bool
    {
        return $this->storage->clear($eventType);
    }
}