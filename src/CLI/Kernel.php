<?php

namespace Horus\Chronicles\CLI;

use Horus\Chronicles\CLI\Commands\ClearEventsCommand;
use Horus\Chronicles\CLI\Commands\FlushQueueCommand;
use Horus\Chronicles\CLI\Commands\InspectDlqCommand;
use Horus\Chronicles\CLI\Commands\ReplayDlqCommand;
use Horus\Chronicles\CLI\Commands\StatusCommand;
use Horus\Chronicles\CLI\Commands\WatchEventsCommand;
use Throwable;

class Kernel
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    /**
     * Registra todos os comandos disponíveis na CLI.
     */
    private function registerCommands(): void
    {
        $this->commands = [
            'status' => new StatusCommand(),
            'queue:flush' => new FlushQueueCommand(),
            'dlq:inspect' => new InspectDlqCommand(),
            'dlq:replay' => new ReplayDlqCommand(),
            'storage:clear' => new ClearEventsCommand(),
            'watch'        => new WatchEventsCommand()
        ];
    }

    /**
     * Manipula a entrada da linha de comando e executa o comando correspondente.
     *
     * @param array $argv Os argumentos da linha de comando, incluindo o nome do script.
     * @return int O código de saída do comando (0 para sucesso, >0 para erro).
     */
    public function handle(array $argv): int
    {
        if (count($argv) < 2 || in_array($argv[1], ['--help', '-h', 'help'])) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];
        $args = array_slice($argv, 2);

        if (!isset($this->commands[$commandName])) {
            $this->error("Comando '{$commandName}' não encontrado.");
            $this->showHelp();
            return 1;
        }

        try {
            $command = $this->commands[$commandName];
            return $command->execute($args);
        } catch (Throwable $e) {
            $this->error("ERRO INESPERADO: " . $e->getMessage());
            $this->output("Arquivo: {$e->getFile()}:{$e->getLine()}");
            $this->output("Trace: \n" . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Exibe a tela de ajuda com a lista de todos os comandos registrados.
     */
    private function showHelp(): void
    {
        $this->output("Chronicles CLI - Ferramenta de Gerenciamento", 'yellow');
        $this->output("------------------------------------------------");
        $this->output("Uso: bin/chronicles <comando> [opções]");
        $this->output("");
        $this->output("Comandos disponíveis:", 'green');
        
        $longestCommand = max(array_map('strlen', array_keys($this->commands)));

        foreach ($this->commands as $name => $command) {
            $this->output(sprintf("  %-{$longestCommand}s   %s", $name, $command->getDescription()));
        }
        $this->output("\nUse --help com qualquer comando para mais detalhes (se aplicável).");
    }

    /**
     * Imprime uma mensagem no console com cores.
     *
     * @param string $message A mensagem a ser impressa.
     * @param string $color A cor do texto ('white', 'yellow', 'green', 'red').
     */
    private function output(string $message, string $color = 'white'): void
    {
        $colors = ['white' => '1;37', 'yellow' => '1;33', 'green' => '0;32', 'red' => '0;31', 'cyan' => '0;36'];
        $colorCode = $colors[$color] ?? $colors['white'];
        echo "\033[{$colorCode}m{$message}\033[0m" . PHP_EOL;
    }

    /**
     * Imprime uma mensagem de erro no console.
     *
     * @param string $message A mensagem de erro.
     */
    private function error(string $message): void
    {
        $this->output("ERRO: {$message}", 'red');
    }
}