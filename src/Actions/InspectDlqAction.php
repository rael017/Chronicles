<?php

namespace Horus\Chronicles\Actions;

use Horus\Chronicles\Contracts\QueueInterface;

class InspectDlqAction
{
    public function __construct(private QueueInterface $queue)
    {
    }

    /**
     * Retorna um array de payloads de eventos da DLQ.
     *
     * @param int $limit O número de eventos a serem buscados.
     * @return array
     */
    public function execute(int $limit = 50): array
    {
        return $this->queue->inspectDlq($limit);
    }
}