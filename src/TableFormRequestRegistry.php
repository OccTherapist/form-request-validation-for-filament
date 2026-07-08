<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Filament\Tables\Table;
use WeakMap;

class TableFormRequestRegistry
{
    /** @var WeakMap<Table, FormRequestConfig> */
    protected static WeakMap $configs;

    public static function boot(): void
    {
        static::$configs = new WeakMap;
    }

    public static function attach(Table $table, FormRequestConfig $config): void
    {
        static::$configs[$table] = $config;
    }

    public static function has(Table $table): bool
    {
        return isset(static::$configs[$table]);
    }

    public static function get(Table $table): ?FormRequestConfig
    {
        return static::$configs[$table] ?? null;
    }
}
