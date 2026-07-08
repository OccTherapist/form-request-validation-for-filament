<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Filament\Schemas\Schema;
use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;
use SplObjectStorage;
use WeakMap;

class FormRequestSchemaRegistry
{
    /** @var WeakMap<Schema, FormRequestConfig> */
    protected static WeakMap $configs;

    /** @var SplObjectStorage<Schema, true> */
    protected static SplObjectStorage $hooksAttached;

    /** @var WeakMap<Component, FormRequest> */
    protected static WeakMap $resolvedFormRequests;

    /** @var WeakMap<Component, array<string, mixed>> */
    protected static WeakMap $resolvedInputs;

    public static function boot(): void
    {
        static::$configs = new WeakMap;
        static::$hooksAttached = new SplObjectStorage;
        static::$resolvedFormRequests = new WeakMap;
        static::$resolvedInputs = new WeakMap;
    }

    public static function attach(Schema $schema, FormRequestConfig $config): void
    {
        static::$configs[$schema] = $config;
    }

    public static function has(Schema $schema): bool
    {
        return isset(static::$configs[$schema]);
    }

    public static function get(Schema $schema): ?FormRequestConfig
    {
        return static::resolve($schema);
    }

    public static function resolve(Schema $schema): ?FormRequestConfig
    {
        if (isset(static::$configs[$schema])) {
            return static::$configs[$schema];
        }

        $component = $schema->getParentComponent();

        while ($component !== null) {
            $container = $component->getContainer();

            if ($container instanceof Schema && isset(static::$configs[$container])) {
                return static::$configs[$container];
            }

            $component = $container instanceof Schema
                ? $container->getParentComponent()
                : null;
        }

        return null;
    }

    public static function shouldValidateOrphans(Schema $schema): bool
    {
        if (isset(static::$configs[$schema])) {
            return $schema->isRoot();
        }

        $component = $schema->getParentComponent();

        while ($component !== null) {
            $container = $component->getContainer();

            if ($container instanceof Schema && isset(static::$configs[$container])) {
                return $container->isRoot() && $schema === $container;
            }

            $component = $container instanceof Schema
                ? $container->getParentComponent()
                : null;
        }

        return $schema->isRoot();
    }

    public static function markHookAttached(Schema $schema): void
    {
        static::$hooksAttached[$schema] = true;
    }

    public static function hasHookAttached(Schema $schema): bool
    {
        return static::$hooksAttached->contains($schema);
    }

    public static function setResolvedFormRequest(Component $livewire, FormRequest $formRequest, array $input): void
    {
        static::$resolvedFormRequests[$livewire] = $formRequest;
        static::$resolvedInputs[$livewire] = $input;
    }

    public static function getResolvedFormRequest(Component $livewire): ?FormRequest
    {
        return static::$resolvedFormRequests[$livewire] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getResolvedInput(Component $livewire): ?array
    {
        return static::$resolvedInputs[$livewire] ?? null;
    }

    public static function forgetResolvedFormRequest(Component $livewire): void
    {
        unset(static::$resolvedFormRequests[$livewire], static::$resolvedInputs[$livewire]);
    }
}
