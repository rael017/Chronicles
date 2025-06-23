<?php

namespace Horus\Chronicles\Storage;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use PDO;
use PDOException;

/**
 * Class MySQLStorage
 *
 * Armazena eventos em um banco de dados MySQL/MariaDB.
 * Cria a query de INSERT dinamicamente com base nos dados do evento.
 */
class MySQLStorage implements StorageInterface
{
    public function __construct(private PDO $pdo) {}

    public function store(EventInterface $event): bool
    {
        $table = 'chronicles_' . $event->getType() . '_events';
        $data = $event->getContext();
        
        // Adiciona os campos base que estão na tabela mas não no contexto
        $data['id'] = $event->getId();
        $data['timestamp'] = $event->getTimestamp();

        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => "`$c`", $columns)),
            implode(', ', $placeholders)
        );

        try {
            $stmt = $this->pdo->prepare($sql);
            // Converte arrays/objetos para JSON antes de salvar
            foreach ($data as $key => &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $stmt->execute($data);
            return true;
        } catch (PDOException $e) {
            error_log("Chronicles MySQL Storage Error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    public function clear(?string $eventType = null): bool
    {
        $tables = ['http', 'sql', 'exception', 'custom'];
        if ($eventType) {
            $tables = in_array($eventType, $tables) ? [$eventType] : [];
        }

        try {
            foreach ($tables as $type) {
                $tableName = 'chronicles_' . $type . '_events';
                $this->pdo->exec("TRUNCATE TABLE `{$tableName}`");
            }
            return true;
        } catch (PDOException $e) {
            error_log("Chronicles MySQL Storage Clear Error: " . $e->getMessage());
            return false;
        }
    }
}