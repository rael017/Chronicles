<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class InstallCommand extends BaseCommand
{
    protected string $description = "Publica o arquivo de configuração e as migrações do Chronicles.";

    public function execute(array $args): int
    {
        $this->output("Publicando recursos do Chronicles...", 'yellow');

        // Pega a configuração para passá-la aos métodos de publicação
        $config = Dispatcher::getConfig();

        // 1. Publicar o arquivo de configuração
        $this->publishConfig($config);

        // 2. Publicar o arquivo de migração SQL
        $this->publishMigration($config);

        $this->output("\nChronicles instalado com sucesso!", 'green');
        $this->output("1. Edite o arquivo 'config/chronicles.php' se necessário.", 'white');
        $this->output("2. Adicione suas credenciais de DB/Redis ao arquivo '.env'.", 'white');
        $this->output("3. Execute 'php ./vendor/bin/chronicles db:setup' para preparar o banco de dados.", 'white');

        return 0;
    }

    private function publishConfig(array $config): void
    {
        // Lê o caminho do config ou usa um padrão
        $destinationDir = $config['paths']['config'] ?? getcwd() . '/config';
        $destinationFile = $destinationDir . '/chronicles.php';

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (file_exists($destinationFile)) {
            $this->output("  - Arquivo de configuração já existe. Pulando.", 'yellow');
            return;
        }

        $source = realpath(__DIR__ . '/../../../config/chronicles.php');
        copy($source, $destinationFile);
        $this->output("  - Configuração publicada em: " . str_replace(getcwd() . '/', '', $destinationFile), 'green');
    }

    private function publishMigration(array $config): void
    {
        // LÊ O CAMINHO DO ARQUIVO DE CONFIGURAÇÃO!
        // Se a chave não existir, ele usa um caminho padrão para não quebrar.
        $defaultPath = getcwd() . '/database/migrations';
        $destinationDir = $config['paths']['migrations'] ?? $defaultPath;
        
        $source = realpath(__DIR__ . '/../../../database/migrations/202406_create_chronicles_tables.sql');
        $destinationFile = $destinationDir . '/' . date('Y_m_d_His') . '_create_chronicles_tables.sql';

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        
        copy($source, $destinationFile);
        $this->output("  - Migração publicada em: " . str_replace(getcwd() . '/', '', $destinationFile), 'green');
    }
}