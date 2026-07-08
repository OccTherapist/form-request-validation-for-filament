<?php

namespace OccTherapist\FormRequestValidationForFilament\Components;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use OccTherapist\FormRequestValidationForFilament\FormRequestConfig;
use OccTherapist\FormRequestValidationForFilament\FormRequestResolver;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;
use OccTherapist\FormRequestValidationForFilament\ResolvedFormRequestValidation;
use OccTherapist\FormRequestValidationForFilament\RuleMerger;

class FormRequestValidationHook extends Hidden
{
    protected ?Schema $formRequestSchema = null;

    protected ?ResolvedFormRequestValidation $resolvedValidation = null;

    public static function make(string $name = '_form_request_validation_hook'): static
    {
        return app(static::class, ['name' => $name]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->hiddenLabel();
        $this->validatedWhenNotDehydrated(true);
    }

    public function schema(Schema $schema): static
    {
        $this->formRequestSchema = $schema;

        return $this;
    }

    public function dehydrateValidationRules(array &$rules): void
    {
        $this->resolvedValidation = null;

        $schema = $this->resolveSchema();
        $config = FormRequestSchemaRegistry::get($schema);

        if ($config === null) {
            return;
        }

        $resolved = $this->resolveValidation($schema, $config);
        $merger = app(RuleMerger::class);

        foreach ($resolved->matchedRules as $statePath => $formRequestRules) {
            $rules[$statePath] = $merger->merge($rules[$statePath] ?? [], $formRequestRules);
        }

        foreach ($resolved->orphanRules as $orphanKey => $orphanRules) {
            $rules[$orphanKey] = $orphanRules;
        }
    }

    public function dehydrateValidationMessages(array &$messages): void
    {
        $schema = $this->resolveSchema();
        $config = FormRequestSchemaRegistry::get($schema);

        if ($config === null) {
            return;
        }

        $resolved = $this->resolveValidation($schema, $config);

        foreach ($resolved->messages as $key => $message) {
            $messages[$key] = $message;
        }

        foreach ($resolved->orphanMessages as $key => $message) {
            $messages[$key] = $message;
        }
    }

    public function dehydrateValidationAttributes(array &$attributes): void
    {
        $schema = $this->resolveSchema();
        $config = FormRequestSchemaRegistry::get($schema);

        if ($config === null) {
            return;
        }

        $resolved = $this->resolveValidation($schema, $config);

        foreach ($resolved->attributes as $key => $attribute) {
            $attributes[$key] = $attribute;
        }

        foreach ($resolved->orphanAttributes as $key => $attribute) {
            $attributes[$key] = $attribute;
        }
    }

    protected function resolveSchema(): Schema
    {
        if ($this->formRequestSchema instanceof Schema) {
            return $this->formRequestSchema;
        }

        $container = $this->getContainer();

        if ($container instanceof Schema) {
            return $container;
        }

        throw new \RuntimeException('Unable to resolve the schema for form request validation.');
    }

    protected function resolveValidation(Schema $schema, FormRequestConfig $config): ResolvedFormRequestValidation
    {
        if ($this->resolvedValidation instanceof ResolvedFormRequestValidation) {
            return $this->resolvedValidation;
        }

        return $this->resolvedValidation = app(FormRequestResolver::class)->resolve($schema, $config);
    }
}
