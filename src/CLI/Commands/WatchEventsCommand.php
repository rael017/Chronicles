<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class WatchEventsCommand extends BaseCommand
{
    protected string $description = "Observa e exibe novos eventos do Chronicles em tempo real.";

    public function execute(array $args): int
    {
        $this->output("Iniciando modo de observação... (Pressione Ctrl+C para parar)", 'yellow');
        
        $storage = Dispatcher::getStorageFactory()->make(Dispatcher::getConfig('storage_driver'));

        // Esta implementação é um exemplo para o MySQLStorage.
        if (!$storage instanceof \Horus\Chronicles\Storage\MySQLStorage) {
            $this->output("O modo 'watch' atualmente só é suportado para o driver 'mysql'.");
            return 1;
        }

        $pdo = Dispatcher::getDbConnection();
        $lastIds = [];
        $eventTypes = ['http', 'sql', 'exception', 'custom'];

        while (true) {
            foreach ($eventTypes as $type) {
                $tableName = "chronicles_{$type}_events";
                $lastId = $lastIds[$type] ?? 0;

                $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id > :last_id ORDER BY id ASC LIMIT 100");
                $stmt->execute(['last_id' => $lastId]);
                
                $events = $stmt->fetchAll();

                if (!empty($events)) {
                    foreach ($events as $event) {
                        $this->printEvent($type, $event);
                        $lastIds[$type] = $event['id']; // Atualiza o último ID visto para este tipo
                    }
                }
            }
            sleep(2); // Espera 2 segundos antes de verificar novamente
        }

        return 0;
    }

    private function printEvent(string $type, array $event): void
    {
        $color = match ($type) {
            'http' => 'green',
            'sql' => 'cyan',
            'exception' => 'red',
            'custom' => 'yellow',
            default => 'white',
        };

        $timestamp = date('Y-m-d H:i:s', $event['timestamp']);
        $this->output("[$timestamp] Novo evento '{$type}' (ID: {$event['id']})", $color);

        // Imprime alguns detalhes do contexto
        $context = $event; // No nosso caso, o evento já é um array plano
        unset($context['id'], $context['timestamp']); // Remove campos já exibidos
        
        foreach ($context as $key => $value) {
            if (!empty($value)) {
                 $this->output(sprintf("  - %s: %s", $key, substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '')));
            }
        }
        $this->output(str_repeat('-', 40));
    }
}