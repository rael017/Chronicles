<?php

namespace Horus\Chronicles\Storage;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use Exception;

class FileStorage implements StorageInterface
{
    public function __construct(private string $logPath) {}

    public function store(EventInterface $event): bool
    {
        $logLine = json_encode($event->toArray()) . PHP_EOL;

        try {
            $fileHandle = fopen($this->logPath, 'a');
            if (!$fileHandle) {
                return false;
            }
            // Garante lock exclusivo para escrita, prevenindo race conditions
            if (flock($fileHandle, LOCK_EX)) {
                fwrite($fileHandle, $logLine);
                flock($fileHandle, LOCK_UN); // Libera o lock
            }
            fclose($fileHandle);
            return true;
        } catch (Exception $e) {
            error_log("Chronicles File Storage Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function clear(?string $eventType = null): bool
    {
        // FileStorage armazena tudo em um só arquivo, então não suporta limpeza por tipo.
        // Ele apenas limpa o arquivo inteiro.
        if ($eventType !== null) {
            // Não suportado, mas retorna true para não causar erro no worker.
            return true;
        }

        try {
            if (file_exists($this->logPath)) {
                return (bool) file_put_contents($this->logPath, '');
            }
            return true;
        } catch (Exception $e) {
            error_log("Chronicles File Storage Clear Error: " . $e->getMessage());
            return false;
        }
    }
}