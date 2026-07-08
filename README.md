# Form Request Validation for Filament

Use Laravel [Form Request](https://laravel.com/docs/validation#form-request-validation) validation inside Filament schemas — keep your rules, messages, and attributes in one place.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- Filament 4 or 5

## Installation

```bash
composer require occ-therapist/form-request-validation-for-filament
```

## Usage

Attach a Form Request to any Filament schema:

```php
use Filament\Schemas\Schema;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            TextInput::make('email'),
            TextInput::make('name'),
        ])
        ->formRequest(
            class: fn () => $this instanceof EditRecord
                ? UpdateUserRequest::class
                : StoreUserRequest::class,
            mergeInput: fn (array $state) => [
                ...$state,
                'account_type' => $this->record?->account_type,
            ],
        );
}
```

Rules from the Form Request are matched to fields automatically by name, including wildcard keys such as `items.*.name` for repeaters.

After a successful validation, use the trait to access validated data:

```php
use OccTherapist\FormRequestValidationForFilament\Concerns\InteractsWithFormRequestValidation;

class CreateUser extends CreateRecord
{
    use InteractsWithFormRequestValidation;

    protected function handleRecordCreation(array $data): Model
    {
        $data = $this->getFormRequestValidated();

        return User::create($data);
    }
}
```

## How it works

- **`rules()`**, **`messages()`**, and **`attributes()`** from the Form Request are applied to matching schema fields.
- Rules are re-resolved on every validation so dynamic rules (e.g. `required_if`) work with current form state.
- A simulated HTTP request provides route and input context to the Form Request.
- Existing field rules are merged; Form Request rules take precedence on conflicts.
- Rules without a matching field still run on submit; failures show a Filament notification.

> Call `->formRequest()` **after** `->components()` so the validation hook is appended correctly.

## Roadmap

- **v1.0** — Resource Create/Edit, standalone forms
- **v1.1** — Actions, wizards, relation managers
- **v1.2** — Table filters

## Testing

```bash
composer test
```

## License

MIT © [occTherapist](https://github.com/occTherapist)
