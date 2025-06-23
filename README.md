Chronicles - Sistema de Observabilidade para PHP
Chronicles é um sistema modular, assíncrono e robusto de rastreamento, auditoria e logging para aplicações PHP modernas. Inspirado em ferramentas de nível empresarial, ele foi projetado para ter impacto mínimo na performance da sua aplicação principal, garantindo ao mesmo tempo que nenhum dado de auditoria seja perdido.

Ele captura eventos importantes da sua aplicação (requisições HTTP, queries SQL, exceções, eventos customizados), os enfileira de forma assíncrona e os persiste em um armazenamento de sua escolha, pronto para análise, depuração e auditoria.

✨ Características Principais
⚡ Processamento Assíncrono: Eventos são enviados para uma fila (Redis) e processados em segundo plano por um worker, não bloqueando a requisição do usuário e garantindo uma resposta rápida da sua API.
🛡️ Resiliente e Tolerante a Falhas: Possui uma Dead-Letter Queue (DLQ) nativa. Se a persistência de um evento falhar (ex: banco de dados offline), ele não é perdido, mas movido para uma fila de erros para inspeção e reprocessamento.
🔒 Seguro por Padrão: Sanitiza automaticamente dados sensíveis (senhas, tokens, cabeçalhos de autorização) e limita o tamanho de payloads para prevenir o armazenamento de dados excessivos e proteger a privacidade.
🧩 Totalmente Desacoplado: Baseado em interfaces (Contracts), pode ser integrado a qualquer framework (Laravel, Symfony, Slim, etc.) ou aplicação PHP pura ("vanilla").
💾 Armazenamento Flexível: Suporte nativo para múltiplos drivers de armazenamento: MySQL, Redis, File (arquivo de log) e Null (para testes).
️ Controlável via CLI: Inclui uma poderosa ferramenta de linha de comando (bin/chronicles) para gerenciar filas, inspecionar erros, verificar o status do sistema e muito mais.
🔧 Extensível: Crie facilmente seus próprios tipos de eventos, drivers de armazenamento ou de fila, implementando as interfaces correspondentes.
🏛️ Arquitetura e Fluxo de Dados
O fluxo de um evento no Chronicles é simples e robusto, garantindo a separação entre a captura e o processamento.

 Aplicação PHP                    Fila (Redis)                    Armazenamento (MySQL, etc.)
┌──────────────────┐             ┌────────────────┐              ┌────────────────────────┐
│                  │             │                │              │                        │
│   Evento ocorre  ├────────────►│  Chronicles    ├─────────────►│         Worker         ├───────────► Persistência
│ (HTTP, SQL, etc) │  (rápido)    │      Queue     │  (assíncrono) │                        │   (lento)
│                  │             │                │              │                        │
└──────────────────┘             └────────────────┘              └────────────────────────┘
🚀 Instalação
A instalação do Chronicles é feita via Composer.

Bash

composer require horus/chronicles
(Nota: horus/chronicles é um nome de pacote hipotético. Ajuste seu composer.json para usar o autoload PSR-4 configurado no projeto.)

⚙️ Configuração
Toda a configuração do Chronicles é feita através de um arquivo principal e variáveis de ambiente.

Variáveis de Ambiente: Copie o arquivo .env.example para .env e ajuste as credenciais do seu banco de dados e do Redis. Este é o método recomendado para configurar ambientes de produção.

Arquivo de Configuração Principal: O arquivo config/chronicles.php contém todas as opções de configuração do sistema. Você pode publicá-lo no diretório de configuração do seu projeto.

Chaves de Configuração Importantes
Chave	Descrição	Valores Padrão
enabled	Habilita ou desabilita globalmente a captura de eventos.	true
queue_driver	Define o driver da fila. redis para produção, sync para dev/testes.	redis
storage_driver	Define o driver de armazenamento final.	mysql
watchers	Array para habilitar/desabilitar watchers específicos (http, sql, etc.).	true para todos
sanitizer.mask	Array de chaves de dados que serão mascaradas (ex: password).	['password', 'token', ...]
payload_limiter.max_kb_size	Tamanho máximo (em KB) para um payload antes de ser truncado.	64
queue.redis.dlq_name	Nome da fila de erros (Dead-Letter Queue).	chronicles:dlq

Exportar para as Planilhas
🏁 Guia Rápido (5 Minutos)
Vamos registrar seu primeiro evento em um script simples.

PHP

// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use Horus\Chronicles\Core\Chronicles;
use Horus\Chronicles\Events\CustomEvent;

// 1. Inicialize o Chronicles com o caminho para sua configuração
Chronicles::init(__DIR__ . '/../config/chronicles.php');

// 2. Verifique se o watcher está habilitado e despache um evento
if (Chronicles::isWatcherEnabled('custom')) {
    echo "Chronicles habilitado. Despachando evento...\n";
    Chronicles::dispatch(
        new CustomEvent(
            'user.registered',
            ['user_id' => 123, 'email' => 'user@example.com', 'plan' => 'premium'],
            'INFO'
        )
    );
    echo "Evento despachado para a fila!\n";
} else {
    echo "Chronicles está desabilitado.\n";
}
Agora, em outro terminal, inicie o worker para processar o evento.

