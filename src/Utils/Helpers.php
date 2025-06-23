<?php

namespace Horus\Chronicles\Utils;

/**
 * Class Helpers
 *
 * Contém funções utilitárias estáticas usadas em todo o projeto.
 */
final class Helpers
{
    /**
     * Carrega uma variável de ambiente de forma segura, com um valor padrão.
     * Converte 'true', 'false', 'null', e 'empty' para seus tipos correspondentes.
     *
     * @param string $key A chave da variável de ambiente.
     * @param mixed|null $default O valor padrão a ser retornado se a variável não for encontrada.
     * @return mixed
     */
     public static function load($dir){
		if(!file_exists($dir.'/.env')){
			return false;
		}
		
		$lines = file($dir.'/.env');
		foreach($lines as $line){
			putenv(trim($line));
		}
	 }

    /**
     * Garante que um diretório exista, criando-o se necessário.
     *
     * @param string $directory O caminho do diretório.
     * @throws \RuntimeException Se não for possível criar o diretório.
     * @return void
     */
    public static function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
}