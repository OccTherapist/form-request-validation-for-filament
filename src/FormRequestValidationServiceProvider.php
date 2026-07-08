<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use OccTherapist\FormRequestValidationForFilament\Adapters\FilamentSchemaValidatorAdapter;
use OccTherapist\FormRequestValidationForFilament\Livewire\FormRequestValidationComponentHook;
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
    }

    public function packageBooted(): void
    {
        app(FilamentSchemaValidatorAdapter::class)->registerFormRequestMacro();

        if (class_exists(ComponentHookRegistry::class)) {
            ComponentHookRegistry::register(FormRequestValidationComponentHook::class);
        }
    }
}
