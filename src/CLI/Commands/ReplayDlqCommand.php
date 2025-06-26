<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Actions\ReplayDlqAction;
use Horus\Chronicles\Core\Dispatcher;

class ReplayDlqCommand extends BaseCommand
{
    protected string $description = "Reprocessa eventos da DLQ, movendo-os para a fila principal. Use --all.";

    public function execute(array $args): int
    {
        $this->output("Reprocessando eventos da Dead-Letter Queue (DLQ)...", 'yellow');

        if (!in_array('--all', $args)) {
            $this->output("Por segurança, você deve especificar a flag --all para reprocessar todos os eventos.");
            $this->output("Exemplo: php bin/chronicles dlq:replay --all");
            return 1;
        }
        
        $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
        $dlqSize = $queue->getDlqSize();

        if ($dlqSize === 0) {
            $this->output("A DLQ já está vazia.", 'green');
            return 0;
        }
        
        $confirmation = readline("Encontrados {$dlqSize} eventos na DLQ. Deseja movê-los para a fila principal? [s/N]: ");
        if (strtolower(trim($confirmation)) !== 's') {
            $this->output("Ação cancelada.", 'green');
            return 0;
        }

        $action = new ReplayDlqAction($queue);
        
        $this->output("Iniciando o reprocessamento...");
        
        $replayedCount = $action->execute();
        
        $this->output("{$replayedCount} de {$dlqSize} evento(s) foram movidos para a fila principal.", 'green');
        if ($replayedCount < $dlqSize) {
            $this->output("Alguns eventos podem não ter sido movidos devido a erros de concorrência. Tente novamente.", 'yellow');
        }

        return 0;
    }
}