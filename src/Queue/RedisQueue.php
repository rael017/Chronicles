<?php

namespace Horus\Chronicles\Queue;

use Horus\Chronicles\Contracts\QueueInterface;
use Horus\Chronicles\Factories\EventFactory;
use Redis;
use RedisException;

/**
 * Class RedisQueue
 *
 * Implementação de fila assíncrona usando Redis. Utiliza listas para o comportamento FIFO.
 * Esta é a implementação recomendada para produção.
 */
class RedisQueue implements QueueInterface
{
    public function __construct(
        private Redis $redis,
        private EventFactory $eventFactory,
        private string $queueName,
        private string $dlqName
    ) {}

    public function push(string $payload): void
    {
        try {
            $this->redis->lPush($this->queueName, $payload);
        } catch (RedisException $e) {
            // Se o Redis estiver offline, logamos o erro e evitamos que a aplicação principal quebre.
            // É melhor perder um evento de log do que gerar um erro 500 para o usuário.
            error_log('Chronicles Critical Failure: Não foi possível conectar à fila Redis. Evento descartado. Erro: ' . $e->getMessage());
        }
    }

    public function pop(): ?string
    {
        try {
            return $this->redis->rPop($this->queueName) ?: null;
        } catch (RedisException $e) {
            error_log('Chronicles Worker Error: Falha ao tentar remover evento da fila Redis. Erro: ' . $e->getMessage());
            // Pausa para evitar um loop de falhas que consuma CPU
            sleep(5);
            return null;
        }
    }

    public function moveToDlq(string $payload): void
    {
        try {
            $this->redis->lPush($this->dlqName, $payload);
        } catch (RedisException $e) {
            // Se até a DLQ falhar, o último recurso é um log de arquivo
            $fallbackLog = __DIR__ . '/../../storage/logs/chronicles_dlq_failures.log';
            file_put_contents($fallbackLog, date('c') . ' | ' . $payload . PHP_EOL, FILE_APPEND);
            error_log('Chronicles Critical Failure: Falha ao mover evento para a DLQ. Payload salvo em ' . $fallbackLog . '. Erro: ' . $e->getMessage());
        }
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getDlqName(): ?string
    {
        return $this->dlqName;
    }

    public function getQueueSize(): int
    {
        try {
            return $this->redis->lLen($this->queueName) ?: 0;
        } catch (RedisException) {
            return 0;
        }
    }

    public function getDlqSize(): int
    {
        try {
            return $this->redis->lLen($this->dlqName) ?: 0;
        } catch (RedisException) {
            return 0;
        }
    }

    public function flushQueue(): int
    {
        try {
            return $this->redis->del($this->queueName) ?: 0;
        } catch (RedisException) {
            return 0;
        }
    }

    public function inspectDlq(int $limit = 50): array
    {
        try {
            return $this->redis->lRange($this->dlqName, 0, $limit - 1) ?: [];
        } catch (RedisException) {
            return [];
        }
    }
    
    public function removeFromDlq(string $payload): int
    {
        try {
            // Remove todas as ocorrências do payload da DLQ.
            return $this->redis->lRem($this->dlqName, $payload, 0);
        } catch (RedisException) {
            return 0;
        }
    }
}