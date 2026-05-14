<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;
use RuntimeException;
class InstallCommand extends BaseCommand
{
    protected string $description = "Publica o arquivo de configuração e as migrações do Chronicles.";

    public function execute(array $args): int
    {
        $this->output("Publicando recursos do Chronicles...", 'yellow');

        try {
            // Analisa os argumentos para caminhos customizados
            $configPath = $this->parseArgument($args, 'config-path') ?? Dispatcher::getConfig('paths')['config'];
            $migrationsPath = $this->parseArgument($args, 'migrations-path') ?? Dispatcher::getConfig('paths')['migrations'];
            $force = in_array('--force', $args);

            // Executa a publicação
            $this->publishConfig($configPath, $force);
            $this->publishMigration($migrationsPath);

        } catch (RuntimeException $e) {
            $this->output("ERRO: " . $e->getMessage());
            return 1;
        }

        $this->output("\n✅ Chronicles instalado com sucesso!", 'green');
        $this->output("Próximos passos:", 'white');
        $this->output("  1. Edite o '.env' com suas configurações de DB/Redis.");
        $this->output("  2. Rode 'php ./vendor/bin/chronicles db:setup' para preparar o banco de dados.");

        return 0;
    }

    /**
     * Copia o arquivo de configuração para o caminho de destino no projeto principal.
     */
    private function publishConfig(string $destinationRelativePath, bool $force): void
    {
        $sourceFile = __DIR__ . '/../../../config/chronicles.php';
        if (!file_exists($sourceFile)) {
            throw new RuntimeException("Arquivo de configuração de origem não foi encontrado no pacote.");
        }
        
        // Usa getcwd() para obter a raiz do projeto principal de forma confiável
        $projectRoot = getcwd();
        $destinationDir = $projectRoot . '/' . ltrim($destinationRelativePath, '/');
        $destinationFile = $destinationDir . '/chronicles.php';

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (file_exists($destinationFile) && !$force) {
            $this->output("  - Arquivo de configuração já existe. Use --force para sobrescrever. Pulando.", 'yellow');
            return;
        }

        copy($sourceFile, $destinationFile);
        $this->output("  - Configuração publicada em: " . $destinationRelativePath . '/chronicles.php', 'green');
    }

    /**
     * Copia os arquivos de migração para o caminho de destino no projeto principal.
     */
    private function publishMigration(string $destinationRelativePath): void
    {
        $sourceDir = __DIR__ . '/../../../database/migrations';
        if (!is_dir($sourceDir)) {
            throw new RuntimeException("Diretório de migrações de origem não foi encontrado no pacote.");
        }

        $projectRoot = getcwd();
        $destinationDir = $projectRoot . '/' . ltrim($destinationRelativePath, '/');

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $migrationFiles = glob($sourceDir . '/*.sql');
        if (empty($migrationFiles)) {
            $this->output("  - Nenhum arquivo .sql de migração encontrado para publicar.", 'yellow');
            return;
        }
        
        foreach ($migrationFiles as $sourceFile) {
            if (is_file($sourceFile)) {
                $baseName = basename($sourceFile);
                $timestampedDestination = $destinationDir . '/'  . $baseName;
                
                copy($sourceFile, $timestampedDestination);
                $this->output("  - Migração publicada em: " . $destinationRelativePath . basename($timestampedDestination), 'green');
            }
        }
    }

    /**
     * Extrai um argumento no formato --nome=valor de um array.
     */
    private function parseArgument(array $args, string $name): ?string
    {
        foreach ($args as $arg) {
            $pattern = "/^--{$name}=(.*)$/";
            if (preg_match($pattern, $arg, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}