<?php

namespace OccTherapist\FormRequestValidationForFilament\Adapters;

use Closure;
use Filament\Schemas\Schema;
use OccTherapist\FormRequestValidationForFilament\Components\FormRequestValidationHook;
use OccTherapist\FormRequestValidationForFilament\FormRequestConfig;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;
use ReflectionProperty;

class FilamentSchemaValidatorAdapter implements SchemaValidatorAdapter
{
    public function registerFormRequestMacro(): void
    {
        Schema::macro('formRequest', function (
            Closure $class,
            ?Closure $mergeInput = null,
        ): Schema {
            /** @var Schema $schema */
            $schema = $this;

            FormRequestSchemaRegistry::attach($schema, new FormRequestConfig($class, $mergeInput));

            app(FilamentSchemaValidatorAdapter::class)->ensureValidationHook($schema);

            return $schema;
        });
    }

    public function ensureValidationHook(Schema $schema): void
    {
        if (FormRequestSchemaRegistry::hasHookAttached($schema)) {
            return;
        }

        $property = new ReflectionProperty($schema, 'components');
        $originalComponents = $property->getValue($schema);

        $property->setValue($schema, function () use ($schema, $originalComponents): array {
            /** @var array<int, mixed>|mixed $components */
            $components = is_callable($originalComponents)
                ? $schema->evaluate($originalComponents)
                : $originalComponents;

            $components = array_values(is_array($components) ? $components : [$components]);

            $components = array_values(array_filter(
                $components,
                fn (mixed $component): bool => ! $component instanceof FormRequestValidationHook,
            ));

            $components[] = FormRequestValidationHook::make('_form_request_validation_hook');

            return $components;
        });

        FormRequestSchemaRegistry::markHookAttached($schema);
    }
}
