# Form Request Validation for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/occ-therapist/form-request-validation-for-filament.svg?style=flat-square)](https://packagist.org/packages/occ-therapist/form-request-validation-for-filament)
[![Total Downloads](https://img.shields.io/packagist/dt/occ-therapist/form-request-validation-for-filament.svg?style=flat-square)](https://packagist.org/packages/occ-therapist/form-request-validation-for-filament)
[![License](https://img.shields.io/packagist/l/occ-therapist/form-request-validation-for-filament.svg?style=flat-square)](https://packagist.org/packages/occ-therapist/form-request-validation-for-filament)
[![GitHub stars](https://img.shields.io/github/stars/OccTherapist/form-request-validation-for-filament?style=flat-square)](https://github.com/OccTherapist/form-request-validation-for-filament)

![Form Request Validation for Filament](thumbnail.jpg)

Use Laravel [Form Request](https://laravel.com/docs/validation#form-request-validation) validation inside Filament schemas. Define your rules, messages, and attributes once — reuse them across API routes, controllers, and Filament forms.

## Why this plugin?

Filament validates fields through dedicated methods like `->required()` and `->email()` on each input. That works well for simple forms, but validation logic often belongs in a central place — especially when the same rules already exist in a Form Request.

This plugin bridges that gap: attach a Form Request to your schema and its validation rules are automatically applied to the matching fields.

## Features

- **Form Request integration** — uses `rules()`, `messages()`, and `attributes()` from your existing Form Requests
- **Automatic field mapping** — matches rules to fields by name (`email` → `TextInput::make('email')`)
- **Wildcard support** — maps nested rules like `items.*.name` to repeater children
- **Context-aware** — choose different Form Requests per page (create vs. edit) via callback
- **Dynamic rules** — rules are re-resolved on every validation, so `required_if` and similar rules work with live form state
- **Request simulation** — provides route parameters and input context to Form Requests (e.g. `Rule::unique()->ignore($this->route('user'))`)
- **Smart rule merging** — combines existing field rules with Form Request rules; Form Request wins on conflicts
- **Orphan rule handling** — rules without a matching field still run on submit; failures show a Filament notification
- **Validated data helper** — optional `getFormRequestValidated()` after successful validation
- **Filament 4 & 5** — single package with an internal adapter layer

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 11, 12, or 13 |
| Filament | 4.x or 5.x |

## Installation

```bash
composer require occ-therapist/form-request-validation-for-filament
```

The package auto-registers via Laravel's package discovery. No manual service provider setup required.

## Quick start

### 1. Create a Form Request

```php
// app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
        ];
    }
}
```

### 2. Attach it to your Filament schema

```php
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
    return $schema
        ->components([
            TextInput::make('name'),
            TextInput::make('email')->email(),
        ])
        ->formRequest(
            class: fn () => $this instanceof EditRecord
                ? UpdateUserRequest::class
                : StoreUserRequest::class,
        );
}
```

> **Important:** Call `->formRequest()` **after** `->components()` so the internal validation hook is appended correctly.

Validation errors appear directly on the matching input fields, just like native Filament validation.

## API reference

### `Schema::formRequest()`

```php
$schema->formRequest(
    class: fn (Component $livewire): string => StoreUserRequest::class,
    mergeInput: fn (array $state, Component $livewire): array => $state,
);
```

| Parameter | Type | Required | Description |
|---|---|---|---|
| `class` | `Closure` | Yes | Returns the Form Request class for the current context. Receives `$livewire` via dependency injection. |
| `mergeInput` | `Closure` | No | Merges additional data into the simulated request input before rules are resolved. Receives `$state` and `$livewire`. |

#### `class` callback

Use this to select the right Form Request depending on the page or context:

```php
->formRequest(
    class: fn () => match (true) {
        $this instanceof CreateRecord => StorePostRequest::class,
        $this instanceof EditRecord => UpdatePostRequest::class,
        default => StorePostRequest::class,
    },
)
```

#### `mergeInput` callback

Some Form Requests rely on data that is not part of the visible form state. Use `mergeInput` to enrich the simulated request:

```php
->formRequest(
    class: fn () => UpdateUserRequest::class,
    mergeInput: fn (array $state) => [
        ...$state,
        'role' => $this->record?->role,
        'tenant_id' => filament()->getTenant()?->id,
    ],
)
```

This is useful for:

- Fields stored on the record but not shown in the form
- Route-like parameters needed by `Rule::unique()->ignore($this->route('user'))`
- Conditional rules that depend on values outside the form

## Field mapping

Rules are matched to schema fields **automatically by name**. No extra configuration per field is needed.

| Form Request rule key | Filament field | Match |
|---|---|---|
| `email` | `TextInput::make('email')` | Exact |
| `address.street` | `TextInput::make('address.street')` | Dot notation |
| `items.*.name` | Repeater child `TextInput::make('name')` | Wildcard |
| `name` | `TextInput::make('data.name')` | By field name |

### Repeaters & nested fields

Form Requests commonly validate repeater data with wildcard keys:

```php
// In your Form Request
'items.*.name' => ['required', 'string', 'max:255'],
'items.*.quantity' => ['required', 'integer', 'min:1'],
```

```php
// In your Filament schema
Repeater::make('items')
    ->schema([
        TextInput::make('name'),
        TextInput::make('quantity')->numeric(),
    ])
```

The plugin maps `items.*.name` to each repeater item's `name` field automatically.

## Rule merging

If a field already has Filament validation rules, both sets are merged:

```php
TextInput::make('email')->email()->nullable()
```

```php
// Form Request
'email' => ['required', 'email', 'unique:users']
```

**Result:** `string`, `required`, `email`, `unique:users`

- Form Request rules take **precedence** when rules conflict (e.g. `nullable` vs. `required`)
- Identical rules are **deduplicated**
- Non-conflicting field rules are **kept** (e.g. `string` from `->email()`)

## Orphan rules

Rules in the Form Request that have no matching schema field are called **orphan rules**. They still run during validation but cannot be displayed on an input.

```php
// Form Request
'terms_accepted' => ['accepted'],
```

If there is no `Checkbox::make('terms_accepted')` in the schema, a failed validation shows a **Filament notification** instead of an inline field error.

**Tip:** Add a matching field (visible or hidden) to show the error inline:

```php
Checkbox::make('terms_accepted')->label('I accept the terms'),
// or
Hidden::make('terms_accepted'),
```

## Accessing validated data

After successful validation, use the `InteractsWithFormRequestValidation` trait to retrieve the validated payload:

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

`getFormRequestValidated()` runs Laravel's validator with the Form Request's rules against the resolved input — the same data that was used to evaluate dynamic rules.

## How it works

```
Schema::formRequest()
        │
        ▼
  Form Request resolved with simulated HTTP request
  (form state + optional mergeInput + route parameters)
        │
        ▼
  rules(), messages(), attributes() extracted
        │
        ▼
  Rules mapped to schema fields (exact + wildcard)
        │
        ▼
  Merged with existing field rules → applied on validation
        │
        ▼
  Orphan rule failures → Filament notification
```

1. A hidden validation hook is injected into the schema when `formRequest()` is called.
2. On each validation, the Form Request is instantiated with a simulated request containing the current form state.
3. Rules are mapped to fields by name and applied through Filament's native validation pipeline.
4. Errors appear on the matching input fields; orphan errors trigger a notification.

## What is not supported (yet)

The following Form Request features are **not** part of v1:

| Feature | Status |
|---|---|
| `rules()` | Supported |
| `messages()` | Supported |
| `attributes()` | Supported |
| `authorize()` | Not supported — use Filament policies instead |
| `prepareForValidation()` | Not supported |
| `withValidator()` | Not supported |
| `passedValidation()` / `failedValidation()` | Not supported |

## Supported contexts

| Context | Version |
|---|---|
| Resource Create / Edit pages | v1.0 |
| Standalone forms / Settings pages | v1.0 |
| Action modals | v1.1 (planned) |
| Wizards (per-step validation) | v1.1 (planned) |
| Relation managers | v1.1 (planned) |
| Table filters | v1.2 (planned) |

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome! Please open an issue or pull request on [GitHub](https://github.com/OccTherapist/form-request-validation-for-filament).

## License

MIT © [occTherapist](https://github.com/OccTherapist)
