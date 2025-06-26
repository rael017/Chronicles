<?php

namespace Horus\Chronicles\Actions;

use Horus\Chronicles\Contracts\QueueInterface;

class ReplayDlqAction
{
    public function __construct(private QueueInterface $queue)
    {
    }

    /**
     * Tenta reenfileirar todos os eventos da DLQ e retorna o número de eventos movidos.
     *
     * @return int
     */
    public function execute(): int
    {
        $itemsToReplay = $this->queue->inspectDlq(5000); // Pega um lote grande de itens
        $replayedCount = 0;

        foreach ($itemsToReplay as $payload) {
            // Tenta remover da DLQ primeiro
            if ($this->queue->removeFromDlq($payload) > 0) {
                 // Se bem-sucedido, adiciona na fila principal
                $this->queue->push($payload);
                $replayedCount++;
            }
        }

        return $replayedCount;
    }
}