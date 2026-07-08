<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use OccTherapist\FormRequestValidationForFilament\Adapters\FilamentSchemaValidatorAdapter;
use OccTherapist\FormRequestValidationForFilament\Adapters\FilamentTableValidatorAdapter;
use OccTherapist\FormRequestValidationForFilament\Livewire\FormRequestValidationComponentHook;
use OccTherapist\FormRequestValidationForFilament\Livewire\TableFiltersFormRequestComponentHook;
use OccTherapist\FormRequestValidationForFilament\TableFormRequestRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FormRequestValidationServiceProvider extends PackageServiceProvider
{
    public static string $name = 'form-request-validation-for-filament';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name);
    }

    public function packageRegistered(): void
    {
        FormRequestSchemaRegistry::boot();
        TableFormRequestRegistry::boot();
    }

    public function packageBooted(): void
    {
        app(FilamentSchemaValidatorAdapter::class)->registerFormRequestMacro();
        app(FilamentTableValidatorAdapter::class)->registerFiltersFormRequestMacro();

        if (class_exists(ComponentHookRegistry::class)) {
            ComponentHookRegistry::register(FormRequestValidationComponentHook::class);
            ComponentHookRegistry::register(TableFiltersFormRequestComponentHook::class);
        }
    }
}
