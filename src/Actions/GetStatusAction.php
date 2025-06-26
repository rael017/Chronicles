<?php

namespace Horus\Chronicles\Actions;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Core\Dispatcher;

class GetStatusAction
{
    public function __construct(private QueueInterface $queue)
    {
    }

    /**
     * Executa a lógica para buscar os dados de status e os retorna como um array.
     *
     * @return array
     */
    public function execute(): array
    {
        $config = Dispatcher::getConfig();

        return [
            'enabled' => $config['enabled'] ?? false,
            'queue_driver' => $config['queue_driver'] ?? 'N/A',
            'storage_driver' => $config['storage_driver'] ?? 'N/A',
            'queue_name' => $this->queue->getQueueName(),
            'queue_size' => $this->queue->getQueueSize(),
            'dlq_name' => $this->queue->getDlqName(),
            'dlq_size' => $this->queue->getDlqSize(),
        ];
    }
}