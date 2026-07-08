<?php

namespace OccTherapist\FormRequestValidationForFilament;

use Closure;
use Livewire\Component;

class FormRequestConfig
{
    /**
     * @param  Closure(Component): class-string<\Illuminate\Foundation\Http\FormRequest>  $class
     * @param  (Closure(array, Component): array)|null  $mergeInput
     */
    public function __construct(
        public readonly Closure $class,
        public readonly ?Closure $mergeInput = null,
    ) {}
}
