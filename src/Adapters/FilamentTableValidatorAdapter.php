<?php

namespace OccTherapist\FormRequestValidationForFilament\Adapters;

use Closure;
use Filament\Actions\Action;
use Filament\Tables\Table;
use OccTherapist\FormRequestValidationForFilament\FormRequestConfig;
use OccTherapist\FormRequestValidationForFilament\TableFormRequestRegistry;

class FilamentTableValidatorAdapter
{
    public function registerFiltersFormRequestMacro(): void
    {
        if (! method_exists(Table::class, 'macro')) {
            return;
        }

        Table::macro('filtersFormRequest', function (
            Closure $class,
            ?Closure $mergeInput = null,
        ): Table {
            /** @var Table $table */
            $table = $this;

            TableFormRequestRegistry::attach($table, new FormRequestConfig($class, $mergeInput));

            $table->filtersApplyAction(function (Action $action): Action {
                return $action->action(function (): void {
                    $this->getSchema('tableFiltersForm')?->validate();

                    $this->applyTableFilters();
                });
            });

            return $table;
        });
    }
}
