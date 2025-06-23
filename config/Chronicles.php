<?php

/**
 * Arquivo de Configuração Principal do Chronicles.
 *
 * Este arquivo define a configuração padrão e a estrutura para o sistema Chronicles.
 * Os valores aqui definidos são projetados para serem sobrescritos por variáveis de ambiente
 * (carregadas de um arquivo .env na raiz do seu projeto), que é a prática recomendada
 * para manter dados sensíveis (como senhas) fora do controle de versão.
 *
 * Precedência de Configuração: Variável de Ambiente (.env) > Valor neste arquivo.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Configurações Gerais
    |--------------------------------------------------------------------------
    |
    | Controles globais para o comportamento do Chronicles.
    |
    */

    // Habilita ou desabilita completamente a captura de eventos. Se false, o Chronicles
    // terá um impacto de performance quase nulo na sua aplicação.
    'enabled' => (bool) (getenv('CHRONICLES_ENABLED') ?? true),

    // Define o driver da fila.
    // 'sync':  Processa eventos imediatamente. Ótimo para desenvolvimento e testes. Não requer worker.
    // 'redis': Enfileira eventos no Redis para processamento assíncrono. Requer um worker rodando.
    'queue_driver' => getenv('CHRONICLES_QUEUE_DRIVER') ?? 'sync',

    // Define onde os eventos serão permanentemente armazenados após o processamento.
    // 'file':  Salva em um arquivo de log. Simples e sem dependências.
    // 'mysql': Salva em um banco de dados MySQL para consultas estruturadas.
    // 'redis': Salva em uma lista do Redis (diferente da fila). Rápido, mas volátil.
    // 'null':  Descarta todos os eventos. Útil para depurar a performance da captura.
    'storage_driver' => getenv('CHRONICLES_STORAGE_DRIVER') ?? 'file',


    /*
    |--------------------------------------------------------------------------
    | Configurações de Conexão
    |--------------------------------------------------------------------------
    |
    | Defina aqui as credenciais e os endpoints para os serviços externos
    | que o Chronicles pode usar, como banco de dados e Redis.
    |
    | Coloque os valores reais no seu arquivo .env.
    |
    */

    'connections' => [

        'mysql' => [
            'host'     => getenv('DB_HOST') ?? '127.0.0.1',
            'port'     => getenv('DB_PORT') ?? '3306',
            'database' => 'chronicles', 
            'username' => getenv('DB_USER') ?? 'root',          // <-- PREENCHA EM .env
            'password' => getenv('DB_PASS') ?? '',              // <-- PREENCHA EM .env
            'charset'  => 'utf8mb4',
            'options'  => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ],

        'redis' => [
            'host'     => getenv('REDIS_HOST') ?? '127.0.0.1',
            'port'     => getenv('REDIS_PORT') ?? 6379,
            'password' => getenv('REDIS_PASSWORD') ?? null,         // <-- PREENCHA EM .env (se houver)
            'database' => getenv('REDIS_DATABASE') ?? 0,
            'timeout'  => 1.0,
        ],

        'file' => [
            // Caminho padrão para o log de eventos quando o driver 'file' é usado.
            'events_path' => __DIR__ . '/../storage/logs/chronicles_events.log',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações da Fila
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'redis' => [
            // Nome da chave no Redis para a fila principal de eventos.
            'queue_name' => 'chronicles:queue',
            // Nome da chave no Redis para a fila de eventos que falharam (Dead-Letter Queue).
            'dlq_name' => 'chronicles:dlq',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Watchers de Eventos
    |--------------------------------------------------------------------------
    |
    | Habilite ou desabilite a captura de tipos específicos de eventos.
    |
    */

    'watchers' => [
        'http'       => true, // Captura requisições HTTP via middleware.
        'sql'        => true, // Captura queries de banco de dados.
        'exceptions' => true, // Captura exceções não tratadas.
        'custom'     => true, // Permite o envio de eventos customizados.
    ],

    /*
    |--------------------------------------------------------------------------
    | Segurança e Performance
    |--------------------------------------------------------------------------
    |
    | Configurações para sanitização de dados e limitação de payloads.
    |
    */

    'security' => [
        // Sanitização de dados sensíveis.
        'sanitizer' => [
            // Os valores para estas chaves (em qualquer nível de um array)
            // serão substituídos por '********'.
            'mask' => [
                'password', 'password_confirmation', 'token', 'secret',
                'authorization', 'x-api-key', 'x-secret-key', 'credit_card', 'cvv',
            ],
        ],

        // Limitação do tamanho dos dados para evitar sobrecarga.
        'payload_limiter' => [
            // Tamanho máximo em kilobytes (KB) para um valor de string antes
            // de ser truncado.
            'max_kb_size' => 64,
        ],
    ],
];