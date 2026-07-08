<?php

namespace OccTherapist\FormRequestValidationForFilament\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;

trait InteractsWithFormRequestValidation
{
    /**
     * @return array<string, mixed>
     */
    public function getFormRequestValidated(): array
    {
        if (! $this instanceof Component) {
            throw new \RuntimeException('Form request validation is only available on Livewire components.');
        }

        $formRequest = FormRequestSchemaRegistry::getResolvedFormRequest($this);
        $input = FormRequestSchemaRegistry::getResolvedInput($this);

        if (! $formRequest instanceof FormRequest || ! is_array($input)) {
            throw new \RuntimeException('No validated form request is available for this component.');
        }

        return Validator::make(
            $input,
            $formRequest->rules(),
            $formRequest->messages(),
            $formRequest->attributes(),
        )->validated();
    }
}
