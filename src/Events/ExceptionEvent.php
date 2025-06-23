<?php

namespace Horus\Chronicles\Events;

use Horus\Chronicles\Core\Dispatcher;
use Throwable;

class ExceptionEvent extends BaseEvent
{
    public const TYPE = 'exception';

    public function __construct(
        public string $class,
        public string $message,
        public string $code,
        public string $file,
        public int $line,
        public array $trace
    ) {
        parent::__construct();
    }

    /**
     * Método de conveniência para criar um evento a partir de um objeto Throwable.
     * @param Throwable $e
     * @return static
     */
    public static function fromThrowable(Throwable $e): self
    {
        return new self(
            get_class($e),
            $e->getMessage(),
            $e->getCode(),
            $e->getFile(),
            $e->getLine(),
            $e->getTrace()
        );
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getContext(): array
    {
        $limiter = Dispatcher::getPayloadLimiter();

        return [
            'class' => $this->class,
            'message' => $this->message,
            'code' => $this->code,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $limiter->limit($this->trace), // Apenas limita o tamanho do trace
        ];
    }
}