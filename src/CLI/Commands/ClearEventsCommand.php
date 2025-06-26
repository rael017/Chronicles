<?php

namespace Horus\Chronicles\CLI\Commands;

use Horus\Chronicles\Actions\ClearStorageAction;
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

        // Pega a dependência (StorageInterface)
        $storage = Dispatcher::getStorageFactory()->make(Dispatcher::getConfig('storage_driver'));

        // Cria e executa a Action
        $action = new ClearStorageAction($storage);
        
        $this->output("Limpando {$target} do armazenamento...");

        if ($action->execute($type)) {
            $this->output("Armazenamento limpo com sucesso.", 'green');
        } else {
            $this->output("Ocorreu um erro ao limpar o armazenamento (o driver de armazenamento reportou uma falha).");
            return 1;
        }

        return 0;
    }

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