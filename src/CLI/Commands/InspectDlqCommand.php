<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class InspectDlqCommand extends BaseCommand
{
    protected string $description = "Inspeciona os eventos na fila de erros (DLQ). Use --limit=<N> para ver mais.";

    public function execute(array $args): int
    {
        $this->output("Inspecionando a Dead-Letter Queue (DLQ)...", 'yellow');
        
        $queue = Dispatcher::getQueueFactory()->make(Dispatcher::getConfig('queue_driver'));
        
        $dlqSize = $queue->getDlqSize();

        if ($dlqSize === 0) {
            $this->output("A DLQ está vazia. Nenhum evento com erro encontrado.", 'green');
            return 0;
        }

        $limit = (int) ($this->parseArgument($args, 'limit') ?? 20);
        $this->output("Encontrado(s) {$dlqSize} evento(s) na DLQ. Exibindo os últimos {$limit}:", 'yellow');
        $this->output(str_repeat('-', 80));

        $events = $queue->inspectDlq($limit);

        foreach ($events as $index => $payload) {
            $data = json_decode($payload, true);
            $id = $data['data']['id'] ?? 'N/A';
            $type = $data['data']['type'] ?? 'N/A';
            $class = $data['class'] ?? 'N/A';
            
            $this->output(sprintf("#%d | ID: %s | Tipo: %s", $index + 1, $id, $type), 'cyan');
            $this->output("    Classe: {$class}");
            $this->output("    Payload (resumido): " . substr(preg_replace('/\s+/', ' ', $payload), 0, 150) . "...");
            $this->output(str_repeat('-', 80));
        }

        $this->output("\nUse `php bin/chronicles dlq:replay --all` para tentar reprocessar estes eventos.", 'green');

        return 0;
    }

    private function parseArgument(array $args, string $name): ?string
    {
        foreach ($args as $arg) {
            if (preg_match("/^--{$name}=(.*)$/", $arg, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}