<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class ReplayDlqCommand extends BaseCommand
{
    protected string $description = "Reprocessa eventos da DLQ, movendo-os para a fila principal. Use --all.";

    public function execute(array $args): int
    {
        $this->output("Reprocessando eventos da Dead-Letter Queue (DLQ)...", 'yellow');

        if (!in_array('--all', $args)) {
            $this->output("Por segurança, você deve especificar a flag --all para reprocessar todos os eventos.", 'red');
            $this->output("Exemplo: php bin/chronicles dlq:replay --all");
            return 1;
        }
        
        $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
        $dlqSize = $queue->getDlqSize();

        if ($dlqSize === 0) {
            $this->output("A DLQ já está vazia.", 'green');
            return 0;
        }
        
        $confirmation = readline("Encontrados {$dlqSize} eventos na DLQ. Deseja movê-los para a fila principal para reprocessamento? [s/N]: ");
        if (strtolower($confirmation) !== 's') {
            $this->output("Ação cancelada.", 'green');
            return 0;
        }

        $replayedCount = 0;
        // Precisamos usar um pop do Redis que move de uma lista para outra atomicamente (BRPOPLPUSH)
        // para máxima segurança. Como nossa interface é simples, vamos simular com rpop e lpush.
        // Em uma implementação real, o driver RedisQueue teria um método `replay` otimizado.

        $this->output("Iniciando o reprocessamento...");
        
        // Pega todos os itens da DLQ de uma vez
        $itemsToReplay = $queue->inspectDlq($dlqSize);

        foreach ($itemsToReplay as $payload) {
            // Remove da DLQ
            if ($queue->removeFromDlq($payload) > 0) {
                 // Adiciona na fila principal
                $queue->push($payload);
                $replayedCount++;
                echo "."; // Indicador de progresso
            }
        }
        
        echo "\n"; // Nova linha após o progresso
        $this->output("{$replayedCount} de {$dlqSize} evento(s) foram movidos para a fila principal.", 'green');

        return 0;
    }
}