<?php

namespace Horus\Chronicles\CLI\Commands;

abstract class BaseCommand
{
    protected string $description = '';

    abstract public function execute(array $args): int;

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function output(string $message, string $color = 'white'): void
    {
        $colors = ['white' => '1;37', 'yellow' => '1;33', 'green' => '0;32', 'red' => '0;31', 'cyan' => '0;36'];
        $colorCode = $colors[$color] ?? $colors['white'];
        echo "\033[{$colorCode}m{$message}\033[0m" . PHP_EOL;
    }
}