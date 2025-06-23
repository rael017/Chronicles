<?php

namespace Horus\Chronicles\Contracts;

/**
 * Interface EventInterface
 *
 * Define o contrato para qualquer evento que possa ser rastreado pelo Chronicles.
 * Cada evento deve ter um ID único, um tipo, um timestamp e um contexto de dados.
 */
interface EventInterface
{
    /**
     * Retorna o ID único do evento, geralmente um UUID.
     * @return string
     */
    public function getId(): string;

    /**
     * Retorna o tipo do evento (ex: 'http', 'sql', 'exception').
     * @return string
     */
    public function getType(): string;

    /**
     * Retorna o timestamp UNIX de quando o evento ocorreu.
     * @return int
     */
    public function getTimestamp(): int;

    /**
     * Retorna um array com os metadados e o contexto principal do evento.
     * @return array
     */
    public function getContext(): array;

    /**
     * Serializa o evento para um array que pode ser armazenado.
     * @return array
     */
    public function toArray(): array;

    /**
     * Serializa o objeto do evento para um payload JSON para a fila.
     * @return string
     */
    public function toJsonPayload(): string;

    /**
     * Cria uma instância do evento a partir de um payload de dados.
     * @param array $payload
     * @return static
     */
    public static function fromArray(array $payload): self;
}