<?php

namespace OccTherapist\FormRequestValidationForFilament\Livewire;

use Filament\Schemas\Schema;
use Livewire\ComponentHook;
use OccTherapist\FormRequestValidationForFilament\Adapters\FilamentSchemaValidatorAdapter;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;
use OccTherapist\FormRequestValidationForFilament\SchemaHookPropagator;
use OccTherapist\FormRequestValidationForFilament\TableFormRequestRegistry;

class TableFiltersFormRequestComponentHook extends ComponentHook
{
    public function call($method, $params, $returnEarly, $metadata, $componentContext): ?\Closure
    {
        if ($method !== 'bootedInteractsWithTable') {
            return null;
        }

        return function (): void {
            $this->configureTableFiltersForm();
        };
    }

    public function update($propertyName, $fullPath, $newValue): ?\Closure
    {
        if ($propertyName !== 'tableFilters') {
            return null;
        }

        $livewire = $this->component;

        if (! method_exists($livewire, 'getTable')) {
            return null;
        }

        $table = $livewire->getTable();

        if (! TableFormRequestRegistry::has($table)) {
            return null;
        }

        if ($table->hasDeferredFilters()) {
            return null;
        }

        return function (): void {
            $this->component->getSchema('tableFiltersForm')?->validate();
        };
    }

    protected function configureTableFiltersForm(): void
    {
        $livewire = $this->component;

        if (! method_exists($livewire, 'getTable')) {
            return;
        }

        $table = $livewire->getTable();
        $config = TableFormRequestRegistry::get($table);

        if ($config === null) {
            return;
        }

        $schema = $livewire->getSchema('tableFiltersForm');

        if (! $schema instanceof Schema) {
            return;
        }

        FormRequestSchemaRegistry::attach($schema, $config);

        $adapter = app(FilamentSchemaValidatorAdapter::class);
        $adapter->ensureValidationHook($schema);
        app(SchemaHookPropagator::class)->propagate($schema);

        if (method_exists($livewire, 'cacheSchema')) {
            $livewire->cacheSchema('tableFiltersForm', $schema);
        }
    }
}
