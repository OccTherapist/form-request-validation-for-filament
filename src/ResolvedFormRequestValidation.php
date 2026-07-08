<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Illuminate\Foundation\Http\FormRequest;

class ResolvedFormRequestValidation
{
    /**
     * @param  array<string, array<int, mixed>>  $matchedRules
     * @param  array<string, array<int, mixed>>  $orphanRules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @param  array<string, string>  $orphanMessages
     * @param  array<string, string>  $orphanAttributes
     */
    public function __construct(
        public readonly FormRequest $formRequest,
        public readonly array $matchedRules,
        public readonly array $orphanRules,
        public readonly array $messages,
        public readonly array $attributes,
        public readonly array $orphanMessages,
        public readonly array $orphanAttributes,
    ) {}
}
