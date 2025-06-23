<?php

namespace Horus\Chronicles\Contracts;

/**
 * Interface QueueInterface
 *
 * Define o contrato para todos os drivers de fila. A fila é responsável
 * por atuar como um buffer assíncrono entre a captura e o armazenamento do evento.
 */
interface QueueInterface
{
    /**
     * Adiciona um payload de evento serializado à fila.
     * @param string $payload
     * @return void
     */
    public function push(string $payload): void;

    /**
     * Remove e retorna o próximo payload da fila.
     * @return string|null
     */
    public function pop(): ?string;

    /**
     * Move um payload de evento bruto para a Dead-Letter Queue (DLQ).
     * @param string $payload
     * @return void
     */
    public function moveToDlq(string $payload): void;

    /**
     * Retorna o nome da fila principal.
     * @return string
     */
    public function getQueueName(): string;

    /**
     * Retorna o nome da Dead-Letter Queue.
     * @return string|null
     */
    public function getDlqName(): ?string;

    /**
     * Retorna o tamanho atual da fila principal.
     * @return int
     */
    public function getQueueSize(): int;

    /**
     * Retorna o tamanho atual da Dead-Letter Queue.
     * @return int
     */
    public function getDlqSize(): int;

    /**
     * Limpa todos os itens da fila principal.
     * @return int O número de itens removidos.
     */
    public function flushQueue(): int;
    
    /**
     * Retorna todos os itens da Dead-Letter Queue para inspeção.
     * @param int $limit
     * @return array
     */
    public function inspectDlq(int $limit = 50): array;

    /**
     * Remove um payload específico da DLQ.
     * @param string $payload
     * @return int
     */
    public function removeFromDlq(string $payload): int;
}