<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Filament\Forms\Components\Contracts\HasValidationRules;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

class FormRequestResolver
{
    public function __construct(
        protected FakeRequestBuilder $requestBuilder,
        protected RuleMapper $ruleMapper,
        protected FieldCollector $fieldCollector,
    ) {}

    public function resolve(Schema $schema, FormRequestConfig $config): ResolvedFormRequestValidation
    {
        $livewire = $schema->getLivewire();
        $input = $this->resolveInput($schema, $config, $livewire);
        $formRequest = $this->createFormRequest($config, $livewire, $input);

        FormRequestSchemaRegistry::setResolvedFormRequest($livewire, $formRequest, $input);

        $fieldPaths = $this->fieldCollector->collectStatePaths($schema);
        $mapping = $this->ruleMapper->map($formRequest->rules(), $fieldPaths);

        return new ResolvedFormRequestValidation(
            formRequest: $formRequest,
            matchedRules: $mapping['matched'],
            orphanRules: $mapping['orphans'],
            messages: $this->mapMessages($formRequest->messages(), $fieldPaths),
            attributes: $this->mapAttributes($formRequest->attributes(), $fieldPaths),
            orphanMessages: $this->mapOrphanMessages($formRequest->messages(), $mapping['orphans']),
            orphanAttributes: $this->mapOrphanAttributes($formRequest->attributes(), $mapping['orphans']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveInput(Schema $schema, FormRequestConfig $config, Component $livewire): array
    {
        $state = $schema->getStateSnapshot();

        if ($config->mergeInput === null) {
            return $state;
        }

        return app()->call($config->mergeInput, [
            'state' => $state,
            'livewire' => $livewire,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function createFormRequest(FormRequestConfig $config, Component $livewire, array $input): FormRequest
    {
        $class = app()->call($config->class, [
            'livewire' => $livewire,
        ]);

        $request = $this->requestBuilder->build($input, $livewire);

        /** @var FormRequest $formRequest */
        $formRequest = $class::createFrom($request)->setContainer(app());

        return $formRequest;
    }

    /**
     * @param  array<string, string>  $messages
     * @param  array<int, string>  $fieldPaths
     * @return array<string, string>
     */
    protected function mapMessages(array $messages, array $fieldPaths): array
    {
        $mapped = [];

        foreach ($fieldPaths as $fieldPath) {
            $relativePath = $this->ruleMapper->toRelativePath($fieldPath);

            foreach ($messages as $key => $message) {
                if (! str_starts_with($key, "{$relativePath}.")) {
                    continue;
                }

                $rule = Str::after($key, "{$relativePath}.");
                $mapped["{$fieldPath}.{$rule}"] = $message;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $attributes
     * @param  array<int, string>  $fieldPaths
     * @return array<string, string>
     */
    protected function mapAttributes(array $attributes, array $fieldPaths): array
    {
        $mapped = [];

        foreach ($fieldPaths as $fieldPath) {
            $relativePath = $this->ruleMapper->toRelativePath($fieldPath);

            if (! array_key_exists($relativePath, $attributes)) {
                continue;
            }

            $mapped[$fieldPath] = $attributes[$relativePath];
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $messages
     * @param  array<string, array<int, mixed>>  $orphanRules
     * @return array<string, string>
     */
    protected function mapOrphanMessages(array $messages, array $orphanRules): array
    {
        $mapped = [];

        foreach (array_keys($orphanRules) as $orphanKey) {
            foreach ($messages as $key => $message) {
                if (! str_starts_with($key, "{$orphanKey}.")) {
                    continue;
                }

                $mapped[$key] = $message;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $attributes
     * @param  array<string, array<int, mixed>>  $orphanRules
     * @return array<string, string>
     */
    protected function mapOrphanAttributes(array $attributes, array $orphanRules): array
    {
        return Arr::only($attributes, array_keys($orphanRules));
    }
}
