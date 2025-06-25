<?php

namespace Horus\Chronicles\Core;

use Horus\Chronicles\Events\ExceptionEvent;
use Throwable;

/**
 * Classe ChronicleBootstrap
 *
 * Ponto de entrada único e automatizado para inicializar o sistema Chronicles
 * em qualquer aplicação PHP, com ou sem um contêiner de DI.
 */
final class ChronicleBootstrap
{
    /**
     * Inicializa o sistema Chronicles e registra os watchers globais.
     *
     * Esta é a única função que o usuário final precisa chamar no bootstrap de sua aplicação.
     *
     * @param string $configPath O caminho absoluto para o arquivo de configuração `chronicles.php`.
     * @return void
     */
    public static function boot(string $configPath): void
    {
        // 1. Inicializa o núcleo do Chronicles, que carrega a configuração
        //    e prepara o Dispatcher para criar os drivers necessários.
        Chronicles::init($configPath);

        // 2. Se o Chronicles estiver habilitado, registra os watchers automáticos.
        if (Chronicles::isEnabled()) {
            self::bootWatchers();
        }
    }

    /**
     * Registra watchers globais que não dependem de um framework específico.
     *
     * Aqui é o lugar perfeito para adicionar a captura de exceções, pois
     * ela pode ser configurada usando funções nativas do PHP.
     *
     * @return void
     */

    private static function bootWatchers(): void
    {
        // Watcher de Exceções
        if (Chronicles::isWatcherEnabled('exceptions')) {
            set_exception_handler(function (Throwable $e) {
                // Despacha o evento de exceção para o Chronicles
                Chronicles::dispatch(ExceptionEvent::fromThrowable($e));

                // IMPORTANTE: Mantenha a lógica de erro original da sua aplicação aqui
                // para que o usuário final veja uma página de erro amigável.
                // Este é apenas um exemplo.

                error_log($e->getMessage() . ' na linha ' . $e->getLine() . ' de ' . $e->getFile());
                http_response_code(500);
                echo "<h1>Ocorreu um Erro Inesperado</h1>";
                echo "<p>Nossa equipe foi notificada.</p>";
            });
        }
        
    
    
    }
}