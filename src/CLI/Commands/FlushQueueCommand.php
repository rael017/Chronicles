<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Actions\FlushQueueAction;
use Horus\Chronicles\Core\Dispatcher;

class FlushQueueCommand extends BaseCommand
{
    protected string $description = "Limpa TODOS os eventos da fila principal de forma irreversível.";

    public function execute(array $args): int
    {
        $this->output("AVISO: Esta ação é destrutiva e não pode ser desfeita.", 'yellow');
        
        $confirmation = readline("Você tem certeza que deseja limpar a fila principal? [s/N]: ");

        if (strtolower(trim($confirmation)) !== 's') {
            $this->output("Ação cancelada.", 'green');
            return 0;
        }

        // Pega a dependência (QueueInterface) através do Dispatcher
        $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
        
        // Cria e executa a Action
        $action = new FlushQueueAction($queue);
        $itemCount = $queue->getQueueSize(); // Pega o tamanho antes de limpar
        $action->execute();

        if ($itemCount > 0) {
            $this->output("{$itemCount} evento(s) foram removidos da fila '{$queue->getQueueName()}' com sucesso.", 'green');
        } else {
            $this->output("A fila '{$queue->getQueueName()}' já estava vazia.", 'green');
        }

        return 0;
    }
}