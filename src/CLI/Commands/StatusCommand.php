<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;
use Horus\Chronicles\Actions\GetStatusAction;
class StatusCommand extends BaseCommand
{
    protected string $description = "Exibe o status atual do sistema Chronicles.";

    public function execute(array $args): int
    {
        // Pega as dependências necessárias através do Dispatcher
        $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
        
        // Cria e executa a Action para obter os dados brutos
        $statusAction = new GetStatusAction($queue);
        $statusData = $statusAction->execute();

        // A partir daqui, é apenas apresentação
        $this->output("Status do Sistema Chronicles", 'yellow');
        $this->output("-----------------------------");

        $enabledText = $statusData['enabled'] ? "\033[0;32mHabilitado\033[0m" : "\033[0;31mDesabilitado\033[0m";
        $this->output("Status Geral: " . $enabledText);
        $this->output("Driver de Fila: " . $statusData['queue_driver']);
        $this->output("Driver de Armazenamento: " . $statusData['storage_driver']);
        $this->output("");

        $this->output("Status da Fila:", 'green');
        $this->output("Fila Principal ({$statusData['queue_name']}): {$statusData['queue_size']} eventos");
        $this->output("Fila de Erros (DLQ) ({$statusData['dlq_name']}): {$statusData['dlq_size']} eventos");

        if ($statusData['dlq_size'] > 0) {
            $this->output("  \033[0;31mALERTA: Existem eventos na DLQ. Use `dlq:inspect` para investigar.\033[0m");
        }
        
        return 0;
    }
}