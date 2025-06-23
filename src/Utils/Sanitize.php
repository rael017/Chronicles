<?php

namespace Horus\Chronicles\Utils;

/**
 * Class Sanitizer
 *
 * Responsável por "limpar" arrays de dados, mascarando valores de chaves sensíveis.
 * Crucial para a segurança, evitando o log de senhas, tokens, etc.
 */
final class Sanitizer
{
    private array $maskedKeys;
    private string $mask;

    /**
     * @param array $config A seção 'sanitizer' do arquivo de configuração.
     * @param string $mask O caractere ou string a ser usado para mascaramento.
     */
    public function __construct(array $config, string $mask = '********')
    {
        // array_flip para uma busca O(1)
        $this->maskedKeys = array_flip($config['mask'] ?? []);
        $this->mask = $mask;
    }

    /**
     * Percorre um array recursivamente e mascara os valores das chaves configuradas.
     *
     * @param array $data O array de dados a ser sanitizado.
     * @return array O array sanitizado.
     */
    public function sanitize(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (isset($this->maskedKeys[$key])) {
                $value = $this->mask;
                continue;
            }

            if (is_array($value)) {
                $value = $this->sanitize($value);
            }
        }
        return $data;
    }
}