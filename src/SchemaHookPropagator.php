<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use OccTherapist\FormRequestValidationForFilament\Adapters\FilamentSchemaValidatorAdapter;

class SchemaHookPropagator
{
    public function __construct(
        protected FilamentSchemaValidatorAdapter $adapter,
    ) {}

    public function propagate(Schema $schema): void
    {
        foreach ($this->collectSchemas($schema) as $nestedSchema) {
            if ($nestedSchema === $schema) {
                continue;
            }

            $this->adapter->ensureValidationHook($nestedSchema);
        }
    }

    /**
     * @return array<int, Schema>
     */
    protected function collectSchemas(Schema $schema): array
    {
        $schemas = [$schema];

        foreach ($schema->getComponents(withActions: false, withHidden: true) as $component) {
            if ($component instanceof Step) {
                $stepSchema = $component->getChildSchema();

                if ($stepSchema instanceof Schema) {
                    $schemas = [...$schemas, $stepSchema, ...$this->collectSchemas($stepSchema)];
                }

                continue;
            }

            foreach ($component->getChildSchemas(withHidden: true) as $childSchema) {
                if ($childSchema->isDirectlyHidden()) {
                    continue;
                }

                $schemas = [...$schemas, ...$this->collectSchemas($childSchema)];
            }
        }

        return $schemas;
    }
}
