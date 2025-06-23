<?php

namespace Horus\Chronicles\Core;

use Horus\Chronicles\Contracts\EventInterface;

/**
 * Class Chronicles
 *
 * Fachada Estática (Static Facade) e ponto de entrada principal para o sistema.
 * A aplicação interage com o Chronicles principalmente através desta classe.
 */
final class Chronicles
{
    private static bool $isInitialized = false;

    /**
     * Inicializa o sistema Chronicles, carregando a configuração.
     * Deve ser chamado uma vez no bootstrap da aplicação.
     *
     * @param string $configPath O caminho absoluto para o arquivo de configuração.
     * @return void
     */
    public static function init(string $configPath): void
    {
        if (self::$isInitialized) {
            return;
        }

        Dispatcher::bootstrap($configPath);
        
        self::$isInitialized = true;
    }

    /**
     * Despacha um evento para ser processado.
     * O evento será serializado e enviado para a fila configurada.
     *
     * @param EventInterface $event O objeto de evento a ser despachado.
     * @return void
     */
    public static function dispatch(EventInterface $event): void
    {
        if (!self::isEnabled()) {
            return;
        }
        
        $queueDriver = Dispatcher::getConfig('queue_driver');
        $queue = Dispatcher::getQueueFactory()->make($queueDriver);
        
        $queue->push($event->toJsonPayload());
    }
    
    /**
     * Verifica se o Chronicles está habilitado na configuração.
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return self::$isInitialized && Dispatcher::getConfig('enabled', false) === true;
    }

    /**
     * Verifica se um watcher específico está habilitado na configuração.
     *
     * @param string $watcherName O nome do watcher (ex: 'http', 'sql').
     * @return bool
     */
    public static function isWatcherEnabled(string $watcherName): bool
    {
        if (!self::isEnabled()) {
            return false;
        }
        
        $watchers = Dispatcher::getConfig('watchers', []);
        return $watchers[$watcherName] ?? false;
    }

    /**
     * Encerra as conexões do Dispatcher. Útil no final de scripts de longa duração.
     * @return void
     */
    public static function terminate(): void
    {
        if (self::$isInitialized) {
            Dispatcher::terminate();
            self::$isInitialized = false;
        }
    }
}