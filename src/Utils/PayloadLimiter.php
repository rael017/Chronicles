<?php

namespace Horus\Chronicles\Utils;

/**
 * Class PayloadLimiter
 *
 * Responsável por limitar o tamanho dos dados para evitar sobrecarga no armazenamento e na fila.
 * Trabalha em conjunto com o Sanitizer.
 */
final class PayloadLimiter
{
    private int $maxBytes;
    private string $truncationIndicator;

    /**
     * @param array $config A seção 'payload_limiter' do arquivo de configuração.
     */
    public function __construct(array $config)
    {
        $maxKb = $config['max_kb_size'] ?? 64;
        $this->maxBytes = $maxKb * 1024;
        $this->truncationIndicator = '...[TRUNCATED]';
    }

    /**
     * Percorre um array recursivamente e trunca strings que excedam o tamanho máximo.
     *
     * @param array $data O array de dados a ser limitado.
     * @return array O array com valores truncados.
     */
    public function limit(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (is_string($value)) {
                if (strlen($value) > $this->maxBytes) {
                    $value = substr($value, 0, $this->maxBytes) . $this->truncationIndicator;
                }
            } elseif (is_array($value)) {
                $value = $this->limit($value);
            }
        }
        return $data;
    }
}