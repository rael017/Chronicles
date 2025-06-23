<?php

namespace Horus\Chronicles\Core;

use RuntimeException;

/**
 * Class ConfigLoader
 *
 * Classe dedicada a carregar e validar o arquivo de configuração principal.
 */
final class ConfigLoader
{
    /**
     * Carrega o arquivo de configuração.
     *
     * @param string $path O caminho para o arquivo config/chronicles.php.
     * @return array
     * @throws RuntimeException Se o arquivo não for encontrado ou não retornar um array.
     */
    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Arquivo de configuração do Chronicles não encontrado em: {$path}");
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new RuntimeException("Arquivo de configuração do Chronicles deve retornar um array.");
        }

        return $config;
    }
}