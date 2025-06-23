<?php

namespace Horus\Chronicles\Contracts;

/**
 * Interface StorageInterface
 *
 * Define o contrato para todos os drivers de armazenamento. O armazenamento é
 * a persistência de longo prazo dos eventos processados pelo worker.
 */
interface StorageInterface
{
    /**
     * Armazena um evento no backend de persistência.
     * @param EventInterface $event
     * @return bool Retorna true em sucesso, false em falha.
     */
    public function store(EventInterface $event): bool;

    /**
     * Limpa todos os eventos, opcionalmente filtrando por tipo.
     * @param string|null $eventType O tipo de evento a ser limpo (ex: 'http'). Se nulo, limpa todos.
     * @return bool Retorna true em sucesso.
     */
    public function clear(?string $eventType = null): bool;
}