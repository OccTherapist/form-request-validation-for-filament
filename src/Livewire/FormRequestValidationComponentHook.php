<?php

namespace OccTherapist\FormRequestValidationForFilament\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\ComponentHook;
use OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry;

class FormRequestValidationComponentHook extends ComponentHook
{
    public function exception($exception, $stopPropagation): void
    {
        if (! $exception instanceof ValidationException) {
            return;
        }

        $livewire = $this->component;

        if (! method_exists($livewire, 'currentlyValidatingSchema')) {
            return;
        }

        $schema = $livewire->currentlyValidatingSchema();

        if ($schema === null) {
            return;
        }

        $config = FormRequestSchemaRegistry::resolve($schema);

        if ($config === null) {
            return;
        }

        $errors = $exception->validator->errors()->toArray();
        $orphanMessages = [];

        foreach ($errors as $key => $messages) {
            if (str_contains($key, '.')) {
                continue;
            }

            $orphanMessages[$key] = $messages;
        }

        if ($orphanMessages === []) {
            return;
        }

        $body = collect($orphanMessages)
            ->flatMap(fn (array $messages): array => $messages)
            ->implode(' ');

        if (class_exists(Notification::class)) {
            Notification::make()
                ->title(__('Validation error'))
                ->body($body)
                ->danger()
                ->send();
        }
    }
}
