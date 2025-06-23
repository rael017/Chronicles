<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class StatusCommand extends BaseCommand
{
    protected string $description = "Exibe o status atual do sistema Chronicles.";

    public function execute(array $args): int
    {
        $this->output("Status do Sistema Chronicles", 'yellow');
        $this->output("-----------------------------");

        // Status Geral
        $enabled = Dispatcher::getConfig('enabled') ? "\033[0;32mHabilitado\033[0m" : "\033[0;31mDesabilitado\033[0m";
        $this->output("Status Geral: " . $enabled);
        $this->output("Driver de Fila: " . Dispatcher::getConfig('queue_driver'));
        $this->output("Driver de Armazenamento: " . Dispatcher::getConfig('storage_driver'));
        $this->output("");

        // Status da Fila
        $this->output("Status da Fila:", 'green');
        $queueFactory = Dispatcher::getQueueFactory();
        $queue = $queueFactory->make(Dispatcher::getConfig('queue_driver'));
        
        $queueSize = $queue->getQueueSize();
        $dlqSize = $queue->getDlqSize();

        $this->output("  Fila Principal ({$queue->getQueueName()}): {$queueSize} eventos");
        $this->output("  Fila de Erros (DLQ) ({$queue->getDlqName()}): {$dlqSize} eventos");

        if ($queueSize > 1000) {
            $this->output("  \033[1;33mAVISO: A fila principal está com muitos eventos. Verifique se os workers estão rodando.\033[0m");
        }
        if ($dlqSize > 0) {
            $this->output("  \033[0;31mALERTA: Existem eventos na DLQ. Use `dlq:inspect` para investigar.\033[0m");
        }

        return 0;
    }
}