<?php

namespace Nexph\Runtime;

class EventBus
{
    private static array $listeners = [];
    private static array $componentListeners = [];
    
    public static function emit(string $componentId, string $event, mixed $data = null): void
    {
        $key = $componentId . ':' . $event;
        
        if (isset(self::$listeners[$key])) {
            foreach (self::$listeners[$key] as $callback) {
                call_user_func($callback, $data);
            }
        }
        
        if (isset(self::$componentListeners[$componentId][$event])) {
            foreach (self::$componentListeners[$componentId][$event] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }
    
    public static function on(string $componentId, string $event, callable $callback): void
    {
        $key = $componentId . ':' . $event;
        
        if (!isset(self::$listeners[$key])) {
            self::$listeners[$key] = [];
        }
        
        self::$listeners[$key][] = $callback;
    }
    
    public static function listen(string $componentId, string $event, callable $callback): void
    {
        if (!isset(self::$componentListeners[$componentId])) {
            self::$componentListeners[$componentId] = [];
        }
        
        if (!isset(self::$componentListeners[$componentId][$event])) {
            self::$componentListeners[$componentId][$event] = [];
        }
        
        self::$componentListeners[$componentId][$event][] = $callback;
    }
    
    public static function off(string $componentId, string $event): void
    {
        $key = $componentId . ':' . $event;
        unset(self::$listeners[$key]);
        
        if (isset(self::$componentListeners[$componentId][$event])) {
            unset(self::$componentListeners[$componentId][$event]);
        }
    }
    
    public static function clear(): void
    {
        self::$listeners = [];
        self::$componentListeners = [];
    }
}
