<?php

namespace Horus\Chronicles\Storage;

use Horus\Chronicles\Contracts\EventInterface;
use Horus\Chronicles\Contracts\StorageInterface;
use Redis;
use RedisException;

class RedisStorage implements StorageInterface
{
    private const STORAGE_PREFIX = 'chronicles:storage:';

    public function __construct(private Redis $redis) {}

    public function store(EventInterface $event): bool
    {
        $key = self::STORAGE_PREFIX . $event->getType();
        $payload = json_encode($event->toArray());

        try {
            $this->redis->lPush($key, $payload);
            return true;
        } catch (RedisException $e) {
            error_log("Chronicles Redis Storage Error: " . $e->getMessage());
            return false;
        }
    }

    public function clear(?string $eventType = null): bool
    {
        $keysToDelete = [];
        if ($eventType) {
            $keysToDelete[] = self::STORAGE_PREFIX . $eventType;
        } else {
            $iterator = null;
            // Busca por todas as chaves de armazenamento do Chronicles para limpar
            while ($keys = $this->redis->scan($iterator, self::STORAGE_PREFIX . '*')) {
                $keysToDelete = array_merge($keysToDelete, $keys);
            }
        }

        try {
            if (!empty($keysToDelete)) {
                $this->redis->del($keysToDelete);
            }
            return true;
        } catch (RedisException $e) {
            error_log("Chronicles Redis Storage Clear Error: " . $e->getMessage());
            return false;
        }
    }
}