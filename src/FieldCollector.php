<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Filament\Forms\Components\Contracts\HasValidationRules;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use OccTherapist\FormRequestValidationForFilament\Components\FormRequestValidationHook;

class FieldCollector
{
    /**
     * @return array<int, HasValidationRules&Component>
     */
    public function collectFields(Schema $schema): array
    {
        $fields = [];

        foreach ($schema->getComponents(withActions: false, withHidden: true) as $component) {
            if ($component instanceof FormRequestValidationHook) {
                continue;
            }

            if ($component instanceof HasValidationRules) {
                $fields[] = $component;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                if ($childSchema->isDirectlyHidden()) {
                    continue;
                }

                $fields = [...$fields, ...$this->collectFields($childSchema)];
            }
        }

        return $fields;
    }

    /**
     * @return array<int, string>
     */
    public function collectStatePaths(Schema $schema): array
    {
        return array_values(array_map(
            fn (HasValidationRules & Component $field): string => $field->getStatePath(),
            $this->collectFields($schema),
        ));
    }
}
