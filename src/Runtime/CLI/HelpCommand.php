<?php
namespace Core\Runtime\CLI;

/**
 * Help command.
 */
class HelpCommand extends Command {
    protected string $name = 'help';
    protected string $description = 'Display help information';
    
    private CommandRegistry $registry;
    
    public function __construct(CommandRegistry $registry) {
        $this->registry = $registry;
    }
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $commandName = $parsed['arguments'][0] ?? null;
        
        if ($commandName) {
            return $this->showCommandHelp($commandName);
        }
        
        return $this->showGeneralHelp();
    }
    
    private function showGeneralHelp(): int {
        $this->output("Nexph Runtime CLI");
        $this->output("");
        $this->output("Usage:");
        $this->output("  nexph <command> [options] [arguments]");
        $this->output("");
        $this->output("Available Commands:");
        $this->output("");
        
        $commands = $this->registry->all();
        $groups = $this->groupCommands($commands);
        
        foreach ($groups as $group => $cmds) {
            $this->output($group . ":");
            foreach ($cmds as $cmd) {
                $name = str_pad($cmd->getName(), 20);
                $this->output("  {$name} {$cmd->getDescription()}");
            }
            $this->output("");
        }
        
        $this->output("Run 'nexph help <command>' for more information on a command.");
        
        return 0;
    }
    
    private function showCommandHelp(string $commandName): int {
        $command = $this->registry->get($commandName);
        
        if ($command === null) {
            $this->error("Error: Unknown command '{$commandName}'");
            return 1;
        }
        
        $this->output("Command: {$command->getName()}");
        $this->output("");
        $this->output("Description:");
        $this->output("  {$command->getDescription()}");
        $this->output("");
        
        $options = $command->getOptions();
        if (!empty($options)) {
            $this->output("Options:");
            foreach ($options as $option => $description) {
                $this->output("  {$option}");
                $this->output("      {$description}");
            }
            $this->output("");
        }
        
        return 0;
    }
    
    private function groupCommands(array $commands): array {
        $groups = [
            'Package' => [],
            'Module' => [],
            'Composer' => [],
            'Queue' => [],
            'Runtime' => [],
            'Schedule' => [],
            'Other' => [],
        ];
        
        foreach ($commands as $command) {
            $name = $command->getName();
            
            if (in_array($name, ['install', 'remove', 'update', 'restore', 'publish', 'search']) || str_starts_with($name, 'package:')) {
                $groups['Package'][] = $command;
            } elseif (str_starts_with($name, 'module:')) {
                $groups['Module'][] = $command;
            } elseif (str_starts_with($name, 'composer:')) {
                $groups['Composer'][] = $command;
            } elseif (str_starts_with($name, 'queue:')) {
                $groups['Queue'][] = $command;
            } elseif (str_starts_with($name, 'runtime:')) {
                $groups['Runtime'][] = $command;
            } elseif (str_starts_with($name, 'schedule:')) {
                $groups['Schedule'][] = $command;
            } else {
                $groups['Other'][] = $command;
            }
        }
        
        return array_filter($groups);
    }
}
