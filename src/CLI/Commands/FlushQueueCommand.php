<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class FlushQueueCommand extends BaseCommand
{
    protected string $description = "Limpa TODOS os eventos da fila principal de forma irreversível.";

    public function execute(array $args): int
    {
        $this->output("AVISO: Esta ação é destrutiva e não pode ser desfeita.", 'yellow');
        
        // Pede confirmação ao usuário
        $confirmation = readline("Você tem certeza que deseja limpar a fila principal? [s/N]: ");

        if (strtolower(trim($confirmation)) !== 's') {
            $this->output("Ação cancelada.", 'green');
            return 0;
        }

        try {
            $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
            
            $this->output("Limpando a fila '{$queue->getQueueName()}'...");
            
            $itemCount = $queue->getQueueSize();
            
            if ($itemCount === 0) {
                 $this->output("A fila já estava vazia.", 'green');
                 return 0;
            }

            $queue->flushQueue();

            $this->output("{$itemCount} evento(s) foram removidos da fila com sucesso.", 'green');

        } catch (\Exception $e) {
            $this->output("Ocorreu um erro ao tentar limpar a fila: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}