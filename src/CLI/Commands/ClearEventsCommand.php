<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Core\Dispatcher;

class ClearEventsCommand extends BaseCommand
{
    protected string $description = "Limpa os eventos do armazenamento final. Use --type=<tipo> para um tipo específico.";

    public function execute(array $args): int
    {
        $this->output("AVISO: Esta ação removerá permanentemente os eventos armazenados.", 'yellow');
        
        $type = $this->parseArgument($args, 'type');
        $target = $type ? "eventos do tipo '{$type}'" : "TODOS os eventos";

        $confirmation = readline("Você tem certeza que deseja limpar {$target}? [s/N]: ");

        if (strtolower(trim($confirmation)) !== 's') {
            $this->output("Ação cancelada.", 'green');
            return 0;
        }

        try {
            $storage = Dispatcher::getStorageFactory()->make(Dispatcher::getConfig('storage_driver'));

            $this->output("Limpando {$target} do armazenamento...");

            if ($storage->clear($type)) {
                $this->output("Armazenamento limpo com sucesso.", 'green');
            } else {
                $this->output("Ocorreu um erro ao limpar o armazenamento (o driver pode ter reportado uma falha).", 'red');
                return 1;
            }
        } catch (\Exception $e) {
            $this->output("Ocorreu um erro ao tentar limpar o armazenamento: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Extrai um argumento no formato --nome=valor de um array.
     * @param array $args
     * @param string $name
     * @return string|null
     */
    private function parseArgument(array $args, string $name): ?string
    {
        foreach ($args as $arg) {
            if (preg_match("/^--{$name}=(.*)$/", $arg, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}