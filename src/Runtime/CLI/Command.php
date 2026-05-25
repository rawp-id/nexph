<?php
namespace Core\Runtime\CLI;

/**
 * Base command interface.
 */
abstract class Command {
    protected string $name = '';
    protected string $description = '';
    protected array $options = [];
    
    /**
     * Execute command.
     */
    abstract public function execute(array $args = []): int;
    
    /**
     * Get command name.
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * Get command description.
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Get command options.
     */
    public function getOptions(): array {
        return $this->options;
    }
    
    /**
     * Parse command line arguments.
     */
    protected function parseArgs(array $args): array {
        $parsed = [
            'options' => [],
            'arguments' => [],
        ];
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $parsed['options'][$key] = $value;
            } elseif (str_starts_with($arg, '-')) {
                $key = substr($arg, 1);
                $parsed['options'][$key] = true;
            } else {
                $parsed['arguments'][] = $arg;
            }
        }
        
        return $parsed;
    }
    
    /**
     * Print output.
     */
    protected function output(string $message): void {
        echo $message . "\n";
    }
    
    /**
     * Print error.
     */
    protected function error(string $message): void {
        fwrite(STDERR, $message . "\n");
    }
}
