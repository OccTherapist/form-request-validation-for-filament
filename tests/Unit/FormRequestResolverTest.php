<?php

use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Foundation\Http\FormRequest;
use Livewire\Component;
use Mockery\MockInterface;
use OccTherapist\FormRequestValidationForFilament\FakeRequestBuilder;
use OccTherapist\FormRequestValidationForFilament\FormRequestConfig;
use OccTherapist\FormRequestValidationForFilament\FormRequestResolver;

class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'type' => ['required', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'headline',
        ];
    }
}

class PrepareForValidationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => strtolower((string) $this->input('type')),
            'slug' => str($this->input('title'))->slug()->toString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'type' => ['required', 'in:draft,published'],
            'slug' => ['required', 'alpha_dash'],
        ];
    }
}

function createHasSchemasLivewire(): Component&HasSchemas
{
    /** @var Component&HasSchemas $livewire */
    $livewire = Mockery::mock(Component::class, HasSchemas::class);

    return $livewire;
}

it('resolves rules messages and attributes from a form request', function () {
    $livewire = createHasSchemasLivewire();

    /** @var MockInterface&\Filament\Schemas\Schema $schema */
    $schema = Mockery::mock(\Filament\Schemas\Schema::class);
    $schema->shouldReceive('getLivewire')->andReturn($livewire);
    $schema->shouldReceive('getStateSnapshot')->andReturn([
        'title' => 'Hello',
        'type' => 'draft',
    ]);

    $config = new FormRequestConfig(
        class: fn (): string => StorePostRequest::class,
    );

    $fieldCollector = Mockery::mock(\OccTherapist\FormRequestValidationForFilament\FieldCollector::class);
    $fieldCollector->shouldReceive('collectStatePaths')->with($schema)->andReturn(['data.title', 'data.type']);

    $resolver = new FormRequestResolver(
        new FakeRequestBuilder,
        app(\OccTherapist\FormRequestValidationForFilament\RuleMapper::class),
        $fieldCollector,
    );

    $resolved = $resolver->resolve($schema, $config);

    expect($resolved->matchedRules['data.title'])->toBe(['required', 'string'])
        ->and($resolved->messages['data.title.required'])->toBe('Title is required.')
        ->and($resolved->attributes['data.title'])->toBe('headline');
});

it('runs prepareForValidation before extracting rules and stores prepared input', function () {
    $livewire = createHasSchemasLivewire();

    /** @var MockInterface&\Filament\Schemas\Schema $schema */
    $schema = Mockery::mock(\Filament\Schemas\Schema::class);
    $schema->shouldReceive('getLivewire')->andReturn($livewire);
    $schema->shouldReceive('getStateSnapshot')->andReturn([
        'title' => 'Hello World',
        'type' => 'DRAFT',
    ]);

    $config = new FormRequestConfig(
        class: fn (): string => PrepareForValidationRequest::class,
    );

    $fieldCollector = Mockery::mock(\OccTherapist\FormRequestValidationForFilament\FieldCollector::class);
    $fieldCollector->shouldReceive('collectStatePaths')->with($schema)->andReturn([
        'data.title',
        'data.type',
        'data.slug',
    ]);

    $resolver = new FormRequestResolver(
        new FakeRequestBuilder,
        app(\OccTherapist\FormRequestValidationForFilament\RuleMapper::class),
        $fieldCollector,
    );

    $resolved = $resolver->resolve($schema, $config);

    expect($resolved->matchedRules['data.type'])->toBe(['required', 'in:draft,published'])
        ->and($resolved->matchedRules['data.slug'])->toBe(['required', 'alpha_dash'])
        ->and(\OccTherapist\FormRequestValidationForFilament\FormRequestSchemaRegistry::getResolvedInput($livewire))
        ->toMatchArray([
            'title' => 'Hello World',
            'type' => 'draft',
            'slug' => 'hello-world',
        ]);
});

it('builds a fake request with route parameters from a record', function () {
    $record = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'posts';

        public function getRouteKeyName(): string
        {
            return 'id';
        }

        public function getRouteKey(): mixed
        {
            return 42;
        }
    };

    $livewire = new class($record) extends Component
    {
        public function __construct(public object $record) {}

        public function getRecord(): object
        {
            return $this->record;
        }

        public function render(): string
        {
            return '';
        }
    };

    $request = (new FakeRequestBuilder)->build(['title' => 'Hello'], $livewire);

    expect($request->input('title'))->toBe('Hello')
        ->and($request->route('id'))->toBe(42);
});
