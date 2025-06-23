<?php

namespace Horus\Chronicles\Workers;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use Horus\Chronicles\Factories\EventFactory;
use Throwable;

/**
 * Class Worker
 *
 * O processo de background que consome eventos da fila e os envia para o armazenamento.
 * Projetado para ser executado como um script de longa duração.
 */
class Worker
{
    private bool $shouldStop = false;

    public function __construct(
        private QueueInterface $queue,
        private StorageInterface $storage,
        private EventFactory $eventFactory
    ) {}

    /**
     * Inicia o loop principal do worker.
     */
    public function run(): void
    {
        $this->registerSignalHandlers();
        $this->log('Worker iniciado. Fila: ' . $this->queue->getQueueName());

        while (!$this->shouldStop) {
            $payload = null;
            try {
                // Bloqueia ou espera por um evento
                $payload = $this->queue->pop();

                if ($payload) {
                    $this->processPayload($payload);
                } else {
                    // Pausa para não consumir 100% de CPU se a fila estiver vazia
                    sleep(1);
                }
            } catch (Throwable $e) {
                $this->log('ERRO CRÍTICO NO WORKER: ' . $e->getMessage() . '. Reiniciando em 5 segundos...');
                // Se o payload foi pego, move para a DLQ para não perdê-lo
                if ($payload) {
                    $this->log('Movendo payload problemático para a DLQ.');
                    $this->queue->moveToDlq($payload);
                }
                sleep(5);
            }

            // Gerenciamento de memória para processos de longa duração
            if (memory_get_usage(true) > 256 * 1024 * 1024) { // 256 MB
                $this->log('Limite de memória atingido. Encerrando para ser reiniciado pelo supervisor.');
                $this->stop();
            }
        }

        $this->log('Worker encerrando...');
    }

    /**
     * Processa um único payload da fila.
     * @param string $payload
     */
    private function processPayload(string $payload): void
    {
        $this->log("Payload recebido. Processando...");
        try {
            $event = $this->eventFactory->make($payload);
            $this->log("Evento '{$event->getType()}' ({$event->getId()}) desserializado.");

            if (!$this->storage->store($event)) {
                throw new \RuntimeException('O driver de armazenamento reportou uma falha na gravação.');
            }

            $this->log("Evento '{$event->getId()}' armazenado com sucesso.");
        } catch (Throwable $e) {
            $this->log("ERRO AO PROCESSAR PAYLOAD: " . $e->getMessage() . ". Movendo para a DLQ.");
            $this->queue->moveToDlq($payload);
        }
    }

    /**
     * Registra handlers para sinais do sistema para um encerramento gracioso.
     */
    private function registerSignalHandlers(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, [$this, 'stop']);  // Ctrl+C
        pcntl_signal(SIGTERM, [$this, 'stop']); // Comando `kill`
    }

    /**
     * Sinaliza para o worker que ele deve parar.
     */
    public function stop(): void
    {
        $this->log('Sinal de encerramento recebido.');
        $this->shouldStop = true;
    }

    /**
     * Loga uma mensagem com o timestamp.
     * @param string $message
     */
    private function log(string $message): void
    {
        echo sprintf("[%s] %s" . PHP_EOL, date('Y-m-d H:i:s'), $message);
    }
}