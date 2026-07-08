<?php

namespace OccTherapist\FormRequestValidationForFilament\Adapters;

use Filament\Schemas\Schema;
use OccTherapist\FormRequestValidationForFilament\Components\FormRequestValidationHook;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;
use ReflectionProperty;

interface SchemaValidatorAdapter
{
    public function registerFormRequestMacro(): void;

    public function ensureValidationHook(Schema $schema): void;
}