Bash

php workers/worker.php
Você deverá ver o worker processar o evento e, se estiver usando o MySQLStorage, uma nova entrada aparecerá na tabela chronicles_custom_events.

usage Como Utilizar
Capturando Eventos
Requisições HTTP (via Middleware)
A forma mais fácil de capturar eventos HTTP é usando o middleware PSR-15 incluso. Em um framework compatível:

PHP

// Adicione o middleware à sua pilha de execução
$app->add(new \Horus\Chronicles\Middleware\HttpLoggerMiddleware());
Queries de Banco de Dados
Você precisa envolver suas chamadas de banco de dados para capturar a query, os parâmetros e a duração.

PHP

use Horus\Chronicles\Events\SqlEvent;
use Horus\Chronicles\Core\Chronicles;

// ...
try {
    $start = microtime(true);
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $duration = (microtime(true) - $start) * 1000;

    if (Chronicles::isWatcherEnabled('sql')) {
        Chronicles::dispatch(new SqlEvent($query, $params, $duration, 'mysql-main'));
    }
} catch (\PDOException $e) {
    $duration = (microtime(true) - $start) * 1000;
    if (Chronicles::isWatcherEnabled('sql')) {
        Chronicles::dispatch(new SqlEvent($query, $params, $duration, 'mysql-main', $e->getMessage()));
    }
    throw $e;
}
Nota: Em frameworks como o Laravel, isso pode ser feito de forma muito mais limpa usando listeners de eventos de banco de dados (DB::listen).

Exceções
Integre com o handler de exceções global da sua aplicação.

PHP

use Horus\Chronicles\Events\ExceptionEvent;
use Horus\Chronicles\Core\Chronicles;

set_exception_handler(function (Throwable $e) {
    if (Chronicles::isWatcherEnabled('exceptions')) {
        Chronicles::dispatch(ExceptionEvent::fromThrowable($e));
    }
    
    // Continue com seu tratamento de exceção normal...
    http_response_code(500);
    echo "Ocorreu um erro inesperado.";
});
👷 O Worker
O worker (workers/worker.php) é um processo de longa duração que consome a fila do Chronicles. Em produção, é essencial que ele seja gerenciado por um supervisor de processos como o Supervisor.

Para iniciar em desenvolvimento:

Bash

php workers/worker.php
Configuração de Produção com Supervisor:
Crie um arquivo de configuração em /etc/supervisor/conf.d/chronicles-worker.conf:

Ini, TOML

[program:chronicles-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/para/seu/projeto/workers/worker.php
autostart=true
autorestart=true
user=www-data
numprocs=4 ; Aumente para maior poder de processamento
redirect_stderr=true
stdout_logfile=/caminho/para/seu/projeto/storage/logs/worker.log
stopwaitsecs=3600
Depois, recarregue a configuração do Supervisor: sudo supervisorctl reread e sudo supervisorctl update.

💻 Linha de Comando (CLI)
O Chronicles vem com uma CLI poderosa (bin/chronicles) para gerenciamento e monitoramento.

Comando	Descrição
status	Exibe o status atual das filas (principal e DLQ) e a configuração dos drivers.
queue:flush	(CUIDADO) Limpa todos os eventos da fila principal. Útil em emergências.
dlq:inspect	Lista os eventos que estão na fila de erros (DLQ) para análise.
dlq:replay	Tenta reprocessar os eventos da DLQ, movendo-os de volta para a fila principal.
storage:clear	(CUIDADO) Limpa todos os eventos do armazenamento final (banco de dados, etc.).

Exportar para as Planilhas
Exemplos de Uso:

Bash

# Verificar a saúde do sistema
php bin/chronicles status

# Ver o que está na fila de erros
php bin/chronicles dlq:inspect

# Tentar reprocessar todos os eventos com erro
php bin/chronicles dlq:replay --all
🧩 Integração com Frameworks
O Chronicles foi projetado para ser integrado. A melhor abordagem é criar um ServiceProvider (ou equivalente) no seu framework para:

Registrar os serviços do Chronicles no contêiner de DI do framework.
Usar os listeners nativos do framework para capturar eventos (ex: DB::listen no Laravel).
Criar comandos "wrapper" que usem a CLI nativa do framework (ex: php artisan chronicles:status).
Consulte docs/integration.md para guias detalhados.

🧪 Testes
Para executar a suíte de testes unitários, use o script do Composer:

Bash

composer test
🤝 Contribuições
Contribuições são bem-vindas! Por favor, sinta-se à vontade para abrir uma issue para relatar um bug ou propor uma nova funcionalidade. Pull requests são ainda melhores!

📜 Licença
O Chronicles é um software de código aberto licenciado sob a Licença MIT.