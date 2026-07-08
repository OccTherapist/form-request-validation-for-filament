<?php

use OccTherapist\FormRequestValidationForFilament\RuleMapper;
use OccTherapist\FormRequestValidationForFilament\StatePathNormalizer;

it('maps rules by exact field name', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    $result = $mapper->map(
        ['email' => ['required', 'email']],
        ['data.email'],
    );

    expect($result['matched']['data.email'])->toBe(['required', 'email'])
        ->and($result['orphans'])->toBeEmpty();
});

it('maps rules using wildcard patterns', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    $result = $mapper->map(
        ['items.*.name' => ['required', 'string']],
        ['data.items.0.name', 'data.items.1.name'],
    );

    expect($result['matched']['data.items.0.name'])->toBe(['required', 'string'])
        ->and($result['matched']['data.items.1.name'])->toBe(['required', 'string'])
        ->and($result['orphans'])->toBeEmpty();
});

it('maps rules defined as pipe-separated strings', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    $result = $mapper->map(
        ['email' => 'required|email|max:255'],
        ['data.email'],
    );

    expect($result['matched']['data.email'])->toBe(['required', 'email', 'max:255']);
});

it('maps rules for mounted action form fields', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    $result = $mapper->map(
        ['title' => ['required', 'string']],
        ['mountedActions.0.data.title'],
    );

    expect($result['matched']['mountedActions.0.data.title'])->toBe(['required', 'string'])
        ->and($result['orphans'])->toBeEmpty();
});

it('returns orphan rules without matching fields', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    $result = $mapper->map(
        [
            'email' => ['required', 'email'],
            'terms_accepted' => ['accepted'],
        ],
        ['data.email'],
    );

    expect($result['matched']['data.email'])->toBe(['required', 'email'])
        ->and($result['orphans']['terms_accepted'])->toBe(['accepted']);
});

it('strips common state prefixes from field paths', function () {
    $mapper = new RuleMapper(new StatePathNormalizer);

    expect($mapper->toRelativePath('data.email'))->toBe('email')
        ->and($mapper->toRelativePath('mountedActions.0.data.email'))->toBe('email')
        ->and($mapper->toRelativePath('mountedActionsData.title'))->toBe('title');
});
